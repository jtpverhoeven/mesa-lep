<?php

namespace App\Actions\SampleBuffers;

use App\Actions\Metadata\HydrateSampleMetadata;
use App\Actions\Printing\DispatchLabelEvents;
use App\Actions\Projects\ResolveProjectReference;
use App\Actions\Samples\CreateSampleWithAnalyses;
use App\ClientPortal\ClientPortalService;
use App\Models\SampleBuffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CommitSampleBuffers
{
    public function __construct(
        private CreateSampleWithAnalyses $createSampleWithAnalyses,
        private HydrateSampleMetadata $hydrateSampleMetadata,
        private DispatchLabelEvents $dispatchLabelEvents,
        private ResolveProjectReference $resolveProjectReference,
        private ClientPortalService $clientPortal,
    ) {}

    public function handle(array $ids, int $registeredBy, array $receipt): Collection
    {
        $portalSamples = [];

        $created = DB::transaction(function () use ($ids, $registeredBy, $receipt, &$portalSamples): Collection {
            $buffers = SampleBuffer::query()->whereKey($ids)->lockForUpdate()->orderBy('id')->get();
            $created = collect();
            $createdProjects = [];
            $referencedProjects = [];

            foreach ($buffers as $buffer) {
                $stored = $buffer->portal_order_info ?? [];
                $groupKey = $buffer->project.($buffer->tht ? '|'.$buffer->tht_date?->format('Y-m-d') : '');
                $projectId = $stored['project_id'] ?? $createdProjects[$groupKey] ?? null;
                $sample = $this->createSampleWithAnalyses->handle([
                    'client' => $buffer->client,
                    'project' => $projectId,
                    'project_name' => $buffer->project_name,
                    'portal_project_id' => $buffer->portal_project,
                    'project_custom_fields' => $stored['project_custom_fields'] ?? [],
                    'description' => $buffer->sample_name,
                    'sampling_method' => $buffer->sampling_method,
                    'sample_note' => $stored['sample_note'] ?? null,
                    'custom_fields' => ['details' => $buffer->sample_details],
                    'analyses' => $buffer->analyses_selected ?? [],
                    'registered_by' => $registeredBy,
                ], printLabels: false);

                $createdProjects[$groupKey] = $sample->project;
                $sample->update([
                    ...(in_array((int) $buffer->source, [1, 3], true) ? ['client_description' => $buffer->sample_name] : []),
                    'custom_fields' => json_encode([
                        ...(json_decode($sample->custom_fields ?: '{}', true) ?: []),
                        'details' => $buffer->sample_details,
                    ], JSON_FORCE_OBJECT),
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
                $projectReference = $project->reference;
                if (! isset($referencedProjects[$project->id])) {
                    $projectReference = $this->resolveProjectReference->handle($project);
                    if ($projectReference !== null) {
                        $project->update(['reference' => $projectReference]);
                    }
                    $referencedProjects[$project->id] = $projectReference;
                } else {
                    $projectReference = $referencedProjects[$project->id];
                }

                if ((int) $buffer->source === 3) {
                    $portalSamples[] = [
                        'portal_sample_id' => $sample->portal_sample_id,
                        'portal_project_id' => $sample->portal_project_id,
                        'mesa_sample_id' => $sample->id,
                        'mesa_project_id' => $sample->project,
                        'mesa_barcode' => $sample->barcode,
                        'mesa_project_reference' => $projectReference,
                    ];
                }

                $created->push($sample);
                $buffer->delete();
            }

            $samples = $created->map(fn ($sample) => $sample->fresh(['analyses']));
            $this->dispatchLabelEvents->handle($samples->concat($samples->flatMap(fn ($sample) => $sample->analyses)));

            return $created;
        });

        $this->notifyPortal($portalSamples);

        return $created;
    }

    private function notifyPortal(array $portalSamples): void
    {
        if ($portalSamples === []) {
            return;
        }

        try {
            $response = $this->clientPortal->acceptSamples($portalSamples);

            if (! $response->successful()) {
                Log::warning('Portal sample acceptance failed.', ['status' => $response->status()]);
            }
        } catch (Throwable $exception) {
            Log::warning('Portal sample acceptance failed.', ['error' => $exception->getMessage()]);
        }
    }
}
