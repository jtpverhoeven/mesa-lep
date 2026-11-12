<?php

namespace Database\Factories;

use App\Enums\NoteKind;
use App\Models\Note;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => NoteKind::Internal,
            'body' => fake()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    public function report(): static
    {
        return $this->state(fn (): array => ['kind' => NoteKind::Report, 'body' => '<p>'.fake()->sentence().'</p>']);
    }

    public function system(): static
    {
        return $this->state(fn (): array => ['user_id' => null, 'origin' => Note::ORIGIN_LEGACY]);
    }

    public function forNotable(Sample|Project $notable): static
    {
        return $this->for($notable, 'notable');
    }
}
