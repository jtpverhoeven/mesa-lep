<?php

namespace App\Actions\Samples;

use App\Actions\Projects\CreateProject;
use App\Actions\Projects\UpdateProjectFromRegistration;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleField;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSample
{
    public function handle(array $data): Sample
    {
        return DB::transaction(function () use ($data) {
            $now = now();
            DB::statement("select pg_advisory_xact_lock(hashtext('mesa-lims-sample-barcode'))");
            $lastSample = Sample::query()
                ->where('sample_type', '!=', 'L')
                ->latest('id')
                ->lockForUpdate()
                ->first(['follow_no', 'date_registered']);
            $barcodeGenerator = app(BarcodeGenerator::class);
            $barcode = $barcodeGenerator->forLastSample($lastSample, $now);
            $followNumber = $barcodeGenerator->followNumberForLastSample($lastSample, $now);
            $project = $this->project($data, $barcode, $now);

            return Sample::create([
                'barcode' => $barcode,
                'follow_no' => $followNumber,
                'description' => $data['description'],
                'client_description' => 'Geen omschrijving beschikbaar',
                'sampling_method' => $data['sampling_method'],
                'date_registered' => (string) $now->timestamp,
                'registered_by' => $data['registered_by'],
                'client' => $data['client'],
                'subclient' => 0,
                'project' => $project->id,
                'custom_fields' => json_encode($this->customFields($data['custom_fields'] ?? []), JSON_FORCE_OBJECT),
                'predicted_end' => $now->timestamp,
                'sample_innoculated' => '',
                'stored_in' => null,
                'sample_note' => $data['sample_note'] ?? null,
                'sample_type' => 'S',
                'leg_type' => '-',
                'isEmpty' => 1,
                'source' => 0,
                'analyses_data' => '[]',
            ]);
        });
    }

    private function project(array $data, string $barcode, $now): Project
    {
        if (! empty($data['project'])) {
            $project = Project::query()
                ->whereKey($data['project'])
                ->where('client', $data['client'])
                ->first();

            if ($project === null) {
                throw ValidationException::withMessages(['project' => 'Het gekozen project hoort niet bij deze klant.']);
            }

            return app(UpdateProjectFromRegistration::class)->handle($project, [
                'project_name' => $data['project_name'],
                'custom_fields' => $data['project_custom_fields'] ?? [],
            ], $now->timestamp);
        }

        return app(CreateProject::class)->handle([
            'client' => $data['client'],
            'project_name' => $data['project_name'] ?: $barcode,
            'custom_fields' => $data['project_custom_fields'] ?? [],
            'added_by' => $data['registered_by'],
        ], $now);
    }

    private function customFields(array $values): array
    {
        return SampleField::query()
            ->orderBy('position')
            ->get(['name', 'std_value'])
            ->mapWithKeys(fn (SampleField $field) => [$field->name => $values[$field->name] ?? $field->std_value])
            ->all();
    }
}
