<?php

namespace Database\Factories;

use App\Models\Export;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Export>
 */
class ExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client' => 1,
            'project' => 1,
            'revision' => 1,
            'print_version' => 1,
            'transaction' => null,
            'type' => 1,
            'template' => 'maz2020',
            'hash' => hash('sha256', fake()->uuid()),
            'date' => (string) now()->timestamp,
            'temporary' => 0,
            'was_authorized' => 1,
            'sample_id' => null,
            'sample_id_scope' => null,
        ];
    }

    public function inTransaction(int $transaction): static
    {
        return $this->state(fn (): array => ['transaction' => $transaction]);
    }

    /** @param list<int> $sampleIds */
    public function withSampleScope(array $sampleIds): static
    {
        return $this->state(fn (): array => ['sample_id_scope' => $sampleIds]);
    }

    public function unauthorized(): static
    {
        return $this->state(fn (): array => ['was_authorized' => 0]);
    }

    public function withPdf(string $contents = "%PDF-1.4\n%%EOF"): static
    {
        return $this->afterCreating(
            fn (Export $export) => Storage::disk('local')->put($export->storagePath(), $contents),
        );
    }
}
