<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\PosSale;
use App\Models\Reservation;
use App\Models\StockAllocation;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Validation;
use App\Services\ReservationPaymentManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('username', 'admin')->exists()) {
            $this->command?->info('Données démo déjà présentes —Seeder ignoré.');

            return;
        }

        $payments = app(ReservationPaymentManager::class);

        /* ---------------------------------------------------------------- */
        /* Comptes */
        /* ---------------------------------------------------------------- */

        $admin = User::factory()->admin()->firstOrCreate(
            ['username' => 'admin'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Plateforme',
                'email' => 'admin@billeterie-express.sn',
            ]
        );

        $organizer = User::factory()->organizer()->firstOrCreate(
            ['username' => 'organisateur'],
            [
                'first_name' => 'Ousmane',
                'last_name' => 'Ndiaye',
                'email' => 'organisateur@billeterie-express.sn',
                'phone' => '+221771234567',
            ]
        );

        $secondOrganizer = User::factory()->organizer()->firstOrCreate(
            ['username' => 'fatou.seck'],
            [
                'first_name' => 'Fatou',
                'last_name' => 'Seck',
                'email' => 'fatou.seck@billeterie-express.sn',
            ]
        );

        $agent = User::factory()->agent($organizer)->firstOrCreate(
            ['username' => 'agent.moussa'],
            [
                'first_name' => 'Moussa',
                'last_name' => 'Fall',
                'email' => 'agent@billeterie-express.sn',
            ]
        );

        $seller = User::factory()->pointOfSale($organizer)->firstOrCreate(
            ['username' => 'aminata.diop'],
            [
                'first_name' => 'Aminata',
                'last_name' => 'Diop',
                'email' => 'pos@billeterie-express.sn',
            ]
        );

        $customer = User::factory()->firstOrCreate(
            ['username' => 'khadija.mbaye'],
            [
                'first_name' => 'Khadija',
                'last_name' => 'Mbaye',
                'email' => 'client@billeterie-express.sn',
            ]
        );

        /* ---------------------------------------------------------------- */
        /* Événements + types de tickets */
        /* ---------------------------------------------------------------- */

        $concert = $this->event($organizer, [
            'title' => 'Festival Teranga 2026',
            'description' => "La plus grande soirée musicale du Sénégal avec plus de 20 artistes.\n\nUne expérience unique entre sonorités traditionnelles et innovation.",
            'event_date' => now()->addDays(45)->setTime(20, 0),
            'location' => 'Stade Abdoulaye Wade, Diamniadio',
            'total_tickets' => 2000,
            'event_type' => 'festival',
        ]);

        $concertTypes = [
            $this->ticketType($concert, ['name' => 'Standard', 'price' => 5000, 'quantity' => 1200]),
            $this->ticketType($concert, ['name' => 'VIP', 'price' => 15000, 'quantity' => 300]),
            $this->ticketType($concert, ['name' => 'Tribune', 'price' => 25000, 'quantity' => 100]),
        ];

        $match = $this->event($organizer, [
            'title' => 'ASC Jaraaf vs Casa Sport',
            'description' => 'Championnat national : la grande affiche du dimanche soir.',
            'event_date' => now()->addDays(12)->setTime(17, 0),
            'location' => 'Stade Demba Diop, Dakar',
            'total_tickets' => 800,
            'event_type' => 'sport',
        ]);

        $matchTypes = [
            $this->ticketType($match, ['name' => 'Tribune', 'price' => 2000, 'quantity' => 500]),
            $this->ticketType($match, ['name' => 'Virage', 'price' => 3500, 'quantity' => 300]),
        ];

        $theatre = $this->event($secondOrganizer, [
            'title' => 'Cérémonie Kwélif',
            'description' => 'Une soirée de poésie et de tradition.',
            'event_date' => now()->addDays(25)->setTime(19, 30),
            'location' => 'Théâtre national Daniel Sorano',
            'total_tickets' => 300,
            'event_type' => 'theatre',
        ]);

        $theatreTypes = [
            $this->ticketType($theatre, ['name' => 'Fosse', 'price' => 7500, 'quantity' => 200]),
        ];

        $this->event($secondOrganizer, [
            'title' => 'Séminaire entrepreneuriat',
            'event_date' => now()->addDays(60)->setTime(9, 0),
            'location' => 'Dakar International Conference Center',
            'total_tickets' => 400,
            'event_type' => 'conference',
            'status' => Event::STATUS_DRAFT,
        ]);

        /* ---------------------------------------------------------------- */
        /* Stock physique attribué au point de vente */
        /* ---------------------------------------------------------------- */

        $this->allocate($concertTypes[0], $seller, 150);
        $this->allocate($concertTypes[1], $seller, 30);
        $this->allocate($matchTypes[0], $seller, 80);

        /* ---------------------------------------------------------------- */
        /* Réservations payées + tickets générés */
        /* ---------------------------------------------------------------- */

        $this->paidReservation($payments, $customer, $concert, $concertTypes[1], 2, now()->subDays(3));
        $this->paidReservation($payments, null, $concert, $concertTypes[0], 3, now()->subDays(2));
        $this->paidReservation($payments, $customer, $match, $matchTypes[1], 4, now()->subDays(1));
        $this->paidReservation($payments, null, $theatre, $theatreTypes[0], 2, now()->subHour(5));

        $this->pendingReservation($customer, $concert, $concertTypes[0], 1);

        /* ---------------------------------------------------------------- */
        /* Vente physique + validation à l'entrée */
        /* ---------------------------------------------------------------- */

        $this->physicalSale($payments, $seller, $concertTypes[0], 3, 'Modou Fall', '+221778888777');

        $ticket = Ticket::where('status', Ticket::STATUS_VALID)
            ->where('event_id', $concert->id)
            ->orderBy('id')
            ->first();

        if ($ticket) {
            $ticket->update(['status' => Ticket::STATUS_USED, 'used_at' => now()->subHours(3), 'validated_by' => $agent->id]);

            Validation::create([
                'ticket_id' => $ticket->id,
                'event_id' => $ticket->event_id,
                'agent_id' => $agent->id,
                'ticket_code' => $ticket->code,
                'status' => Validation::STATUS_VALID,
                'message' => Validation::MSG_WELCOME,
                'scanned_code' => $ticket->code,
            ]);
        }

        $this->command?->newLine();
        $this->command?->info('Comptes de démonstration (mot de passe : password)');
        $this->command?->table(
            ['Rôle', 'E-mail'],
            [
                ['Administrateur', $admin->email],
                ['Organisateur', $organizer->email],
                ['Organisateur 2', $secondOrganizer->email],
                ['Agent de contrôle', $agent->email],
                ['Point de vente', $seller->email],
                ['Client', $customer->email],
            ]
        );
    }

    /* ------------------------------------------------------------------ */

    private function event(User $organizer, array $attributes): Event
    {
        return Event::create([
            ...$attributes,
            'organizer_id' => $organizer->id,
            'slug' => Event::slugFor($attributes['title']),
            'status' => $attributes['status'] ?? Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);
    }

    private function ticketType(Event $event, array $attributes): TicketType
    {
        return $event->ticketTypes()->create([
            ...$attributes,
            'is_active' => true,
        ]);
    }

    private function allocate(TicketType $ticketType, User $seller, int $quantity): StockAllocation
    {
        return StockAllocation::create([
            'ticket_type_id' => $ticketType->id,
            'user_id' => $seller->id,
            'allocated' => $quantity,
            'sold' => 0,
        ]);
    }

    private function paidReservation(
        ReservationPaymentManager $payments,
        ?User $user,
        Event $event,
        TicketType $ticketType,
        int $quantity,
        Carbon $when,
    ): Reservation {
        $reservation = Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'user_id' => $user?->id,
            'customer_name' => $user?->full_name ?? 'Client invité',
            'customer_email' => $user?->email ?? 'invite'.fake()->unique()->numerify('####').'@exemple.sn',
            'customer_phone' => '+22177'.fake()->numerify('#######'),
            'quantity' => $quantity,
            'unit_price' => $ticketType->price,
            'total_amount' => $ticketType->price * $quantity,
            'payment_status' => Reservation::STATUS_PAID,
            'channel' => Reservation::CHANNEL_ONLINE,
            'payment_operator' => fake()->randomElement(['wave', 'orange_money']),
            'paid_at' => $when,
        ]);

        $reservation->forceFill(['created_at' => $when, 'updated_at' => $when])->saveQuietly();

        $ticketType->increment('sold_online', $quantity);

        $payments->generateTickets($reservation);

        return $reservation;
    }

    private function pendingReservation(User $user, Event $event, TicketType $ticketType, int $quantity): Reservation
    {
        return Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'user_id' => $user->id,
            'customer_name' => $user->full_name,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'quantity' => $quantity,
            'unit_price' => $ticketType->price,
            'total_amount' => $ticketType->price * $quantity,
            'payment_status' => Reservation::STATUS_PENDING,
            'channel' => Reservation::CHANNEL_ONLINE,
        ]);
    }

    private function physicalSale(
        ReservationPaymentManager $payments,
        User $seller,
        TicketType $ticketType,
        int $quantity,
        string $buyer,
        string $phone,
    ): void {
        $allocation = StockAllocation::where('ticket_type_id', $ticketType->id)
            ->where('user_id', $seller->id)
            ->first();

        if (! $allocation) {
            return;
        }

        $unitPrice = $ticketType->price;

        $allocation->increment('sold', $quantity);
        $ticketType->increment('sold_physical', $quantity);

        $reservation = Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $ticketType->event_id,
            'ticket_type_id' => $ticketType->id,
            'user_id' => $seller->id,
            'customer_name' => $buyer,
            'customer_email' => $seller->email,
            'customer_phone' => $phone,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $unitPrice * $quantity,
            'payment_status' => Reservation::STATUS_PAID,
            'channel' => Reservation::CHANNEL_PHYSICAL,
            'sold_by' => $seller->id,
            'paid_at' => now(),
        ]);

        PosSale::create([
            'stock_allocation_id' => $allocation->id,
            'ticket_type_id' => $ticketType->id,
            'user_id' => $seller->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $unitPrice * $quantity,
            'buyer_name' => $buyer,
            'buyer_phone' => $phone,
        ]);

        $payments->generateTickets($reservation);
    }
}
