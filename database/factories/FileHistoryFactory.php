<?php

namespace Database\Factories;

use App\Models\FileHistory;
use App\Models\SampleFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileHistory>
 */
class FileHistoryFactory extends Factory
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
            'samplefile_id' => SampleFile::factory(),
            'hash_name' => fake()->uuid(),
            'sent_by' => fake()->numberBetween(1, 100),
            'sent_to' => ['groups' => fake()->words(2), 'extra' => fake()->safeEmail()],
            'sent_method' => fake()->randomElement(['report', 'filesender']),
            'transaction_id' => null,
        ];
    }
}
