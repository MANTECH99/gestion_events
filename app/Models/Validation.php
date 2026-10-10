<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'event_id', 'agent_id', 'ticket_code', 'status', 'message', 'scanned_code'])]
class Validation extends Model
{
    use HasFactory;

    public const STATUS_VALID = 'valid';

    public const STATUS_INVALID = 'invalid';

    public const MSG_WELCOME = 'Entrée autorisée - Bienvenue !';

    public const MSG_ALREADY_USED = 'Ce ticket a déjà été utilisé.';

    public const MSG_UNKNOWN = 'Ticket introuvable - code inconnu.';

    public const MSG_EXPIRED = 'Ce ticket a été remboursé ou annulé.';

    public const MSG_EVENT_MISMATCH = 'Ce ticket ne correspond pas à l\'événement contrôlé.';

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }
}
