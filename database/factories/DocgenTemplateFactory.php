<?php

namespace Database\Factories;

use App\Models\DocgenTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocgenTemplate>
 */
class DocgenTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'MAZ 2020',
            'folder' => 'maz2020',
            'active' => true,
            'view_order' => 1,
        ];
    }
}
