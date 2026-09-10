<?php

namespace App\Http\Controllers;

use App\Actions\Clients\SearchClients;
use App\Actions\ResearchProfiles\BulkChangeResearchProfiles;
use App\Actions\ResearchProfiles\CopyResearchProfileAssays;
use App\Actions\ResearchProfiles\SaveResearchProfile;
use App\Http\Requests\BulkChangeResearchProfilesRequest;
use App\Http\Requests\SaveResearchProfileRequest;
use App\Models\Assay;
use App\Models\Matrix;
use App\Models\ReferenceSource;
use App\Models\ResearchProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResearchProfileController extends Controller
{
    public function index(): View
    {
        return view('research-profiles.index');
    }

    public function editor(?ResearchProfile $researchProfile = null): View
    {
        return view('research-profiles.editor', ['researchProfile' => $researchProfile]);
    }

    public function bulk(): View
    {
        return view('research-profiles.bulk');
    }

    public function list(Request $request): JsonResponse
    {
        $profiles = ResearchProfile::query()
            ->with('clientRecord:id,name,active')
            ->where('active', 1)
            ->when($request->string('query')->trim()->isNotEmpty(), fn ($query) => $query->where('name', 'like', '%'.$request->string('query')->trim().'%'))
            ->where(fn ($query) => $query->where('client', 0)->orWhereHas('clientRecord', fn ($client) => $client->where('active', 1)))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $profiles->map(fn ($profile) => [
            'id' => $profile->id, 'name' => $profile->name, 'global' => $profile->global,
            'scope' => $profile->global ? 'Globaal' : $profile->clientRecord?->name,
            'portal_visible' => $profile->portal_visible, 'lims_visible' => $profile->lims_visible,
        ])]);
    }

    public function data(?ResearchProfile $researchProfile = null): JsonResponse
    {
        $researchProfile?->load([
            'clientRecord:id,name,reference',
            'assayProfiles' => fn ($query) => $query->where('hidden', 0),
            'assayProfiles.assayRecord:id,name',
            'assayProfiles.referenceSource:id,name,client',
        ]);
        $referenceSourceClient = $researchProfile && ! $researchProfile->global ? (int) $researchProfile->client : null;
        $revisions = $researchProfile?->revisions()->reorder('id', 'desc')->get(['id']) ?? collect();

        return response()->json(['data' => [
            'profile' => $researchProfile ? [
                ...$researchProfile->only(['id', 'original_id', 'name', 'global', 'client', 'portal_visible', 'lims_visible']),
                'is_tip' => (bool) $researchProfile->active,
                'client_record' => $researchProfile->clientRecord,
                'assays' => $researchProfile->assayProfiles->map(fn ($item) => [
                    ...$item->only(['assay', 'dillutions', 'replicates', 'reference', 'conf_trip', 'reference_source', 'project_order']),
                    'name' => $item->assayRecord?->name,
                    'reference_source_record' => $item->referenceSource ? [
                        'id' => $item->referenceSource->id,
                        'name' => $item->referenceSource->localizedName(),
                    ] : null,
                ])->values(),
            ] : null,
            'revisions' => $revisions->values()->map(fn ($revision, $index) => [
                'id' => $revision->id, 'label' => 'Revisie '.($revisions->count() - $index).($index === 0 ? ' (actueel)' : ''),
            ]),
            'matrices' => Matrix::query()->orderBy('name')->get(['id', 'name']),
            'assays' => Assay::query()->with('matrixContents:assay_base,matrix')->where('active', 1)->orderBy('name')
                ->get(['id', 'original_id', 'name', 'dillution', 'replicates'])
                ->map(fn ($assay) => [
                    ...$assay->only(['id', 'original_id', 'name', 'dillution', 'replicates']),
                    'matrices' => $assay->matrixContents->pluck('matrix')->values(),
                ]),
            'reference_sources' => $this->referenceSourceOptions($referenceSourceClient),
        ]]);
    }

    public function referenceSources(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->referenceSourceOptions($request->integer('client') ?: null),
        ]);
    }

    public function clients(Request $request, SearchClients $searchClients): JsonResponse
    {
        return response()->json(['data' => $searchClients->handle((string) $request->query('query'))]);
    }

    public function store(SaveResearchProfileRequest $request, SaveResearchProfile $save): JsonResponse
    {
        $profile = $save->handle($request->validated());

        return response()->json(['message' => 'Onderzoeksprofiel is aangemaakt.', 'data' => ['id' => $profile->id]], 201);
    }

    public function update(SaveResearchProfileRequest $request, ResearchProfile $researchProfile, SaveResearchProfile $save): JsonResponse
    {
        $profile = $save->handle($request->validated(), $researchProfile);

        return response()->json(['message' => 'Nieuwe revisie is opgeslagen.', 'data' => ['id' => $profile->id]]);
    }

    public function destroy(ResearchProfile $researchProfile): JsonResponse
    {
        $researchProfile->update(['active' => 0]);

        return response()->json(['message' => 'Onderzoeksprofiel is verwijderd.']);
    }

    public function copy(Request $request, ResearchProfile $researchProfile, CopyResearchProfileAssays $copy): JsonResponse
    {
        abort_unless($researchProfile->active, 422, 'Alleen naar de actuele revisie kan worden gekopieerd.');
        $validated = $request->validate(['source_id' => ['required', 'integer', Rule::exists('researchprofiles', 'id')]]);
        $count = $copy->handle(ResearchProfile::findOrFail($validated['source_id']), $researchProfile);

        return response()->json(['message' => $count.' analyses zijn gekopieerd.']);
    }

    public function bulkUpdate(BulkChangeResearchProfilesRequest $request, BulkChangeResearchProfiles $bulk): JsonResponse
    {
        $count = $bulk->handle($request->validated());

        return response()->json(['message' => $count.' profielen zijn bijgewerkt.']);
    }

    private function referenceSourceOptions(?int $client): array
    {
        return ReferenceSource::query()
            ->availableForClient($client)
            ->orderBy('id')
            ->get(['id', 'name', 'client'])
            ->map(fn (ReferenceSource $source) => [
                'id' => $source->id,
                'name' => $source->localizedName(),
                'client' => $source->client,
            ])
            ->all();
    }
}