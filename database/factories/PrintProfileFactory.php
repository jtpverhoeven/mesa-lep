<?php

namespace Database\Factories;

use App\Models\PrintProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrintProfile>
 */
class PrintProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->bothify('Profiel-####'),
            'profile' => '10 EXIT',
        ];
    }
}
