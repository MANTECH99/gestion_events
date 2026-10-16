<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Event;
use App\Models\EventImage;
use App\Models\EventReview;
use App\Models\Reservation;
use App\Models\TicketType;
use App\Models\User;
use App\Services\ReservationPaymentManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Données de démonstration de la page de détail d'événement :
 * artiste, galerie, avis, durée, tags, lieu illustré, organisateur vérifié,
 * événements de la maquette et ventes (« 15K+ », 4,8/5 sur 2 412 avis).
 *
 * Idempotent : relancer le seeder ne crée aucun doublon.
 */
class EventDetailDemoSeeder extends Seeder
{
    private const WALLY_SOLD_TOTAL = 15000;

    private const WALLY_REVIEW_COUNT = 2412;

    /** @var list<string> */
    private const REVIEW_NAMES = [
        'Mamadou Diao', 'Awa Ndiaye', 'Cheikh Sarr', 'Fatou Diop', 'Ibrahima Fall',
        'Aminata Ba', 'Moussa Kane', 'Khadija Sy', 'Ousmane Camara', 'Ndèye Gueye',
        'Pape Diallo', 'Rama Mbengue', 'Samba Thiam', 'Astou Ndour', 'Modou Bop',
        'Devi Sow', 'Alou Badji', 'Ndeye Coulibaly', 'Youssouph Ndiaye', 'Mame Diarra',
        'Toussaint Dieng', 'Sokhna Mbaye', 'El Hadji Ndoye', 'Bineta Samb', 'Doudou Cissé',
        'Khady Faye', 'Seydina Lima', 'Rokhaya Diagne', 'Mouhamed Sow', 'Sarra Diouf',
        'Lamine Sall', 'Mariama Jallow', 'Omar Seye', 'Aissatou Barry', 'Famara Diatta',
        'Niankoro Touré', 'Aminatou Keita', 'Djibril Diack', 'Sokhna Diouf', 'Boubacar Sy',
    ];

    /** @var list<string> */
    private const REVIEW_COMMENTS = [
        'Ambiance incroyable, je recommande à 100 % !',
        'Billets achetés en 2 minutes, entrée sans attente.',
        'Artiste au top, l\'organisation était parfaite.',
        'Très bonne expérience, on reviendra.',
        'Le lieu était facile d\'accès et la sécurité au rendez-vous.',
        'Un moment inoubliable avec les amis.',
        'Paiement Wave rapide, aucune galère.',
        'Sonorité et éclairage impeccables.',
        'Meilleur concert de la saison, sans discussion.',
        'Réservation simple, billet QR scanné en une seconde.',
        'Cadre magnifique, je recommande.',
        'Bon rapport qualité/prix pour le VIP.',
        'L\'artiste a donné le meilleur de lui-même.',
        'Tout était bien organisé, bravo à l\'équipe.',
        'Journée réussie, très bonne organisation.',
    ];

