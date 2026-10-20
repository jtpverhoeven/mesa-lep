<?php

namespace App\Actions\ResearchProfiles;

use App\Models\ReferenceSource;
use Illuminate\Validation\ValidationException;

class ValidateResearchProfileReferences
{
    public function handle(array $assays, ?int $client): void
    {
        $sourceIds = collect($assays)->pluck('reference_source')->filter()->unique();
        $availableSources = ReferenceSource::query()->availableForClient($client)
            ->whereKey($sourceIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $errors = [];

        foreach (array_values($assays) as $index => $assay) {
            $source = $assay['reference_source'] ?? null;
            $hasReference = false;

            foreach ($assay['reference'] ?? [] as $value) {
                if ($value !== null && ! is_scalar($value)) {
                    $errors["assays.{$index}.reference"] = 'Een referentiewaarde moet tekst of een getal zijn.';

                    continue;
                }

                $hasReference = $hasReference || ($value !== null && trim((string) $value) !== '');
            }

            if ($hasReference && ! $source) {
                $errors["assays.{$index}.reference_source"] = 'Een referentiebron is verplicht wanneer een referentiewaarde is ingevuld.';
            } elseif ($source && ! in_array((int) $source, $availableSources, true)) {
                $errors["assays.{$index}.reference_source"] = 'Deze referentiebron is niet beschikbaar voor dit profiel.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
