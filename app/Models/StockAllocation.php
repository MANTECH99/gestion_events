<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ticket_type_id', 'user_id', 'allocated', 'sold'])]
class StockAllocation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'allocated' => 'integer',
            'sold' => 'integer',
        ];
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->allocated - $this->sold);
    }
}
