<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Validation;
use App\Services\ReservationPaymentManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Jeu d'entraînement au scan des billets pour les agents de contrôle.
 *
 * Crée — ou remet à zéro — un événement « [ENTRAÎNEMENT] Session scan agents »
 * par ORGANISATEUR ayant des agents : chaque agent voit l'événement de SON
 * organisateur dans le sélecteur du scanner (le scanner ne montre que les
 * événements de l'organisateur de l'agent connecté).
 *
 * Isolation des vraies données :
 *  - événements en brouillon (draft) : invisibles de l'accueil, des listes
 *    publiques et des ventes ;
 *  - tarif 0 FCFA : aucun impact sur les revenus et les statistiques ;
 *  - identifiables : slug `entrainement-scan-agents-{id}`, titre préfixé
 *    « [ENTRAÎNEMENT] » ;
 *  - supprimables en une commande (voir ci-dessous).
 *
 * Usage (sur l'environnement voulu) :
 *     php artisan db:seed --class=AgentTrainingSeeder
 *
 * Relancer la même commande remet les billets à l'état « valide » et efface
 * les scans précédents → nouvel entraînement sur les mêmes billets.
 *
 * Ne cibler qu'un seul organisateur (au lieu de tous ceux qui ont des agents) :
 *     PowerShell : $env:TRAINING_AGENT='agent@billeterie-express.sn'
 *     Bash       : TRAINING_AGENT=agent@billeterie-express.sn php artisan db:seed --class=AgentTrainingSeeder
 *
 * Suppression complète (php artisan tinker) :
 *     $events = App\Models\Event::where('slug', 'like', 'entrainement-scan-agents%')->get();
 *     App\Models\Validation::whereIn('event_id', $events->pluck('id'))->delete();
 *     $events->each->delete(); // cascade : tickets + réservations
 */
class AgentTrainingSeeder extends Seeder
{
    /** Nombre de billets d'entraînement par événement (≥ 10). */
    private const TICKET_COUNT = 12;

    /** Préfixe de slug : un événement par organisateur → slug-{organizerId}. */
    private const SLUG_PREFIX = 'entrainement-scan-agents';

    /** Titulaires variés : l'agent vérifie que le bon billet s'affiche. */
    private const HOLDERS = [
        'Mamadou Ndiaye',
        'Aissatou Diop',
        'Ousmane Fall',
        'Khadija Gueye',
        'Ibrahima Sarr',
        'Fatou Mbaye',
        'Cheikh Diallo',
        'Awa Sow',
        'Babacar Ndoye',
        'Mariama Ba',
        'Alioune Camara',
        'Ndeye Diouf',
    ];

    public function run(): void
    {
        $targets = $this->targets();

        foreach ($targets as $scopeId => $agents) {
            $event = $this->seedEvent((int) $scopeId);
            $this->report($event, $agents);
        }

        $this->command?->info(count($targets).' événement(s) d\'entraînement traité(s) — brouillon, 0 FCFA, supprimables en une commande (voir en-tête du seeder).');
    }

    /* ------------------------------------------------------------------ */
    /* Cibles : un organisateur → ses agents */
    /* ------------------------------------------------------------------ */

    /**
     * Organisateurs cibles → comptes agents qui scanneront.
     *
     * - TRAINING_AGENT (username ou email) : uniquement l'organisateur de
     *   ce compte ;
     * - sinon : chaque organisateur ayant au moins un compte agent actif
     *   (le premier agent classé par ID n'est pas forcément le bon).
     *
     * @return array<int, Collection<int, User>>
     */
    private function targets(): array
    {
        $wanted = $this->envAgent();

        if ($wanted !== null) {
            $user = User::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q->where('username', $wanted)->orWhere('email', $wanted))
                ->first();

            if (! $user) {
                throw new RuntimeException("TRAINING_AGENT=\"{$wanted}\" ne correspond à aucun compte actif (username ou email).");
            }

            if (! $user->isAgent()) {
                throw new RuntimeException("Le compte {$wanted} n'a pas le droit de scanner (rôle agent, organisateur ou admin requis).");
            }

            $scopeId = (int) ($user->organizer_id ?? $user->id);
            $agents = $this->agentsOf($scopeId);

            return [$scopeId => $agents->isEmpty() ? collect([$user]) : $agents];
        }

        $agents = User::query()
            ->where('role', User::ROLE_AGENT)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($agents->isEmpty()) {
            // Repli : un administrateur, sinon un organisateur, peut scanner.
            $fallback = User::query()->where('role', User::ROLE_ADMIN)->where('is_active', true)->orderBy('id')->first()
                ?? User::query()->where('role', User::ROLE_ORGANIZER)->where('is_active', true)->orderBy('id')->first();

            if (! $fallback) {
                throw new RuntimeException("Aucun compte agent/organisateur actif trouvé. Créez un compte agent ou relancez avec TRAINING_AGENT=<username ou email>.");
            }

            return [(int) ($fallback->organizer_id ?? $fallback->id) => collect([$fallback])];
        }

        return $agents
            ->groupBy(fn (User $agent) => (int) ($agent->organizer_id ?? $agent->id))
            ->all();
    }

    /** Comptes actifs au rôle « agent » rattachés à un organisateur (ou l'agent lui-même). */
    private function agentsOf(int $scopeId): Collection
    {
        return User::query()
            ->where('role', User::ROLE_AGENT)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('organizer_id', $scopeId)
                ->orWhere(fn ($q2) => $q2->whereNull('organizer_id')->where('id', $scopeId)))
            ->orderBy('id')
            ->get();
    }

    /** TRAINING_AGENT lu sans passer par env() (compatible config:cache). */
    private function envAgent(): ?string
    {
        $value = $_SERVER['TRAINING_AGENT'] ?? $_ENV['TRAINING_AGENT'] ?? getenv('TRAINING_AGENT');

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /* ------------------------------------------------------------------ */
    /* Événement + billets */
    /* ------------------------------------------------------------------ */

    /** Crée (ou remet à zéro) l'événement d'entraînement d'un organisateur. */
    private function seedEvent(int $organizerId): Event
    {
        $event = Event::updateOrCreate(
            ['slug' => self::SLUG_PREFIX.'-'.$organizerId],
            [
                'organizer_id' => $organizerId,
                'title' => '[ENTRAÎNEMENT] Session scan agents',
                'description' => "Données d'entraînement au scan des billets (générées par AgentTrainingSeeder). "
                    . "Aucune valeur commerciale — supprimer cet événement efface billets et réservations associés.",
                'event_date' => now()->addDays(7)->setTime(18, 0),
                'location' => "Dakar — site d'entraînement",
                'total_tickets' => 100,
                'event_type' => 'other',
                // Brouillon : invisible du public, mais visible de l'agent
                // (le sélecteur d'événements du scanner ne filtre pas le statut).
                'status' => Event::STATUS_DRAFT,
                'online_sales_enabled' => false,
            ],
        );

        $ticketType = TicketType::updateOrCreate(
            ['event_id' => $event->id, 'name' => 'Standard'],
            [
                'description' => "Tarif d'entraînement (0 FCFA — aucun impact sur les revenus)",
                'price' => 0,
                'quantity' => 100,
                'is_active' => true,
            ],
        );

        // Complète les billets manquants (1re exécution : création des 12).
        $missing = self::TICKET_COUNT - Ticket::where('event_id', $event->id)->count();

        if ($missing > 0) {
            $this->createTrainingReservation($ticketType, $missing);
        }

        // Remise à zéro : les billets déjà scannés redeviennent valides et
        // l'historique de scans de l'entraînement est effacé.
        $ticketIds = Ticket::where('event_id', $event->id)->pluck('id');

        Ticket::where('event_id', $event->id)->update([
            'status' => Ticket::STATUS_VALID,
            'used_at' => null,
            'validated_by' => null,
        ]);

        Validation::where('event_id', $event->id)
            ->orWhereIn('ticket_id', $ticketIds)
            ->delete();

        // Titulaires variés, dans l'ordre des billets (idempotent).
        Ticket::where('event_id', $event->id)
            ->orderBy('id')
            ->get()
            ->each(function (Ticket $ticket, int $index): void {
                $name = self::HOLDERS[$index % count(self::HOLDERS)];

                if ($ticket->holder_name !== $name || $ticket->holder_email !== 'entrainement'.($index + 1).'@exemple.sn') {
                    $ticket->update([
                        'holder_name' => $name,
                        'holder_email' => 'entrainement'.($index + 1).'@exemple.sn',
                    ]);
                }
            });

        return $event;
    }

    /** Réservation payée de tarif 0 → génère les billets (même chemin que le paiement réel). */
    private function createTrainingReservation(TicketType $ticketType, int $quantity): void
    {
        $reservation = Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $ticketType->event_id,
            'ticket_type_id' => $ticketType->id,
            'user_id' => null,
            'customer_name' => 'Session entraînement scan',
            'customer_email' => 'entrainement@exemple.sn',
            'customer_phone' => '+221770000000',
            'quantity' => $quantity,
            'unit_price' => $ticketType->price,
            'total_amount' => $ticketType->price * $quantity,
            'payment_status' => Reservation::STATUS_PAID,
            'channel' => Reservation::CHANNEL_ONLINE,
            'paid_at' => now(),
        ]);

        $ticketType->increment('sold_online', $quantity);

        app(ReservationPaymentManager::class)->generateTickets($reservation);
    }

    /* ------------------------------------------------------------------ */
    /* Compte rendu */
    /* ------------------------------------------------------------------ */

    /** @param  Collection<int, User>  $agents */
    private function report(Event $event, Collection $agents): void
    {
        $tickets = Ticket::where('event_id', $event->id)->orderBy('id')->get();

        $this->command?->info('[ENTRAÎNEMENT] « '.$event->title.' » — slug : '.$event->slug);
        $this->command?->info('  Statut : brouillon (invisible du public) — organisateur #'.$event->organizer_id.' — '.$event->event_date->format('d/m/Y H:i'));
        $this->command?->info('  Compte(s) pour scanner : '.$agents->map(fn (User $agent) => $agent->email.' ('.$agent->username.')')->implode(' , '));
        $this->command?->info('  '.$tickets->count().' billet(s) valide(s). Relancer la commande = remise à zéro des billets scannés.');
        $this->command?->newLine();

        $this->command?->info('Codes à saisir dans la saisie manuelle de /agent/scanner :');
        $this->command?->table(
            ['#', 'Code', 'Titulaire'],
            $tickets->map(fn (Ticket $ticket, int $index) => [$index + 1, $ticket->code, $ticket->holder_name])->all(),
        );

        $this->command?->info('QR à scanner avec la caméra (liens signés, valables 30 jours) :');

        foreach ($tickets as $ticket) {
            $this->command?->line('  '.$ticket->code.'  '.URL::temporarySignedRoute('tickets.show', now()->addDays(30), ['ticket' => $ticket->code]));
        }

        $this->command?->newLine();
        $this->command?->info('Scanner : '.route('agent.scan'));
        $this->command?->info('Liste des billets : '.route('agent.index'));
        $this->command?->newLine();
    }
}
