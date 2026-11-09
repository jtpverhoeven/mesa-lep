<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'template' => fake()->paragraph(),
            'template_en' => fake()->paragraph(),
            'bcc' => fake()->safeEmail(),
            'defaultmail' => 0,
            'defaultmail_with_files' => 0,
            'defaultmail_for_files' => 0,
        ];
    }

    public function defaultForReports(): static
    {
        return $this->state(fn (): array => ['defaultmail' => 1]);
    }

    public function defaultWithFiles(): static
    {
        return $this->state(fn (): array => ['defaultmail_with_files' => 1]);
    }

    public function defaultForFiles(): static
    {
        return $this->state(fn (): array => ['defaultmail_for_files' => 1]);
    }
}
