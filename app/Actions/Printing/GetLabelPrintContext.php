<?php

namespace App\Actions\Printing;

use App\Models\Sample;
use App\Models\SampleBuffer;
use App\Models\SampleField;

class GetLabelPrintContext
{
    /**
     * @return array{sample: array<string, mixed>, client: array<string, mixed>, method: array<string, mixed>, project: array<string, mixed>, sample_field_types: array<string, string>}
     */
    public function handle(Sample|SampleBuffer $sample): array
    {
        $sampleData = $sample->getAttributes();

        if ($sample instanceof Sample) {
            $sample->loadMissing(['client', 'project', 'samplingProcedure']);
            $client = $sample->getRelation('client');
            $project = $sample->getRelation('project');
            $follow = Sample::query()->where('project', $sample->project)->where('id', '<=', $sample->getKey())->count();
            $extra = json_decode($sample->sample_extra ?: '{}', true);
            $sampleData['db_follow_no'] = $follow;
            $sampleData['user_follow_no'] = is_array($extra) ? ($extra['follow'] ?? $follow) : $follow;
        } else {
            $sample->loadMissing(['clientRecord', 'samplingProcedure']);
            $client = $sample->clientRecord;
            $project = null;
            $sampleData['db_follow_no'] = null;
            $sampleData['user_follow_no'] = null;
        }

        return [
            'sample' => $sampleData,
            'client' => $client?->getAttributes() ?? [],
            'method' => $sample->samplingProcedure?->getAttributes() ?? [],
            'project' => $project?->getAttributes() ?? [],
            'sample_field_types' => SampleField::query()->pluck('type', 'name')->all(),
        ];
    }
}
