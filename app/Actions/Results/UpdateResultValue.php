<?php

namespace App\Actions\Results;

use App\ChangeTracking\ChangeTracker;
use App\Models\AssayTypeField;
use App\Models\Project;
use App\Models\Result;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateResultValue
{
    public function __construct(private ChangeTracker $changeTracker) {}

    public function handle(SampleAnalysis $analysis, Result $result, string $field, string $value): Result
    {
        return DB::transaction(function () use ($analysis, $result, $field, $value) {
            $analysis = SampleAnalysis::query()
                ->with('assayRecord')
                ->lockForUpdate()
                ->findOrFail($analysis->getKey());
            $result = Result::query()
                ->whereKey($result->getKey())
                ->where('sa_id', $analysis->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($analysis->project !== null) {
                $project = Project::query()->lockForUpdate()->findOrFail($analysis->project);
                if ($project->locked || $project->auth_status) {
                    throw ValidationException::withMessages([
                        'result' => 'Dit project is vergrendeld of geautoriseerd.',
                    ]);
                }
            }

            $values = is_array($result->data) ? $result->data : [];
            $configured = AssayTypeField::query()
                ->where('test_id', $analysis->assayRecord?->type_base)
                ->where('name', $field)
                ->exists();

            if (! $configured && ! array_key_exists($field, $values)) {
                throw ValidationException::withMessages([
                    'field' => 'Dit resultaatveld hoort niet bij deze analyse.',
                ]);
            }

            $previous = $values[$field] ?? '';
            $values[$field] = $value;
            $result->data = $values;
            $result->save();

            $analysis->is_ready = false;
            $analysis->storedResult = null;
            $analysis->save();

            $alias = AssayTypeField::query()->where('test_id', $analysis->assayRecord?->type_base)->where('name', $field)->value('alias') ?: $field;
            $this->changeTracker->changed(1, project: $analysis->project, sample: $analysis->sample, said: $analysis->id,
                event: $alias.' ['.$analysis->assayRecord?->name.'] Df: '.$result->df.' Rep: '.$result->rep,
                from: $previous, to: $value);

            return $result->fresh();
        });
    }
}
