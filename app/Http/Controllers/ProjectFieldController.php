<?php

namespace App\Http\Controllers;

use App\Actions\ProjectFields\CreateProjectField;
use App\Actions\ProjectFields\DeleteProjectField;
use App\Actions\ProjectFields\UpdateProjectField;
use App\Http\Requests\SaveProjectFieldRequest;
use App\Models\ProjectField;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectFieldController extends Controller
{
    public function index(): View
    {
        return view('project-fields.index', ['fields' => ProjectField::orderBy('position')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('project-fields.create');
    }

    public function store(SaveProjectFieldRequest $request, CreateProjectField $createProjectField): RedirectResponse
    {
        $field = $createProjectField->handle($request->validated());

        return to_route('project-fields.index')->with('success', 'Projectveld "'.$field->alias.'" is aangemaakt.');
    }

    public function edit(ProjectField $projectField): View
    {
        return view('project-fields.edit', ['field' => $projectField]);
    }

    public function update(SaveProjectFieldRequest $request, ProjectField $projectField, UpdateProjectField $updateProjectField): RedirectResponse
    {
        $field = $updateProjectField->handle($projectField, $request->validated());

        return to_route('project-fields.edit', $field)->with('success', 'Projectveld "'.$field->alias.'" is bijgewerkt.');
    }

    public function destroy(ProjectField $projectField, DeleteProjectField $deleteProjectField): RedirectResponse
    {
        $deleteProjectField->handle($projectField);

        return to_route('project-fields.index')->with('success', 'Projectveld is verwijderd.');
    }
}