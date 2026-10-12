<?php

namespace Database\Factories;

use App\Models\LabelDesign;
use App\Models\LabelEvent;
use App\Models\Printer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabelEvent>
 */
class LabelEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event' => 'P',
            'event_group' => 1,
            'event_data' => '',
            'print_label' => LabelDesign::factory(),
            'print_to' => Printer::factory(),
            'copies' => 1,
        ];
    }

    /** @param array{type: string, inspect?: string, inspect_value: string, negative: string} $condition */
    public function conditional(array $condition, int $group = 1): static
    {
        return $this->state(fn (): array => [
            'event' => 'L',
            'event_group' => $group,
            'event_data' => json_encode($condition, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR),
        ]);
    }
}
