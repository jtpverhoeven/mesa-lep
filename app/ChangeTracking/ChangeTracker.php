<?php

namespace App\ChangeTracking;

use App\Models\ChangeRevision;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use LogicException;

class ChangeTracker
{
    public function changed(
        int|string $type,
        int|false|null $project = null,
        int|false|null $sample = null,
        int|false|null $said = null,
        string|false|null $event = null,
        string|int|float|bool|null $from = null,
        string|int|float|bool|null $to = null,
        int|false|null $assuranceForm = null,
        ?int $userId = null,
    ): ChangeRevision {
        $userId ??= auth()->id();

        if ($userId === null) {
            throw new LogicException('A change revision requires an authenticated user or an explicit user ID.');
        }

        if (! $sample && $said) {
            $analysis = SampleAnalysis::query()->find($said);
            $sample = $analysis?->sample;
            $project = $project ?: $analysis?->project;
        }

        if (! $project && $sample) {
            $project = Sample::query()->whereKey($sample)->value('project');
        }

        return ChangeRevision::query()->create([
            'user_id' => $userId,
            'timestamp' => (string) now()->timestamp,
            'type' => (string) $type,
            'project' => $project === false ? null : $project,
            'sample' => $sample === false ? null : $sample,
            'said' => $said === false ? null : $said,
            'assurance_form' => $assuranceForm === false ? null : $assuranceForm,
            'event' => $event === false ? '' : $event,
            'from' => $from === null ? null : (string) $from,
            'to' => $to === null ? null : (string) $to,
        ]);
    }
}
