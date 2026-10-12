<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'reference', 'event_id', 'ticket_type_id', 'user_id', 'customer_name',
    'customer_email', 'customer_phone', 'quantity', 'unit_price', 'total_amount',
    'payment_status', 'channel', 'sold_by', 'paid_at', 'payment_operator',
])]
class Reservation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_EXPIRED = 'expired';

    public const CHANNEL_ONLINE = 'online';

    public const CHANNEL_PHYSICAL = 'physical';

    /** Libellés des statuts de paiement. */
    public const STATUSES = [
        self::STATUS_PENDING => 'En attente de paiement',
        self::STATUS_PAID => 'Payé',
        self::STATUS_FAILED => 'Échec',
        self::STATUS_CANCELLED => 'Annulée',
        self::STATUS_REFUNDED => 'Remboursée',
        self::STATUS_EXPIRED => 'Expirée',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'total_amount' => 'integer',
            'paid_at' => 'datetime',
            'whatsapp_sent_at' => 'datetime',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Relations */
    /* ------------------------------------------------------------------ */

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function payment(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany('id');
    }

    /* ------------------------------------------------------------------ */
    /* Scopes */
    /* ------------------------------------------------------------------ */

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', self::STATUS_PAID);
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    /* ------------------------------------------------------------------ */
    /* Attributs */
    /* ------------------------------------------------------------------ */

    public static function makeReference(): string
    {
        do {
            $reference = 'BEX-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            self::STATUS_PAID => 'Payée',
            self::STATUS_PENDING => 'En attente de paiement',
            self::STATUS_FAILED => 'Échec',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_REFUNDED => 'Remboursée',
            self::STATUS_EXPIRED => 'Expirée',
            default => ucfirst((string) $this->payment_status),
        };
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::STATUS_PAID;
    }

    /** Le billet a-t-il déjà été envoyé automatiquement par WhatsApp ? */
    public function hasBeenSentToWhatsApp(): bool
    {
        return $this->whatsapp_sent_at !== null;
    }

    public function isPending(): bool
    {
        return $this->payment_status === self::STATUS_PENDING;
    }

    public function isRefunded(): bool
    {
        return $this->payment_status === self::STATUS_REFUNDED;
    }

    /** Vente déclarée par un point de vente (espèces). */
    public function isPhysical(): bool
    {
        return $this->channel === self::CHANNEL_PHYSICAL;
    }

    public function isOnline(): bool
    {
        return $this->channel === self::CHANNEL_ONLINE;
    }

    public function getChannelLabelAttribute(): string
    {
        return $this->isPhysical() ? 'Physique' : 'En ligne';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            self::STATUS_PAID => 'emerald',
            self::STATUS_PENDING => 'amber',
            self::STATUS_REFUNDED => 'sky',
            default => 'rose',
        };
    }
}
