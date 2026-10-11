<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'first_name', 'last_name', 'username', 'email', 'password',
    'role', 'organizer_id', 'phone', 'point_of_sale', 'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_ORGANIZER = 'organizer';

    public const ROLE_AGENT = 'agent';

    public const ROLE_USER = 'user';

    public const ROLE_CLIENT = 'client';

    /** Libellés des rôles. */
    public const ROLES = [
        self::ROLE_ADMIN => 'Administrateur',
        self::ROLE_ORGANIZER => 'Organisateur',
        self::ROLE_AGENT => 'Agent',
        self::ROLE_USER => 'Point de vente',
        self::ROLE_CLIENT => 'Client',
    ];

    /** Rôles autorisés à être désignés comme agent de contrôle. */
    public const AGENT_ROLES = [self::ROLE_ADMIN, self::ROLE_ORGANIZER, self::ROLE_AGENT];

    /**
     * Valeurs par défaut alignées sur la migration.
     *
     * Sans cela, un modèle créé en mémoire (factory, inscription, création
     * par un organisateur) exposerait `is_active === null`, ce que les
     * contrôles d'accès liraient comme un compte désactivé.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => self::ROLE_CLIENT,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Relations */
    /* ------------------------------------------------------------------ */

    /** L'organisateur référent, si l'utilisateur est agent ou point de vente. */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'organizer_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    /** Points de vente rattachés à cet organisateur. */
    public function pointsOfSale(): HasMany
    {
        return $this->hasMany(self::class, 'organizer_id')->where('role', self::ROLE_USER);
    }

    /** Agents de contrôle rattachés à cet organisateur. */
    public function agents(): HasMany
    {
        return $this->hasMany(self::class, 'organizer_id')->where('role', self::ROLE_AGENT);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }

    /** Réservations des événements dont cet utilisateur est l'organisateur. */
    public function eventReservations(): HasManyThrough
    {
        return $this->hasManyThrough(
            Reservation::class,
            Event::class,
            'organizer_id',
            'event_id',
            'id',
            'id'
        );
    }

    public function validations(): HasMany
    {
        return $this->hasMany(Validation::class, 'agent_id');
    }

    public function stockAllocations(): HasMany
    {
        return $this->hasMany(StockAllocation::class, 'user_id');
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'user_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'organizer_id');
    }

    /* ------------------------------------------------------------------ */
    /* Rôles */
    /* ------------------------------------------------------------------ */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOrganizer(): bool
    {
        return $this->role === self::ROLE_ORGANIZER;
    }

    public function isAgent(): bool
    {
        return in_array($this->role, self::AGENT_ROLES, true);
    }

    /** Un point de vente vend des tickets physiques. */
    public function isPointOfSale(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /** Un point de vente rattaché à un organisateur, avec un stock physique à vendre. */
    public function isManagedPointOfSale(): bool
    {
        return $this->isPointOfSale() && $this->organizer_id !== null;
    }

    /** Un client, venu réserver ses billets (sans activité de vente). */
    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /* ------------------------------------------------------------------ */
    /* Attributs */
    /* ------------------------------------------------------------------ */

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr((string) $this->first_name, 0, 1).mb_substr((string) $this->last_name, 0, 1));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('username', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }
}
