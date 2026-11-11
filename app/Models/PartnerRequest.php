<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Demande envoyée depuis le formulaire « Rejoignez notre réseau de
 * partenaires » (page /devenir-partenaire). Aucun mail n'est envoyé :
 * l'administrateur traite la demande depuis son back-office.
 */
class PartnerRequest extends Model
{
    public const STATUS_NOUVEAU = 'nouveau';
    public const STATUS_TRAITE = 'traite';

    /** @varlist<string> */
    protected $fillable = [
        'nom',
        'email',
        'structure',
        'type_evenement',
        'message',
        'statut',
        'traite_at',
    ];

    protected function casts(): array
    {
        return [
            'traite_at' => 'datetime',
        ];
    }

    public function isTraitee(): bool
    {
        return $this->statut === self::STATUS_TRAITE;
    }
}
