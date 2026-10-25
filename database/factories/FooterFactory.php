<?php

namespace Database\Factories;

use App\Models\Footer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Footer>
 */
class FooterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'maz2020',
            'lang' => 'nl',
            'q' => 0,
            'data' => fake()->sentence(),
        ];
    }
}
