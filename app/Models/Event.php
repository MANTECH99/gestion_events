<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'organizer_id', 'title', 'slug', 'description', 'event_date', 'location',
    'total_tickets', 'event_type', 'image_path', 'status', 'online_sales_enabled',
    'organizer_display_name',
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

    public function isOnSale(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->online_sales_enabled
            && ! $this->isSoldOut()
            && $this->event_date->isFuture();
    }
}
