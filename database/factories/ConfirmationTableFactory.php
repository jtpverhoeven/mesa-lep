<?php

namespace Database\Factories;

use App\Models\ConfirmationTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConfirmationTable>
 */
class ConfirmationTableFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'html' => '<table><tbody><tr><td>'.fake()->word().'</td><td>+</td></tr></tbody></table>',
        ];
    }
}
