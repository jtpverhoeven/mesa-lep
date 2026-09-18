<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleField;
use App\Models\SampleProcedure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateProjectSearchSample
{
    public function handle(Project $project, Sample $sample, array $data): Sample
    {
        abort_unless((int) $sample->project === (int) $project->id, 404);

        return DB::transaction(function () use ($project, $sample, $data): Sample {
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
            $lockedSample = Sample::query()->lockForUpdate()->findOrFail($sample->id);

            abort_if($lockedProject->auth_status || $lockedProject->locked, 403, 'Dit project is vergrendeld of geautoriseerd.');

            match ($data['source']) {
                'attribute' => $this->updateAttribute($lockedSample, $data['field'], $data['value']),
                'custom' => $this->updateJsonField($lockedSample, 'custom_fields', $data['field'], $data['value'], $this->customFieldNames()),
                'extra' => $this->updateJsonField($lockedSample, 'sample_extra', $data['field'], $data['value'], $this->extraFieldNames($lockedSample)),
            };

            $lockedProject->update(['last_edit' => now()->timestamp]);

            return $lockedSample->fresh();
        });
    }

    private function updateAttribute(Sample $sample, string $field, string $value): void
    {
        if (! in_array($field, ['description', 'sampling_method'], true)) {
            throw ValidationException::withMessages(['field' => 'Dit monsterveld kan niet worden gewijzigd.']);
        }

        if ($field === 'sampling_method' && ! SampleProcedure::query()->whereKey($value)->where('active', 1)->exists()) {
            throw ValidationException::withMessages(['value' => 'Selecteer een geldige monsternameprocedure.']);
        }

        $sample->update([$field => $value]);
    }

    private function updateJsonField(Sample $sample, string $attribute, string $field, string $value, array $allowedFields): void
    {
        if (! in_array($field, $allowedFields, true)) {
            throw ValidationException::withMessages(['field' => 'Dit monsterveld kan niet worden gewijzigd.']);
        }

        $values = json_decode($sample->{$attribute} ?: '{}', true);
        $values = is_array($values) ? $values : [];
        $values[$field] = $value;
        $sample->update([$attribute => json_encode($values, JSON_FORCE_OBJECT)]);
    }

    private function customFieldNames(): array
    {
        return SampleField::query()->pluck('name')->all();
    }

    private function extraFieldNames(Sample $sample): array
    {
        $values = json_decode($sample->sample_extra ?: '{}', true);

        return collect(is_array($values) ? array_keys($values) : [])
            ->reject(fn (string $name): bool => $name === 'follow')
            ->values()
            ->all();
    }
}
