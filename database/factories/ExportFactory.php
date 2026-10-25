<?php

namespace Database\Factories;

use App\Models\Export;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Export>
 */
class ExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client' => 1,
            'project' => 1,
            'revision' => 1,
            'print_version' => 1,
            'type' => 1,
            'template' => 'maz2020',
            'hash' => hash('sha256', fake()->uuid()),
            'date' => (string) now()->timestamp,
            'temporary' => 0,
            'was_authorized' => 1,
        ];
    }
}
