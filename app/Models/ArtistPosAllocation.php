<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Billets réservés pour un point de vente d'artiste, par type de billet.
 *
 * L'assignation retire ces billets du stock en ligne public (comme le stock
 * physique attribué aux points de vente via StockAllocation) : l'artiste est
 * sûr de pouvoir vendre le nombre de billets qui lui a été promis.
 */
#[Fillable(['artist_pos_id', 'ticket_type_id', 'allocated', 'sold'])]
class ArtistPosAllocation extends Model
{
    protected $table = 'artist_pos_allocations';

    protected function casts(): array
    {
        return [
            'allocated' => 'integer',
            'sold' => 'integer',
        ];
    }

    public function artistPos(): BelongsTo
    {
        return $this->belongsTo(ArtistPointOfSale::class, 'artist_pos_id');
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /** Billets assignés encore non vendus. */
    public function getRemainingAttribute(): int
    {
        return max(0, (int) $this->allocated - (int) $this->sold);
    }
}
