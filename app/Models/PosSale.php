<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_allocation_id', 'ticket_type_id', 'user_id', 'quantity',
    'unit_price', 'total_amount', 'buyer_name', 'buyer_phone', 'buyer_email', 'note',
])]
class PosSale extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public function stockAllocation(): BelongsTo
    {
        return $this->belongsTo(StockAllocation::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
