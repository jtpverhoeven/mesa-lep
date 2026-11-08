<?php

namespace Database\Factories;

use App\Models\SampleFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SampleFile>
 */
class SampleFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sample_id' => fake()->numberBetween(1, 1000),
            'hash_name' => (string) Str::uuid(),
            'original_name' => fake()->word().'.pdf',
            'visible_for_client' => false,
            'client_id' => fake()->numberBetween(1, 100),
            'sent_to_client' => false,
        ];
    }
}
