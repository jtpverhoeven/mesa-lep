<?php

namespace App\Actions\SampleBuffers;

use App\Actions\Metadata\HydrateSampleMetadata;
use App\Actions\Printing\DispatchLabelEvents;
use App\Actions\Projects\ResolveProjectReference;
use App\Actions\SampleAnalyses\AddResearchProfileToSample;
use App\Actions\Samples\BulkSampleRegistrationOptions;
use App\Actions\Samples\CreateSampleWithAnalyses;
use App\Actions\Samples\RegisterSampleInoculation;
use App\ClientPortal\ClientPortalService;
use App\Models\ProjectField;
use App\Models\ResearchProfile;
use App\Models\Sample;
use App\Models\SampleBuffer;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CommitSampleBuffers
{
    private const RODAC = '2';

    private const LEGIONELLA = '3';

    public function __construct(
        private CreateSampleWithAnalyses $createSampleWithAnalyses,
        private HydrateSampleMetadata $hydrateSampleMetadata,
        private DispatchLabelEvents $dispatchLabelEvents,
        private ResolveProjectReference $resolveProjectReference,
        private ClientPortalService $clientPortal,
        private AddResearchProfileToSample $addResearchProfile,
        private BulkSampleRegistrationOptions $options,
        private RegisterSampleInoculation $registerSampleInoculation,
    ) {}

    public function handle(array $ids, int $registeredBy, array $receipt): Collection
    {
        $portalSamples = [];

        $created = DB::transaction(function () use ($ids, $registeredBy, $receipt, &$portalSamples): Collection {
            $buffers = SampleBuffer::query()->whereKey($ids)->lockForUpdate()->orderBy('id')->get()
                ->groupBy(fn (SampleBuffer $buffer) => $this->groupKey($buffer))
                ->flatten(1);
            $created = collect();
            $createdProjects = [];
            $referencedProjects = [];
            $follows = [];

            foreach ($buffers as $buffer) {
                $stored = $buffer->portal_order_info ?? [];
                $groupKey = $this->groupKey($buffer);
                $follows[$groupKey] = ($follows[$groupKey] ?? 0) + 1;
                $projectId = $stored['project_id'] ?? $createdProjects[$groupKey] ?? null;
                $properties = $this->properties($buffer);
                $type = (string) $buffer->sample_research_type;
                $isLegionella = $type === self::LEGIONELLA;
                $isRodac = $type === self::RODAC;
                $sample = $this->createSampleWithAnalyses->handle([
                    'client' => $buffer->client,
                    'project' => $projectId,
                    'project_name' => filled($buffer->project_name) ? $buffer->project_name : null,
                    'portal_project_id' => $buffer->portal_project,
                    'project_custom_fields' => $projectId === null
                        ? $this->projectCustomFields($stored, $properties, $isLegionella || $isRodac)
                        : [],
                    'description' => $buffer->sample_name,
                    'sampling_method' => $buffer->sampling_method,
                    'sample_note' => $stored['sample_note'] ?? '',
                    'custom_fields' => ['details' => $buffer->sample_details],
                    'analyses' => $isLegionella || ! is_array($buffer->analyses_selected) ? [] : $buffer->analyses_selected,
                    'registered_by' => $registeredBy,
                    ...$this->typeAttributes($buffer, $properties, $follows[$groupKey]),
                ], printLabels: false);

                $createdProjects[$groupKey] = $sample->project;
                $sample->update([
                    'custom_fields' => json_encode(['details' => $buffer->sample_details], JSON_FORCE_OBJECT),
                    'tht_code' => $buffer->tht_code,
                    'source' => $buffer->source,
                    'analyses_data' => json_encode([
                        'misc_directions' => $buffer->misc_directions,
                        'analyses_selected' => $buffer->getRawOriginal('analyses_selected'),
                    ], JSON_FORCE_OBJECT),
                    ...(filled($buffer->misc_directions) ? ['portal_notes' => $buffer->misc_directions] : []),
                    ...(in_array((int) $buffer->source, [1, 3], true) ? ['client_description' => $buffer->sample_name] : []),
                    ...(filled($buffer->portal_product_group_id) ? ['portal_product_group_id' => $buffer->portal_product_group_id] : []),
                    ...((int) $buffer->source === 3 ? [
                        'portal_sample_id' => $buffer->portal_id,
                        'portal_project_id' => $buffer->portal_project,
                        'portal_analyses' => $buffer->getRawOriginal('portal_analyses'),
                    ] : []),
                ]);

                if ($isLegionella) {
                    $this->registerLegionellaProfile($sample, (string) ($properties['matrixBaseProfile']['value'] ?? ''));
                }

                $this->hydrateSampleMetadata->handle(
                    $sample,
                    $buffer->meta ?? [],
                    (int) $buffer->source === 3 ? ($buffer->portal_meta ?? []) : [],
                );
                if ($isRodac && $this->options->value('RODAC_AUTO_REGISTER_INNOCULATION') === '1' && empty($sample->sample_innoculated)) {
                    $sample = $this->registerSampleInoculation->handle($sample, storedIn: '', dilutedAt: '');
                }

                $project = $sample->project()->firstOrFail();
                $projectFields = json_decode($project->custom_fields ?: '{}', true) ?: [];
                $project->update([
                    'custom_fields' => json_encode([
                        ...$projectFields,
                        'project_monster' => $buffer->sampling_date,
                        'project_ontvangst' => $buffer->tht ? $buffer->receive_date : $this->legacyDate($receipt['receive_date']),
                        'project_tijd_ontvangst' => $buffer->tht ? $buffer->receive_time : $receipt['receive_time'],
                    ], JSON_FORCE_OBJECT),
                    'last_edit' => now()->timestamp,
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

    private function groupKey(SampleBuffer $buffer): string
    {
        return $buffer->project.($buffer->tht ? '|'.$buffer->tht_date?->format('Y-m-d') : '');
    }

    /** @return array<string, array{property_name: string, value: mixed}> */
    private function properties(SampleBuffer $buffer): array
    {
        $properties = array_filter($buffer->sample_properties ?? [], 'is_array');

        return array_column($properties, null, 'property_name');
    }

    /**
     * Legionella and Rodac samples keep their legacy sample type, description, extra data and project type.
     *
     * @param  array<string, array{property_name: string, value: mixed}>  $properties
     * @return array<string, mixed>
     */
    private function typeAttributes(SampleBuffer $buffer, array $properties, int $follow): array
    {
        $value = fn (string $name): string => (string) ($properties[$name]['value'] ?? '');

        return match ((string) $buffer->sample_research_type) {
            self::LEGIONELLA => $this->legionellaAttributes($value, $follow),
            self::RODAC => [
                'special_type' => 2,
                'project_extra' => [
                    'sample_method' => $this->options->value('MESA_STD_RODACSMPL_METHOD'),
                    'number_of_blanks' => '0',
                ],
                'sample_type' => 'R',
                'description' => $this->options->value('RODAC_STD_DESCRIPTION'),
                'sample_extra' => ['follow' => $follow, 'location' => $value('rodacRoom')],
            ],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private function legionellaAttributes(Closure $value, int $follow): array
    {
        $matrix = $value('matrixType');
        $volume = (int) $this->options->value('LEGIONELLA_FILTER_VOLUME_'.(in_array($matrix, ['A', 'B', 'C'], true) ? $matrix : 'A'));

        return [
            'special_type' => 1,
            'sample_type' => 'L',
            'leg_type' => $matrix,
            'description' => $this->options->value('LEGIONELLA_STD_DESCRIPTION'),
            'sample_extra' => [
                'follow' => $follow,
                'type' => $value('waterLineType'),
                'temperature' => $value('waterTemperature'),
                'filter_volume' => $volume,
            ],
        ];
    }

    /**
     * Legionella and Rodac projects are preloaded with the sampling time; every other project field becomes '???'.
     *
     * @param  array<string, mixed>  $stored
     * @param  array<string, array{property_name: string, value: mixed}>  $properties
     * @return array<string, string>
     */
    private function projectCustomFields(array $stored, array $properties, bool $preload): array
    {
        if (! $preload) {
            return $stored['project_custom_fields'] ?? [];
        }

        $samplingTime = (string) ($properties['waterSamplingTime']['value'] ?? $properties['rodacSamplingTime']['value'] ?? '');

        return ProjectField::query()->orderBy('position')->pluck('name')
            ->mapWithKeys(fn (string $name) => [$name => $name === 'project_bemonster_tijd' ? $samplingTime : '???'])
            ->all();
    }

    private function registerLegionellaProfile(Sample $sample, string $profileId): void
    {
        if (! ctype_digit($profileId)) {
            return;
        }

        $profile = ResearchProfile::query()
            ->where(fn ($query) => $query->whereKey($profileId)->orWhere('original_id', $profileId))
            ->latest('id')
            ->first();

        if ($profile !== null) {
            $this->addResearchProfile->handle($sample, $profile, legacyRegistration: true, printLabels: false);
        }
    }

    private function legacyDate(string $date): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            ? CarbonImmutable::createFromFormat('!Y-m-d', $date)->format('d-m-Y')
            : $date;
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
