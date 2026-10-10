<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'source', 'event', 'reference', 'event_id_header', 'signature_valid',
    'processed', 'payload', 'error',
])]
class WebhookLog extends Model
{
    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'processed' => 'boolean',
        ];
    }
}
