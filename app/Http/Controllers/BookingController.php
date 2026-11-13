<?php

namespace App\Http\Controllers;

use App\Exceptions\DexPayException;
use App\Models\ArtistPointOfSale;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\TicketType;
use App\Services\DexPay\DexPayAccount;
use App\Services\DexPay\DexPayCheckout;
use App\Services\ReservationPaymentManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(
        private readonly DexPayCheckout $dexpay,
        private readonly DexPayAccount $account,
        private readonly ReservationPaymentManager $payments,
    ) {}

    /**
     * Page de réservation : le récapitulatif part des quantités choisies sur la
     * page événement (?qty[{typeId}]={quantité}). Sans sélection, la page laisse
     * ajouter les types de billets directement dans le formulaire.
     *
     * La réservation et le paiement sont fusionnés : la page affiche aussi le
     * choix du moyen de paiement DexPay, et `store()` démarre la checkout
     * immédiatement après la création des réservations.
     */
    public function create(Request $request, Event $event): View|RedirectResponse
    {
        $artistPos = $this->artistPosFor($request, $event);

        if (! $event->isOnSale()) {
            return redirect()->to($this->eventUrlWithPos($event, $artistPos))
                ->with('error', 'Les réservations en ligne ne sont pas ouvertes pour cet événement.');
        }

        // Quota du point de vente artiste épuisé : le lien ne réserve plus.
        if ($artistPos?->isExhausted()) {
            return redirect()->to($this->eventUrlWithPos($event, $artistPos))
                ->with('error', 'Le stock assigné à ce lien d\'artiste est épuisé. Vous pouvez réserver sans son lien.');
        }

        $event->load('ticketTypes');

        $types = $event->ticketTypes->where('is_active', true)->values();
        $qtys = [];

        foreach ((array) $request->input('qty', []) as $typeId => $qty) {
            $typeId = (int) $typeId;
            $type = $types->firstWhere('id', $typeId);

            if (! $type || ! is_numeric($qty)) {
                continue;
            }

            $available = $type->available_online;

            // Sous le lien d'artiste, le plafond est aussi le solde restant
            // sur son point de vente pour ce type de billet.
            $posRemaining = $artistPos?->remainingFor($typeId);

            if ($posRemaining !== null) {
                $available = $available === null ? $posRemaining : min($available, $posRemaining);
            }

            $limit = $available === null ? 20 : min(20, $available);
            $qty = (int) $qty;

            if ($qty >= 1 && $limit >= 1) {
                $qtys[$typeId] = min($qty, $limit);
            }
        }

        $totalAmount = (int) $types->sum(fn ($t) => ($t->price ?? 0) * ($qtys[$t->id] ?? 0));

        // La référence est affichée sur la page (« Réservation BEX-… ») avant
        // même que la réservation n'existe : elle est réutilisée au POST si
        // elle est toujours libre.
        $operators = [];
        $serviceFee = 0;
        $dexpayFee = 0;

        // Pas d'appel API quand DexPay n'est pas configuré : les tests rendent
        // la page sans clés et ne doivent rien appeler.
        if ($this->dexpay->client()->isConfigured()) {
            $operators = $this->account->collectOperators();
            $serviceFee = dexpay_service_fee($totalAmount, $operators[0] ?? null);
            // Avec client_support_fee=true, DexPay prélève en plus ses frais
            // d'encaissement au même taux : on les affiche pour que le client
            // voie le total réellement débité. Sans ce flag, DexPay ne débite
            // pas ce second frais — on n'affiche donc que la surcharge.
            $dexpayFee = config('dexpay.client_support_fee')
                ? dexpay_service_fee($totalAmount, $operators[0] ?? null)
                : 0;
        }

        return view('booking.create', [
            'event' => $event,
            'types' => $types,
            'qtys' => $qtys,
            'artistPos' => $artistPos,
            'operators' => $operators,
            'sandbox' => $this->dexpay->client()->isSandbox(),
            'configured' => $this->dexpay->client()->isConfigured(),
            'service_fee' => $serviceFee,
            'dexpay_fee' => $dexpayFee,
            'to_pay' => $totalAmount + $serviceFee + $dexpayFee,
            'reference' => Reservation::makeReference(),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse|JsonResponse
    {
        abort_unless($event->isOnSale(), 404);

        // Même garde-fou que create() : jamais de réservation attribuée à un
        // lien d'artiste dont le quota est épuisé.
        $artistPos = $this->artistPosFor($request, $event);

        if ($artistPos?->isExhausted()) {
            $message = 'Le stock assigné à ce lien d\'artiste est épuisé. Vous pouvez réserver sans son lien.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->to($this->eventUrlWithPos($event, $artistPos))->with('error', $message);
        }

        $operator = trim((string) $request->input('operator', ''));

        // Le choix du moyen de paiement est validé AVANT toute écriture : un
        // opérateur inconnu (ou un service non configuré) ne doit jamais créer
        // de réservation. `operator` reste nullable — sans lui, on conserve
        // l'ancien comportement (redirection vers la page de paiement).
        if ($operator !== '') {
            if (! $this->dexpay->client()->isConfigured()) {
                return back()->withInput()->withErrors([
                    'operator' => 'Le service de paiement n\'est pas configuré. Renseignez DEXPAY_API_KEY dans le fichier .env.',
                ]);
            }

            $codes = array_column($this->account->collectOperators(), 'code');

            if (! in_array($operator, $codes, true)) {
                return back()->withInput()->withErrors([
                    'operator' => 'Ce moyen de paiement n\'est pas disponible.',
                ]);
            }
        }

        $phoneRule = $operator !== ''
            ? ['required', 'string', 'max:30']
            : ['nullable', 'string', 'max:30'];
        $useRecap = $request->has('qty') || ! $request->filled('ticket_type_id');

        if ($useRecap) {
            $data = $request->validate([
                'customer_name' => ['required', 'string', 'min:3', 'max:120'],
                'customer_phone' => $phoneRule,
                'qty' => ['required', 'array', 'min:1'],
                'qty.*' => ['required', 'integer', 'min:1', 'max:20'],
            ], [
                'qty.required' => 'Sélectionnez au moins un billet.',
                'qty.min' => 'Sélectionnez au moins un billet.',
                'qty.*.max' => '20 tickets maximum par type de billet.',
                'customer_phone.required' => 'Indiquez le numéro de téléphone qui recevra la demande de paiement.',
            ], [
                'customer_name' => 'nom',
                'customer_phone' => 'téléphone',
                'qty' => 'sélection de billets',
                'qty.*' => 'quantité',
            ]);

            $types = TicketType::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->whereIn('id', array_keys($data['qty']))
                ->get()
                ->keyBy('id');

            if ($types->count() !== count($data['qty'])) {
                return back()->withInput()
                    ->withErrors(['qty' => 'Un des types de billets sélectionnés n\'est plus disponible.']);
            }

            $lines = [];

            foreach ($data['qty'] as $typeId => $qty) {
                $lines[] = ['type' => $types->get((int) $typeId), 'qty' => (int) $qty];
            }
        } else {
            // Format historique : un seul type de billet.
            $data = $request->validate([
                'customer_name' => ['required', 'string', 'min:3', 'max:120'],
                'customer_phone' => $phoneRule,
                'ticket_type_id' => ['required', Rule::exists('ticket_types', 'id')->where('event_id', $event->id)],
                'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            ], [
                'customer_phone.required' => 'Indiquez le numéro de téléphone qui recevra la demande de paiement.',
            ], [
                'customer_name' => 'nom',
                'customer_phone' => 'téléphone',
                'ticket_type_id' => 'type de ticket',
                'quantity' => 'quantité',
            ]);

            $ticketType = TicketType::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->findOrFail($data['ticket_type_id']);

            $lines = [['type' => $ticketType, 'qty' => (int) $data['quantity']]];
        }

        $single = count($lines) === 1;

        foreach ($lines as $line) {
            $available = $line['type']->available_online;

            // Lien d'artiste : jamais plus que le solde assigné au point de
            // vente pour ce type de billet.
            $posRemaining = $artistPos?->remainingFor($line['type']->id);

            if ($posRemaining !== null) {
                $available = $available === null ? $posRemaining : min($available, $posRemaining);
            }

            if ($available !== null && $line['qty'] > $available) {
                $message = 'Il ne reste que '.($available ?: 0).' ticket(s) disponible(s) pour ce type.';

                if (! $single) {
                    $message = 'Il ne reste que '.($available ?: 0).' ticket(s) disponible(s) pour le type '.$line['type']->name.'.';
                }

                return back()->withInput()->with('error', $message);
            }
        }

        // Référence affichée sur la page de réservation : réutilisée pour la
        // première réservation si elle est toujours libre.
        $requestedReference = trim((string) $request->input('reference', ''));
        $firstReference = Reservation::makeReference();

        if ($requestedReference !== '' && preg_match('/^BEX-\d{6}-[A-Z0-9]{6}$/', $requestedReference)) {
            $taken = Reservation::where('reference', $requestedReference)->exists();

            if (! $taken) {
                $firstReference = $requestedReference;
            }
        }

        $reservations = DB::transaction(function () use ($lines, $data, $event, $firstReference, $artistPos) {
            $created = [];

            foreach ($lines as $index => $line) {
                $ticketType = TicketType::query()
                    ->whereKey($line['type']->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $created[] = Reservation::create([
                    'reference' => $index === 0 ? $firstReference : Reservation::makeReference(),
                    'event_id' => $event->id,
                    'ticket_type_id' => $ticketType->id,
                    'user_id' => auth()->id(),
                    'customer_name' => $data['customer_name'],
                    'customer_email' => null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'customer_city' => null,
                    'customer_address' => null,
                    'quantity' => $line['qty'],
                    'unit_price' => $ticketType->price,
                    'total_amount' => $ticketType->price * $line['qty'],
                    'payment_status' => Reservation::STATUS_PENDING,
                    'channel' => Reservation::CHANNEL_ONLINE,
                    // Traçage : créditée à l'artiste si créée via son lien.
                    'artist_pos_id' => $artistPos?->id,
                ]);
            }

            return $created;
        });

        // Autorise cette session (invité ou client) à suivre le paiement.
        foreach ($reservations as $reservation) {
            $request->session()->put('reservation_access.'.$reservation->id, true);
        }

        // Toutes les réservations de la commande, affichées sur la page de succès.
        $request->session()->put(
            'payment_refs',
            array_map(fn (Reservation $r) => $r->reference, $reservations),
        );

        // Aucun opérateur choisi : ancien comportement, la page de paiement
        // reste accessible (repli, anciens liens, tests existants).
        if ($operator === '') {
            return redirect()->route('payment.show', $reservations[0]->reference);
        }

        // Paiement fusionné : on démarre immédiatement la checkout DexPay et
        // on envoie le client directement sur la page hébergée.
        try {
            $this->payments->startOnlinePayment(
                $reservations[0],
                (string) $data['customer_phone'],
                $operator,
            );
        } catch (DexPayException $e) {
            // Repli : la réservation existe, la page de paiement reste le bon
            // endroit pour réessayer.
            return redirect()->route('payment.show', $reservations[0]->reference)
                ->with('error', $e->getMessage());
        }

        $reservations[0]->refresh();
        $payment = $reservations[0]->latestPayment;
        $paymentUrl = $payment?->payment_url;

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => filled($paymentUrl),
                'payment_url' => $paymentUrl,
                'reference' => $reservations[0]->reference,
                'message' => filled($paymentUrl) ? null : 'Impossible de démarrer le paiement.',
            ], filled($paymentUrl) ? 200 : 422);
        }

        if (filled($paymentUrl)) {
            // DexPay refuse iframe et popup : seule la navigation top-level
            // fonctionne (redirect 302 côté formulaire).
            return redirect()->away($paymentUrl);
        }

        return redirect()->route('payment.show', $reservations[0]->reference)
            ->with('error', 'Impossible de démarrer le paiement. Réessayez depuis la page de paiement.');
    }

    /**
     * URL de retour vers la page de l'événement. Si l'utilisateur est dans
     * le flux d'un lien d'artiste, le jeton est réinjecté (?pov=) : la trace
     * en session est close dès qu'on revient sur la page sans le paramètre
     * (le badge « Vous réservez via le lien de … » ne doit pas persister
     * après avoir quitté le lien).
     */
    private function eventUrlWithPos(Event $event, ?ArtistPointOfSale $artistPos): string
    {
        return route('events.show', $event)
            . ($artistPos ? '?pov=' . $artistPos->token : '');
    }

    /**
     * Point de vente artiste actif dans la session pour cet événement
     * (positionné par EventController::show lors du passage par le lien
     * partagé de l'artiste). Renvoie null hors lien d'artiste — ou si le
     * point de vente a été désactivé entre-temps (lien mort : ni blocage,
     * ni attribution).
     */
    private function artistPosFor(Request $request, Event $event): ?ArtistPointOfSale
    {
        $stored = $request->session()->get('artist_pos');

        if (! is_array($stored) || (int) ($stored['event_id'] ?? 0) !== $event->id) {
            return null;
        }

        return ArtistPointOfSale::where('token', (string) ($stored['token'] ?? ''))
            ->where('is_active', true)
            ->first();
    }
}