    public function run(): void
    {
        $payments = app(ReservationPaymentManager::class);

        /* ---------------------------------------------------------------- */
        /* Artiste + organisateur vérifié */
        /* ---------------------------------------------------------------- */

        $artist = Artist::updateOrCreate(
            ['name' => 'Wally Seck'],
            [
                'title' => 'Chanteur • Mbalax',
                'bio' => "Wally Seck est l'une des plus grandes voix du Sénégal. De « Bambino » à ses derniers titres, il fait danser le public du Teranga Festival au Stade Demba Diop, avec un répertoire qui mêle mbalax, afro-pop et classiques indémodables.",
                'image_path' => $this->cover('1507676184212-d03ab07a01bf'),
            ],
        );

        $arena = User::updateOrCreate(
            ['email' => 'arena@billeterieexpress.com'],
            [
                'first_name' => 'Arena',
                'last_name' => 'Events',
                'username' => 'arena.events',
                'role' => User::ROLE_ORGANIZER,
                'password' => 'password',
                'phone' => '+221770000001',
                'bio' => "Arena Events produit les plus grands concerts et festivals du Sénégal depuis 2018 : Wally Seck, Teranga Festival, Sunugal… Des soirées grand public, une billetterie 100 % sécurisée.",
                'is_verified' => true,
                'is_active' => true,
            ],
        );

        $mamadou = User::updateOrCreate(
            ['email' => 'mamadou.diao@exemple.sn'],
            [
                'first_name' => 'Mamadou',
                'last_name' => 'Diao',
                'username' => 'mamadou.diao',
                'role' => User::ROLE_CLIENT,
                'password' => 'password',
                'is_active' => true,
            ],
        );

        /* ---------------------------------------------------------------- */
        /* Événements de la maquette */
        /* ---------------------------------------------------------------- */

        $wally = $this->event($arena, [
            'title' => 'Wally Seck — Live à Dakar',
            'slug' => 'wally-seck-live-a-dakar',
            'description' => "Le phénomène mbalax revient sur la plus grande scène de Dakar pour une nuit unique : les plus grands tubes, un live à la hauteur et une ambiance à couper le souffle.\n\nOuverture des portes à 19h, concert à 20h30. VIP et places en tribune disponibles.",
            'event_date' => now()->addDays(12)->setTime(20, 30),
            'duration_minutes' => 150,
            'location' => 'Stade Demba Diop, Dakar',
            'total_tickets' => 25000,
            'event_type' => 'concert',
            'tags' => 'Mbalax,Musique live,Sénégal',
            'image_path' => $this->cover('1507676184212-d03ab07a01bf'),
            'venue_image_path' => $this->cover('1489944440615-453fc2b6a9a9'),
        ], $artist);

        $wallyTypes = [
            'Normal' => $this->ticketType($wally, ['name' => 'Normal', 'description' => 'Accès debout général', 'price' => 5000, 'quantity' => 12000]),
            'VIP' => $this->ticketType($wally, ['name' => 'VIP', 'description' => 'Zone dédiée + bar exclusif', 'price' => 15000, 'quantity' => 8000]),
            'VVIP' => $this->ticketType($wally, ['name' => 'VVIP', 'description' => 'Loges et meet & greet', 'price' => 30000, 'quantity' => 4000]),
        ];

        $this->gallery($wally, [
            '1493225457124-a3eb161ffa5f',
            '1506157786151-b8491531f063',
            '1492684223066-81342ee5ff30',
            '1429962714451-bb934ecdc4ec',
            '1470229722913-7c0e2dbbafd3',
        ]);

        $alioune = $this->event($arena, [
            'title' => 'Alioune Badara Cissé — Récital',
            'slug' => 'alioune-badara-cisse-recital',
            'description' => "Le poète et comédien Alioune Badara Cissé signe un récit musical puissant entre tradition orale et scène contemporaine.\n\nUne soirée intimiste au Théâtre national, en première et unique représentation à Dakar.",
            'event_date' => now()->addDays(40)->setTime(18, 30),
            'duration_minutes' => 110,
            'location' => 'Théâtre national Daniel Sorano, Dakar',
            'total_tickets' => 900,
            'event_type' => 'theatre',
            'tags' => 'Théâtre,Poésie,Sénégal',
            'image_path' => $this->cover('1540575467063-178a50c2df87'),
        ]);

        $this->ticketType($alioune, ['name' => 'Entrée', 'description' => 'Fauteuil salle', 'price' => 2500, 'quantity' => 700]);
        $this->ticketType($alioune, ['name' => 'VIP', 'description' => 'Premières loges', 'price' => 7500, 'quantity' => 200]);

        $sunugal = $this->event($arena, [
            'title' => 'Festival Sunugal',
            'slug' => 'festival-sunugal',
            'description' => "Deux jours de célébration sur le front de mer : danses, percussions, artisanat et grandes scènes nocturnes.\n\nSunugal, la fête où le Sénégal se raconte en musique.",
            'event_date' => now()->addDays(55)->setTime(16, 0),
            'duration_minutes' => 300,
            'location' => 'Plage de Ngor, Dakar',
            'total_tickets' => 3000,
            'event_type' => 'festival',
            'tags' => 'Festival,Culture,Plage',
            'image_path' => $this->cover('1507525428034-b723cf961d3e'),
        ]);

        $this->ticketType($sunugal, ['name' => 'Pass 1 jour', 'description' => 'Accès samedi ou dimanche', 'price' => 5000, 'quantity' => 1500]);
        $this->ticketType($sunugal, ['name' => 'Pass 2 jours', 'description' => 'Accès complet du festival', 'price' => 10000, 'quantity' => 600]);

        $forum = $this->event($arena, [
            'title' => 'Forum emploi & innovation',
            'slug' => 'forum-emploi-innovation',
            'description' => "40 entreprises, 600 participants et une journée entière de conférences, ateliers et rencontres.\n\nLe rendez-vous des jeunes diplômés et des startups sénégalaises.",
            'event_date' => now()->addDays(70)->setTime(9, 0),
            'duration_minutes' => 420,
            'location' => 'Dakar International Conference Center',
            'total_tickets' => 800,
            'event_type' => 'conference',
            'tags' => 'Conférence,Emploi,Innovation',
            'image_path' => $this->cover('1540575467063-178a50c2df87'),
        ]);

        $this->ticketType($forum, ['name' => 'Standard', 'description' => 'Accès conférences', 'price' => 3000, 'quantity' => 500]);
        $this->ticketType($forum, ['name' => 'Entrepreneur', 'description' => 'Accès + ateliers + déjeuner', 'price' => 7500, 'quantity' => 150]);

        /* ---------------------------------------------------------------- */
        /* Enrichissement des événements de démonstration existants */
        /* ---------------------------------------------------------------- */

        $this->enrich('asc-jaraaf-vs-casa-sport', [
            'duration_minutes' => 120,
            'tags' => 'Football,Ligue 1,Sénégal',
            'image_path' => $this->cover('1522778119026-d647f0596c20'),
            'venue_image_path' => $this->cover('1489944440615-453fc2b6a9a9'),
        ], [
            '1492684223066-81342ee5ff30',
            '1470229722913-7c0e2dbbafd3',
            '1429962714451-bb934ecdc4ec',
        ]);

        $this->enrich('festival-teranga-2026', [
            'duration_minutes' => 240,
            'tags' => 'Festival,Musique,Diversité',
            'image_path' => $this->cover('1514525253161-7a46d19cd819'),
            'venue_image_path' => $this->cover('1522778119026-d647f0596c20'),
        ]);

        $this->enrich('ceremonie-kwelif', [
            'duration_minutes' => 90,
            'tags' => 'Poésie,Scène',
            'image_path' => $this->cover('1503095396549-807759245b35'),
        ]);

        /* ---------------------------------------------------------------- */
        /* Ventes « 15K+ » sur l'événement phare */
        /* ---------------------------------------------------------------- */

        $this->wallySales($payments, $wally, $wallyTypes);

        /* ---------------------------------------------------------------- */
        /* Avis */
        /* ---------------------------------------------------------------- */

        $this->wallyReviews($mamadou);
        $this->sampleReviews('asc-jaraaf-vs-casa-sport', [5, 5, 4, 5, 4]);
        $this->sampleReviews('festival-teranga-2026', [5, 4, 5, 4]);
        $this->sampleReviews('ceremonie-kwelif', [5, 5, 4]);

        $this->command?->info('Détail événements : artiste, galeries, avis et ventes de démonstration ajoutés.');
    }

