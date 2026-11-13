<?php

namespace Database\Factories;

use App\Models\Worklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Worklist>
 */
class WorklistFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'analyses' => [],
            'no_columns' => 1,
            'title' => fake()->unique()->words(2, true),
            'subtitle' => null,
            'footer_text' => null,
            'columns' => [],
            'colum_groups' => [],
            'extra_rows' => [],
            'extra_pages' => null,
            'orientation' => 'P',
        ];
    }
}
