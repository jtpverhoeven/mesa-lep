<?php

namespace Database\Factories;

use App\Models\NoteTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NoteTemplate>
 */
class NoteTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'content' => '<p>'.fake()->sentence().'</p>',
        ];
    }
}