    /* ------------------------------------------------------------------ */

    private function cover(string $id): string
    {
        return 'https://images.unsplash.com/photo-'.$id.'?auto=format&fit=crop&w=1400&q=70';
    }

    private function event(User $organizer, array $attributes, ?Artist $artist = null): Event
    {
        return Event::updateOrCreate(
            ['slug' => $attributes['slug']],
            [
                ...$attributes,
                'organizer_id' => $organizer->id,
                'artist_id' => $artist?->id,
                'organizer_display_name' => 'Arena Events',
                'status' => Event::STATUS_PUBLISHED,
                'online_sales_enabled' => true,
            ],
        );
    }

    private function enrich(string $slug, array $attributes, array $gallery = []): void
    {
        $event = Event::where('slug', $slug)->first();

        if (! $event) {
            return;
        }

        $event->fill($attributes)->save();
        $this->gallery($event, $gallery);
    }

    private function ticketType(Event $event, array $attributes): TicketType
    {
        return TicketType::updateOrCreate(
            ['event_id' => $event->id, 'name' => $attributes['name']],
            [...$attributes, 'is_active' => true],
        );
    }

    /** @param list<string> $ids */
    private function gallery(Event $event, array $ids): void
    {
        if ($ids === [] || $event->images()->exists()) {
            return;
        }

        foreach ($ids as $position => $id) {
            EventImage::create([
                'event_id' => $event->id,
                'image_path' => $this->cover($id),
                'position' => $position + 1,
            ]);
        }
    }

