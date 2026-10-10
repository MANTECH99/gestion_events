<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['event_id', 'name', 'description', 'price', 'quantity', 'sold_online', 'sold_physical', 'is_active'])]
class TicketType extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity' => 'integer',
            'sold_online' => 'integer',
            'sold_physical' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function stockAllocations(): HasMany
    {
        return $this->hasMany(StockAllocation::class);
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    /** Stock physique : somme des quantités attribuées aux points de vente. */
    public function getPhysicalAllocatedAttribute(): int
    {
        return (int) $this->stockAllocations()->sum('allocated');
    }

    public function getPhysicalSoldAttribute(): int
    {
        return (int) $this->posSales()->sum('quantity');
    }

    public function getPhysicalRemainingAttribute(): int
    {
        return max(0, $this->physical_allocated - $this->physical_sold);
    }

    public function getSoldTotalAttribute(): int
    {
        return $this->sold_online + $this->sold_physical;
    }

    /**
     * Stock dédié à la vente en ligne : stock total (quantity) moins le stock
     * physique déjà attribué. L'attribution de stock physique réduit donc le
     * stock en ligne.
     */
    public function getOnlineQuantityAttribute(): int
    {
        if ($this->quantity === null) {
            return 0;
        }

        return max(0, $this->quantity - $this->physical_allocated);
    }

    public function getAvailableOnlineAttribute(): ?int
    {
        if ($this->quantity === null) {
            return null;
        }

        return max(0, $this->quantity - $this->physical_allocated - $this->sold_online);
    }

    public function isSoldOut(): bool
    {
        return $this->available_online === 0;
    }
}
