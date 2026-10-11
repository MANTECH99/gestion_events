<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** Prénom / nom sénégalais courants. */
    private const FIRST_NAMES = [
        'Aminata', 'Fatou', 'Moussa', 'Awa', 'Ibrahima', 'Khadija', 'Ousmane',
        'Mariama', 'Cheikh', 'Ndeye', 'Alioune', 'Rokhaya', 'Babacar', 'Sokhna',
    ];

    private const LAST_NAMES = [
        'Diop', 'Seck', 'Sarr', 'Fall', 'Gueye', 'Mbaye', 'Diallo', 'Sow',
        'Ndoye', 'Ba', 'Camara', 'Diouf', 'Ndiaye', 'Sy',
    ];

    public function definition(): array
    {
        $first = fake()->randomElement(self::FIRST_NAMES);
        $last = fake()->randomElement(self::LAST_NAMES);

        return [
            'first_name' => $first,
            'last_name' => $last,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => '+22177'.fake()->numerify('#######'),
            'role' => User::ROLE_CLIENT,
            'is_active' => true,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN, 'organizer_id' => null]);
    }

    public function organizer(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ORGANIZER]);
    }

    public function agent(?User $organizer = null): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_AGENT,
            'organizer_id' => $organizer?->id,
        ]);
    }

    public function pointOfSale(?User $organizer = null): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_USER,
            'organizer_id' => $organizer?->id,
            'point_of_sale' => fake()->randomElement(['Boutique Centre-ville', 'Kiosque Fass', 'Guichet Stade']),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
