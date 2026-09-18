<?php

namespace App\Actions\SampleBuffers;

use App\Actions\Metadata\HydrateSampleMetadata;
use App\Actions\Samples\CreateSampleWithAnalyses;
use App\Models\SampleBuffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommitSampleBuffers
{
    public function __construct(
        private CreateSampleWithAnalyses $createSampleWithAnalyses,
        private HydrateSampleMetadata $hydrateSampleMetadata,
    ) {}

    public function handle(array $ids, int $registeredBy, array $receipt): Collection
    {
        return DB::transaction(function () use ($ids, $registeredBy, $receipt): Collection {
            $buffers = SampleBuffer::query()->whereKey($ids)->lockForUpdate()->orderBy('id')->get();
            $created = collect();
            $createdProjects = [];

            foreach ($buffers as $buffer) {
                $stored = $buffer->portal_order_info ?? [];
                $groupKey = $buffer->project.($buffer->tht ? '|'.$buffer->tht_date?->format('Y-m-d') : '');
                $projectId = $stored['project_id'] ?? $createdProjects[$groupKey] ?? null;
                $sample = $this->createSampleWithAnalyses->handle([
                    'client' => $buffer->client,
                    'project' => $projectId,
                    'project_name' => $buffer->project_name,
                    'project_custom_fields' => $stored['project_custom_fields'] ?? [],
                    'description' => $buffer->sample_name,
                    'sampling_method' => $buffer->sampling_method,
                    'sample_note' => $stored['sample_note'] ?? null,
                    'custom_fields' => [],
                    'analyses' => $buffer->analyses_selected ?? [],
                    'registered_by' => $registeredBy,
                ]);

                $createdProjects[$groupKey] = $sample->project;
                $sample->update([
                    'tht_code' => $buffer->tht_code,
                    'source' => $buffer->source,
                    'portal_notes' => $buffer->misc_directions,
                    'portal_sample_id' => $buffer->portal_id,
                    'portal_product_group_id' => $buffer->portal_product_group_id,
                    'portal_project_id' => $buffer->portal_project,
                    'portal_analyses' => $buffer->portal_analyses ? json_encode($buffer->portal_analyses) : null,
                ]);
                $this->hydrateSampleMetadata->handle(
                    $sample,
                    $buffer->meta ?? [],
                    (int) $buffer->source === 3 ? ($buffer->portal_meta ?? []) : [],
                );
                $project = $sample->project()->firstOrFail();
                $projectExtra = json_decode($project->project_extra ?: '{}', true) ?: [];
                $project->update([
                    'project_date' => $buffer->sampling_date,
                    'project_extra' => json_encode([
                        ...$projectExtra,
                        'receive_date' => $buffer->tht ? $buffer->receive_date : $receipt['receive_date'],
                        'receive_time' => $buffer->tht ? $buffer->receive_time : $receipt['receive_time'],
                    ], JSON_FORCE_OBJECT),
                ]);
                $created->push($sample);
                $buffer->delete();
            }

            return $created;
        });
    }
}
