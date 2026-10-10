<?php

namespace App\Http\Controllers;

use App\Actions\ChangeTracking\GetChangeRevisions;
use App\Models\AssuranceForm;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ChangeRevisionController extends Controller
{
    public function index(Request $request, GetChangeRevisions $revisions): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['required', Rule::in(['project', 'sample', 'analysis', 'assurance-form'])],
            'id' => ['required', 'integer', 'min:1'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'sample_id' => ['required_if:scope,analysis', 'nullable', 'integer', 'min:1'],
            'types' => ['sometimes', 'array', 'max:50'],
            'types.*' => ['required', 'string', 'max:32'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $scope = $data['scope'];
        $id = (int) $data['id'];
        $projectId = $data['project_id'] ?? null;
        Gate::authorize(match ($scope) {
            'project' => 'projects.view',
            'assurance-form' => 'assurance-form.view',
            default => $projectId ? 'projects.view' : 'samples.view',
        });

        match ($scope) {
            'project' => Project::query()->findOrFail($id),
            'assurance-form' => AssuranceForm::query()->findOrFail($id),
            'sample' => Sample::query()->when($projectId, fn ($query) => $query->where('project', $projectId))->findOrFail($id),
            'analysis' => SampleAnalysis::query()->where('sample', $data['sample_id'])
                ->when($projectId, fn ($query) => $query->where('project', $projectId))->findOrFail($id),
        };

        return response()->json($revisions->handle($scope, $id, $data['types'] ?? [], (int) ($data['page'] ?? 1),
            isset($data['sample_id']) ? (int) $data['sample_id'] : null));
    }
}
