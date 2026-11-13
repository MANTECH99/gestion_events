<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Point de vente en ligne d'un artiste : un lien partageable vers la page
 * d'un événement, avec un quota de billets RÉSERVÉS par type de billet —
 * l'assignation réduit le stock en ligne public, comme le stock physique.
 */
#[Fillable(['artist_id', 'event_id', 'token', 'is_active'])]
class ArtistPointOfSale extends Model
{
    /** Pluriel irrégulier : le modèle attend artist_point_of_sales. */
    protected $table = 'artist_points_of_sale';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Relations */
    /* ------------------------------------------------------------------ */

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'artist_pos_id');
    }

    /** Billets réservés à ce lien, type par type. */
    public function allocations(): HasMany
    {
        return $this->hasMany(ArtistPosAllocation::class, 'artist_pos_id');
    }

    /* ------------------------------------------------------------------ */
    /* Statistiques (réservations payées uniquement) */
    /* ------------------------------------------------------------------ */

    /** Billets vendus via ce lien (réservations payées). */
    public function soldQuantity(): int
    {
        return (int) $this->reservations()->paid()->sum('quantity');
    }

    /** Montant total encaissé via ce lien (réservations payées). */
    public function revenue(): int
    {
        return (int) $this->reservations()->paid()->sum('total_amount');
    }

    /** Billets assignés au lien (tous types confondus). */
    public function totalAllocated(): int
    {
        return (int) $this->allocations->sum('allocated');
    }

    /** Billets restants pour un type de billet (0 = type non assigné). */
    public function remainingFor(int $ticketTypeId): int
    {
        return $this->allocations->firstWhere('ticket_type_id', $ticketTypeId)?->remaining ?? 0;
    }

    /** Billets restants dans le quota assigné (tous types). */
    public function remaining(): int
    {
        return (int) $this->allocations->sum(fn (ArtistPosAllocation $a) => $a->remaining);
    }

    /** Le quota est-il épuisé ? Le lien affiche alors « stock épuisé ». */
    public function isExhausted(): bool
    {
        return $this->remaining() <= 0;
    }

    /* ------------------------------------------------------------------ */
    /* Lien de partage */
    /* ------------------------------------------------------------------ */

    /** URL publique de l'événement portant le jeton de traçage. */
    public function url(): string
    {
        return route('events.show', $this->event).'?pov='.$this->token;
    }

    public static function makeToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('token', $token)->exists());

        return $token;
    }
}
