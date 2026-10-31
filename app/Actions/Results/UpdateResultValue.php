<?php

namespace App\Actions\Results;

use App\Actions\Projects\EnsureEditable;
use App\ChangeTracking\ChangeTracker;
use App\Jobs\CalculateAnalysisResultJob;
use App\Keyrings\KeyringType;
use App\Models\AssayTypeField;
use App\Models\Project;
use App\Models\Result;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateResultValue
{
    public function __construct(
        private ChangeTracker $changeTracker,
        private GetMetaAnalysisParent $metaParent,
        private EnsureEditable $ensureEditable,
    ) {}

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

            $project = $analysis->project === null ? null : Project::query()->lockForUpdate()->findOrFail($analysis->project);
            $this->ensureEditable->handle($project, KeyringType::Sample, (int) $analysis->sample);

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

            $parent = $this->metaParent->handle($analysis);

            if ($parent !== null) {
                $parent->update(['is_ready' => false, 'storedResult' => null]);
                CalculateAnalysisResultJob::dispatch($parent->id)->afterCommit();
            }

            $alias = AssayTypeField::query()->where('test_id', $analysis->assayRecord?->type_base)->where('name', $field)->value('alias') ?: $field;
            $this->changeTracker->changed(1, project: $analysis->project, sample: $analysis->sample, said: $analysis->id,
                event: $alias.' ['.$analysis->assayRecord?->name.'] Df: '.$result->df.' Rep: '.$result->rep,
                from: $previous, to: $value);

            return $result->fresh();
        });
    }
}
