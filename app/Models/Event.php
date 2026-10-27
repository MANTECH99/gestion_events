<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'organizer_id', 'artist_id', 'title', 'slug', 'description', 'event_date', 'duration_minutes',
    'location', 'total_tickets', 'event_type', 'tags', 'image_path', 'venue_image_path',
    'ticket_header_image_path', 'status', 'online_sales_enabled', 'organizer_display_name',
])]
class Event extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const TYPES = [
        'concert' => 'Concert',
        'festival' => 'Festival',
        'sport' => 'Sport',
        'theatre' => 'Théâtre',
        'conference' => 'Conférence',
        'evening' => 'Soirée',
        'other' => 'Autre',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'duration_minutes' => 'integer',
            'online_sales_enabled' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* ------------------------------------------------------------------ */
    /* Relations */
    /* ------------------------------------------------------------------ */

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(EventImage::class)->orderBy('position');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(EventReview::class)->latest();
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class)->orderBy('price');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function validations(): HasMany
    {
        return $this->hasMany(Validation::class);
    }

    /* ------------------------------------------------------------------ */
    /* Scopes */
    /* ------------------------------------------------------------------ */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('event_date', '>=', now()->startOfDay());
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%");
        });
    }

    /* ------------------------------------------------------------------ */
    /* Attributs */
    /* ------------------------------------------------------------------ */

    public static function slugFor(string $title): string
    {
        $slug = Str::slug($title);

        return $slug !== '' ? $slug : 'evenement';
    }

    public function getOrganizerDisplayNameAttribute(): string
    {
        return $this->attributes['organizer_display_name'] ?? null
            ?: ($this->organizer?->full_name ?? 'Billeterie Express');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->event_type] ?? ucfirst((string) $this->event_type);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'Publié',
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_COMPLETED => 'Terminé',
            default => ucfirst((string) $this->status),
        };
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http')) {
            // Les chemins uploads avant le passage en HTTPS ont memorise un
            // APP_URL local (http://localhost:8000/...) : le renvoyer tel quel
            // serait bloque en mixed content. On ne garde que le chemin.
            $host = parse_url($this->image_path, PHP_URL_HOST);
            $path = parse_url($this->image_path, PHP_URL_PATH);

            if ($host === null || in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'], true)) {
                return $path !== null ? $path : $this->image_path;
            }

            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    /** Illustration du lieu : URL externe ou fichier local. */
    public function getVenueImageUrlAttribute(): ?string
    {
        if (! $this->venue_image_path) {
            return null;
        }

        if (str_starts_with($this->venue_image_path, 'http')) {
            return $this->venue_image_path;
        }

        return Storage::disk('public')->url($this->venue_image_path);
    }

    /** Image d'en-tête du billet : URL externe ou fichier local. */
    public function getTicketHeaderImageUrlAttribute(): ?string
    {
        if (! $this->ticket_header_image_path) {
            return null;
        }

        if (str_starts_with($this->ticket_header_image_path, 'http')) {
            return $this->ticket_header_image_path;
        }

        return Storage::disk('public')->url($this->ticket_header_image_path);
    }

    public function getTicketsSoldAttribute(): int
    {
        return (int) $this->tickets()->whereIn('status', ['valid', 'used'])->count();
    }

    public function getRevenueAttribute(): int
    {
        return (int) $this->reservations()
            ->where('payment_status', 'paid')
            ->sum('total_amount');
    }

    public function getMinPriceAttribute(): ?int
    {
        $price = $this->ticketTypes()->where('is_active', true)->min('price');

        return $price === null ? null : (int) $price;
    }

    public function isSoldOut(): bool
    {
        // Une capacité nulle signifie « pas de limite déclarée ».
        return $this->total_tickets > 0 && $this->tickets_sold >= $this->total_tickets;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /** Billets restants en vente en ligne (types actifs, stock illimité ignoré). */
    public function getAvailableTicketsAttribute(): int
    {
        return (int) $this->ticketTypes()
            ->where('is_active', true)
            ->whereNotNull('quantity')
            ->get(['id', 'quantity', 'sold_online'])
            ->sum(fn (TicketType $type) => max(0, $type->quantity - $type->physical_allocated - $type->sold_online));
    }

    public function getMinPriceLabelAttribute(): string
    {
        return $this->min_price === null ? '—' : money($this->min_price);
    }

    public function getAvgRatingAttribute(): ?float
    {
        $avg = $this->reviews()->reorder()->avg('rating');

        return $avg === null ? null : round((float) $avg, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->reorder()->count();
    }

    /** Répartition des notes (5 → 1) en pourcentages entiers. */
    public function getRatingDistributionAttribute(): array
    {
        $counts = $this->reviews()
            ->reorder()
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $total = (int) $counts->sum();

        if ($total === 0) {
            return [];
        }

        $distribution = [];

        for ($stars = 5; $stars >= 1; $stars--) {
            $distribution[$stars] = (int) round(((int) ($counts[$stars] ?? 0)) / $total * 100);
        }

        return $distribution;
    }

    public function getDurationLabelAttribute(): ?string
    {
        if (! $this->duration_minutes) {
            return null;
        }

        $hours = intdiv((int) $this->duration_minutes, 60);
        $minutes = (int) $this->duration_minutes % 60;

        if ($minutes === 0) {
            return $hours.'h';
        }

        return sprintf('%dh %02d', $hours, $minutes);
    }

    /** Liste nettoyée des tags séparés par des virgules. */
    public function getTagsListAttribute(): array
    {
        if (! $this->tags) {
            return [];
        }

        return collect(explode(',', $this->tags))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
    }

    /** « 15K+ », « 2.4K+ », « 850 » — pour la barre de statistiques. */
    public function getTicketsSoldShortAttribute(): string
    {
        $sold = $this->tickets_sold;

        if ($sold < 1000) {
            return (string) $sold;
        }

        if ($sold < 10000) {
            return rtrim(rtrim(number_format($sold / 1000, 1, ',', ''), '0'), ',').'K+';
        }

        return round($sold / 1000).'K+';
    }

    /** Événements similaires : même type d'abord, puis complément parmi les à venir. */
    public function similarEvents(int $limit = 3): Collection
    {
        $same = static::query()
            ->published()
            ->upcoming()
            ->where('event_type', $this->event_type)
            ->whereKeyNot($this->getKey())
            ->orderBy('event_date')
            ->limit($limit)
            ->get();

        if ($same->count() < $limit) {
            $others = static::query()
                ->published()
                ->upcoming()
                ->whereKeyNot($this->getKey())
                ->whereNotIn('id', $same->pluck('id'))
                ->orderBy('event_date')
                ->limit($limit - $same->count())
                ->get();

            $same = $same->concat($others);
        }

        return $same->take($limit)->values();
    }

    public function isOnSale(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->online_sales_enabled
            && ! $this->isSoldOut()
            && $this->event_date->isFuture();
    }
}
