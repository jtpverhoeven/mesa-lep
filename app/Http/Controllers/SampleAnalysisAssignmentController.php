<?php

namespace App\Http\Controllers;

use App\Actions\SampleAnalyses\AddResearchToEmptySamples;
use App\Actions\SampleAnalyses\GetSampleAnalysisOptions;
use App\Http\Requests\StoreSampleRequest;
use App\Models\Client;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SampleAnalysisAssignmentController extends Controller
{
    private const PAGE_SIZE = 150;

    public function index(): View
    {
        return view('samples.assign-analyses');
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:128'],
        ]);
        $search = trim((string) ($validated['search'] ?? ''));

        $query = Sample::query()
            ->where('isEmpty', 1)
            ->whereDoesntHave('analyses')
            ->whereHas('client')
            ->with([
                'client:id,name,notes,attachment',
                'project:id,custom_fields',
            ])
            ->select([
                'id', 'client', 'project', 'barcode', 'description', 'sample_note',
                'sample_innoculated', 'source', 'analyses_data', 'portal_analyses',
            ])
            ->orderBy('id');

        if (isset($validated['after_id'])) {
            $query->where('id', '>', (int) $validated['after_id']);
        }

        if ($search !== '') {
            $searchPattern = '%'.$search.'%';
            $query->where(function (Builder $sampleQuery) use ($searchPattern): void {
                $sampleQuery
                    ->where('barcode', 'ilike', $searchPattern)
                    ->orWhere('description', 'ilike', $searchPattern)
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('name', 'ilike', $searchPattern));
            });
        }

        $loadedSamples = $query->limit(self::PAGE_SIZE + 1)->get();
        $hasMore = $loadedSamples->count() > self::PAGE_SIZE;
        $samples = $loadedSamples->take(self::PAGE_SIZE)->values();

        return response()->json(['data' => [
            'samples' => $samples->map(fn (Sample $sample): array => $this->sampleData($sample))->all(),
            'next_cursor' => $hasMore && $samples->isNotEmpty() ? (int) $samples->last()->id : null,
            'has_more' => $hasMore,
        ]]);
    }

    public function options(Client $client, GetSampleAnalysisOptions $options): JsonResponse
    {
        return response()->json(['data' => $options->handle((int) $client->id)]);
    }

    public function store(Request $request, AddResearchToEmptySamples $addResearch): JsonResponse
    {
        $analysisRules = array_filter(
            (new StoreSampleRequest)->rules(),
            fn (string $key): bool => str_starts_with($key, 'analyses.'),
            ARRAY_FILTER_USE_KEY,
        );
        $validated = $request->validate([
            'sample_ids' => ['required', 'array', 'min:1'],
            'sample_ids.*' => ['required', 'integer', 'distinct', Rule::exists('samples', 'id')],
            'analyses' => ['required', 'array', 'min:1'],
            ...$analysisRules,
        ]);
        $sampleIds = array_map('intval', $validated['sample_ids']);
        $count = $addResearch->handle($sampleIds, $validated['analyses']);

        return response()->json(['data' => [
            'message' => 'Analyses toegevoegd aan '.$count.' '.($count === 1 ? 'monster.' : 'monsters.'),
            'sample_ids' => $sampleIds,
        ]]);
    }

    /**
     * @return array{
     *     id: int,
     *     client: array{id: int, name: string},
     *     barcode: string,
     *     description: string,
     *     ontvangst: string,
     *     ontvangst_tijd: string,
     *     inzet_datum: string,
     *     sample_note: string,
     *     client_has_wishes: bool,
     *     client_wishes_title: string,
     *     import_requested_analysis: list<array{text: string, kind: string}>,
     *     import_other_directions: string,
     *     show_import_details: bool
     * }
     */
    private function sampleData(Sample $sample): array
    {
        $client = $sample->getRelation('client');
        $project = $sample->getRelation('project');
        $projectFields = $this->decodeObject($project?->custom_fields);
        $clientNotes = trim((string) ($client->notes ?? ''));
        $clientAttachments = $this->decodeObject($client->attachment);
        $importDetails = $this->importDetails($sample);
        $wishes = [];

        if ($clientNotes !== '') {
            $wishes[] = $clientNotes;
        }

        if ($clientAttachments !== []) {
            $wishes[] = 'Klantbijlagen beschikbaar.';
        }

        return [
            'id' => (int) $sample->id,
            'client' => [
                'id' => (int) $client->id,
                'name' => (string) $client->name,
            ],
            'barcode' => (string) $sample->barcode,
            'description' => (string) $sample->description,
            'ontvangst' => $this->displayText($projectFields['project_ontvangst'] ?? ''),
            'ontvangst_tijd' => $this->displayText($projectFields['project_tijd_ontvangst'] ?? ''),
            'inzet_datum' => is_numeric($sample->sample_innoculated) && (int) $sample->sample_innoculated > 0
                ? date('d-m-Y', (int) $sample->sample_innoculated)
                : 'Niet ingezet',
            'sample_note' => trim((string) ($sample->sample_note ?? '')),
            'client_has_wishes' => $wishes !== [],
            'client_wishes_title' => implode(' ', $wishes),
            ...$importDetails,
        ];
    }

    /**
     * @return array{
     *     import_requested_analysis: list<array{text: string, kind: string}>,
     *     import_other_directions: string,
     *     show_import_details: bool
     * }
     */
    private function importDetails(Sample $sample): array
    {
        $importInfo = $this->decodeObject($sample->analyses_data);
        $requested = [];

        if ((int) $sample->source === 1) {
            $requestedText = $this->displayText($importInfo['analyses_selected'] ?? '');

            if ($requestedText !== '') {
                $requested[] = ['text' => $requestedText, 'kind' => 'plain'];
            }
        } elseif ((int) $sample->source === 3) {
            $portalInfo = $this->decodeObject($sample->portal_analyses);
            $profileId = $portalInfo['profile_id'] ?? null;

            if ($profileId === null || $profileId === '' || $profileId === 0 || $profileId === '0') {
                foreach ($this->textList($portalInfo['assays_all'] ?? []) as $assay) {
                    $requested[] = ['text' => $assay, 'kind' => 'plain'];
                }
            } else {
                $profile = $this->displayText($portalInfo['profile'] ?? '');

                if ($profile !== '') {
                    $requested[] = ['text' => $profile, 'kind' => 'profile'];
                }

                foreach ($this->textList($portalInfo['assays_addition'] ?? []) as $assay) {
                    $requested[] = ['text' => $assay, 'kind' => 'addition'];
                }

                foreach ($this->textList($portalInfo['assays_substraction'] ?? []) as $assay) {
                    $requested[] = ['text' => $assay, 'kind' => 'subtraction'];
                }
            }
        }

        $directions = $this->displayText($importInfo['misc_directions'] ?? '');

        return [
            'import_requested_analysis' => $requested,
            'import_other_directions' => $directions,
            'show_import_details' => $requested !== [] || $directions !== '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeObject(?string $value): array
    {
        $decoded = json_decode($value ?: '{}', true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<string>
     */
    private function textList(mixed $value): array
    {
        $values = is_array($value) ? array_values($value) : [$value];

        return array_values(array_filter(array_map(fn (mixed $item): string => $this->displayText($item), $values)));
    }

    private function displayText(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', $this->textList($value));
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
