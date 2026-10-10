<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organizer_id', 'reference', 'dexpay_id', 'amount', 'currency', 'fees',
    'destination_phone', 'recipient_name', 'operator', 'country_iso', 'status',
    'provider_name', 'provider_tx_id', 'failure_reason', 'completed_at',
])]
class Payout extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'fees' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'COMPLETED' => 'Effectué',
            'FAILED' => 'Échec',
            'CANCELLED' => 'Annulé',
            'PROCESSING' => 'En cours',
            default => 'En attente',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'COMPLETED' => 'emerald',
            'FAILED', 'CANCELLED' => 'rose',
            'PROCESSING' => 'sky',
            default => 'amber',
        };
    }
}
