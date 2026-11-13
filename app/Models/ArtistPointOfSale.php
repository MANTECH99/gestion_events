<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Point de vente en ligne d'un artiste : un lien partageable vers la page
 * d'un événement, avec un quota de billets vendables via ce lien.
 */
#[Fillable(['artist_id', 'event_id', 'token', 'ticket_limit'])]
class ArtistPointOfSale extends Model
{
    /** Pluriel irrégulier : le modèle attend artist_point_of_sales. */
    protected $table = 'artist_points_of_sale';

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

    /** Billets restants dans le quota assigné. */
    public function remaining(): int
    {
        return max(0, (int) $this->ticket_limit - $this->soldQuantity());
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
