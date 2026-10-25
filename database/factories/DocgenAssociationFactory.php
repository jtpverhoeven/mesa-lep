<?php

namespace Database\Factories;

use App\Models\DocgenAssociation;
use App\Models\DocgenTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocgenAssociation>
 */
class DocgenAssociationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assay_id' => 1,
            'original_assay_id' => 1,
            'occurs_in' => DocgenTemplate::factory(),
        ];
    }
}
