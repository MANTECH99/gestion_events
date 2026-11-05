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

    /**
     * Statut canonique du payout.
     *
     * Le webhook DexPay écrit « COMPLETED » (majuscules) mais l'API REST
     * renvoie « completed » (minuscules, parfois « success ») : sans
     * normalisation, la synchronisation écrasait un statut final et le label
     * retombait dans le cas défaut — « En attente » — alors que le retrait
     * était bien effectué.
     */
    public static function normalizeStatus(mixed $status): string
    {
        return match (strtoupper(trim((string) $status))) {
            'COMPLETED', 'SUCCESS', 'PAID' => 'COMPLETED',
            'FAILED', 'FAILURE' => 'FAILED',
            'CANCELLED', 'CANCELED' => 'CANCELLED',
            'PROCESSING' => 'PROCESSING',
            'PENDING', 'INITIATED', '' => 'PENDING',
            default => strtoupper(trim((string) $status)),
        };
    }

    /**
     * Passe toutes les écritures (store, sync, webhook) par la normalisation.
     *
     * Retourne `$this` : HasAttributes::setAttribute() propage la valeur de
     * retour du mutateur, et les appels enchaînés type
     * `(new Payout)->setAttribute('status', …)->status_color` en dépendent.
     */
    protected function setStatusAttribute(mixed $value): static
    {
        $this->attributes['status'] = static::normalizeStatus($value);

        return $this;
    }

    public function getStatusLabelAttribute(): string
    {
        return match (strtoupper((string) $this->status)) {
            'COMPLETED' => 'Effectué',
            'FAILED' => 'Échec',
            'CANCELLED' => 'Annulé',
            'PROCESSING' => 'En cours',
            default => 'En attente',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match (strtoupper((string) $this->status)) {
            'COMPLETED' => 'emerald',
            'FAILED', 'CANCELLED' => 'rose',
            'PROCESSING' => 'sky',
            default => 'amber',
        };
    }
}
