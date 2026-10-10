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
    'code', 'reservation_id', 'event_id', 'ticket_type_id', 'holder_name',
    'holder_email', 'price', 'status', 'used_at', 'validated_by',
])]
class Ticket extends Model
{
    use HasFactory;

    public const STATUS_VALID = 'valid';

    public const STATUS_USED = 'used';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'used_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(Validation::class);
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VALID);
    }

    public function scopeUsed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_USED);
    }

    /** Contenu encodé dans le QR code. */
    public function qrPayload(): string
    {
        return $this->code;
    }

    public static function makeCode(): string
    {
        do {
            $code = 'BXT-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function isUsable(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_VALID => 'Valide',
            self::STATUS_USED => 'Utilisé',
            self::STATUS_REFUNDED => 'Remboursé',
            self::STATUS_CANCELLED => 'Annulé',
            default => ucfirst((string) $this->status),
        };
    }

    /** Couleur du badge de statut (palettes déclarées dans app.css). */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_VALID => 'emerald',
            self::STATUS_USED => 'sky',
            self::STATUS_REFUNDED, self::STATUS_CANCELLED => 'rose',
            default => 'amber',
        };
    }
}
