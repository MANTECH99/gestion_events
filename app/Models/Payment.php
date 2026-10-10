<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reservation_id', 'provider', 'reference', 'session_id', 'payment_url',
    'status', 'currency', 'amount', 'operator', 'transaction_id',
    'completed_at', 'payload',
])]
class Payment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'completed_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