    /**
     * 15 000 billets vendus (8 000 Normal + 5 000 VIP + 2 000 VVIP),
     * créés par lots de 1 000 comme dans DemoSeeder.
     *
     * @param array<string, TicketType> $types
     */
    private function wallySales(ReservationPaymentManager $payments, Event $event, array $types): void
    {
        if (TicketType::where('event_id', $event->id)->sum('sold_online') >= self::WALLY_SOLD_TOTAL) {
            return;
        }

        $plan = ['Normal' => 8000, 'VIP' => 5000, 'VVIP' => 2000];

        foreach ($plan as $name => $target) {
            $type = $types[$name] ?? null;

            if (! $type) {
                continue;
            }

            $remaining = $target - (int) $type->sold_online;
            $lot = 1;

            while ($remaining > 0) {
                $quantity = min(1000, $remaining);
                $when = $this->randomPast(2, 45);

                $reservation = Reservation::create([
                    'reference' => Reservation::makeReference(),
                    'event_id' => $event->id,
                    'ticket_type_id' => $type->id,
                    'user_id' => null,
                    'customer_name' => 'Vente en ligne — lot '.$lot,
                    'customer_email' => 'ventes.'.strtolower($name).'.'.$lot.'@exemple.sn',
                    'customer_phone' => '+22177'.random_int(1000000, 9999999),
                    'quantity' => $quantity,
                    'unit_price' => $type->price,
                    'total_amount' => $type->price * $quantity,
                    'payment_status' => Reservation::STATUS_PAID,
                    'channel' => Reservation::CHANNEL_ONLINE,
                    'payment_operator' => random_int(0, 1) ? 'wave' : 'orange_money',
                    'paid_at' => $when,
                ]);

                $reservation->forceFill(['created_at' => $when, 'updated_at' => $when])->saveQuietly();

                $type->increment('sold_online', $quantity);
                $payments->generateTickets($reservation);

                $remaining -= $quantity;
                $lot++;
            }
        }
    }

    /** 2 412 avis (4,8/5) : 2 000 · 289 · 97 · 13 · 13. */
    private function wallyReviews(User $mamadou): void
    {
        $event = Event::where('slug', 'wally-seck-live-a-dakar')->first();

        if (! $event || $event->reviews()->count() >= self::WALLY_REVIEW_COUNT) {
            return;
        }

        $event->reviews()->delete();

        $featured = [
            'event_id' => $event->id,
            'user_id' => $mamadou->id,
            'author_name' => 'Mamadou Diao',
            'rating' => 5,
            'comment' => "Une soirée inoubliable, l'ambiance était incroyable du début à la fin. Billets achetés en deux minutes sur Billeterie Express.",
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ];

        // 1 avis vedette (5★) + 1 999 · 289 · 97 · 13 · 13 = 2 412.
        $plan = [5 => 1999, 4 => 289, 3 => 97, 2 => 13, 1 => 13];
        $rows = [$featured];

        foreach ($plan as $rating => $count) {
            for ($i = 0; $i < $count; $i++) {
                $when = $this->randomPast(10, 240);
                $hasComment = $rating >= 4 && $i % 3 === 0;

                $rows[] = [
                    'event_id' => $event->id,
                    'user_id' => null,
                    'author_name' => self::REVIEW_NAMES[array_rand(self::REVIEW_NAMES)],
                    'rating' => $rating,
                    'comment' => $hasComment ? self::REVIEW_COMMENTS[array_rand(self::REVIEW_COMMENTS)] : null,
                    'created_at' => $when,
                    'updated_at' => $when,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            EventReview::insert($chunk);
        }
    }

    /** @param list<int> $ratings */
    private function sampleReviews(string $slug, array $ratings): void
    {
        $event = Event::where('slug', $slug)->first();

        if (! $event || $event->reviews()->exists()) {
            return;
        }

        foreach ($ratings as $index => $rating) {
            $when = $this->randomPast(3, 60);

            EventReview::create([
                'event_id' => $event->id,
                'user_id' => null,
                'author_name' => self::REVIEW_NAMES[($index + 7) % count(self::REVIEW_NAMES)],
                'rating' => $rating,
                'comment' => self::REVIEW_COMMENTS[($index * 3) % count(self::REVIEW_COMMENTS)],
            ])->forceFill(['created_at' => $when, 'updated_at' => $when])->saveQuietly();
        }
    }

    private function randomPast(int $minDaysAgo, int $maxDaysAgo): Carbon
    {
        return Carbon::createFromTimestamp(
            random_int(now()->subDays($maxDaysAgo)->timestamp, now()->subDays($minDaysAgo)->timestamp),
        );
    }
}
