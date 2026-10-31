<?php

namespace Database\Factories;

use App\Keyrings\KeyringType;
use App\Models\Keyring;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Keyring>
 */
class KeyringFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => KeyringType::Sample,
            'type_id' => fake()->unique()->numberBetween(1, 1_000_000),
            'issued' => now()->timestamp,
            'issued_to' => User::factory(),
            'expires' => Keyring::nextExpiry(),
        ];
    }

    public function forResource(KeyringType $type, int $typeId): static
    {
        return $this->state(fn (): array => ['type' => $type, 'type_id' => $typeId]);
    }

    public function heldBy(User $user): static
    {
        return $this->state(fn (): array => ['issued_to' => $user->id]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires' => now()->timestamp - 60]);
    }
}
