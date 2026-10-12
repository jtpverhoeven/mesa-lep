<?php

namespace Database\Factories;

use App\Models\LabelDesign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabelDesign>
 */
class LabelDesignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->bothify('Label-####-????'),
            'zpl' => "^XA\n^FO40,40^FD{sample_name}^FS\n^XZ",
            'default_sample' => false,
            'default_analysis' => false,
            'default_printer' => null,
        ];
    }
}
