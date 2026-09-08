<?php

namespace App\Http\Controllers;

use App\Actions\Assays\CreateAssayTypeField;
use App\Actions\Assays\CreateAssayType;
use App\Actions\Assays\DeleteAssayTypeField;
use App\Actions\Assays\UpdateAssayType;
use App\Actions\Assays\UpdateAssayTypeField;
use App\Http\Requests\StoreAssayTypeRequest;
use App\Http\Requests\StoreAssayTypeFieldRequest;
use App\Http\Requests\UpdateAssayTypeFieldRequest;
use App\Http\Requests\UpdateAssayTypeRequest;
use App\Models\AssayType;
use App\Models\AssayTypeField;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssayTypeController extends Controller
{
    public function index(): View
    {
        return view('assay-types.index', ['types' => AssayType::where('active', 1)->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('assay-types.create');
    }

    public function store(StoreAssayTypeRequest $request, CreateAssayType $createAssayType): RedirectResponse
    {
        $createAssayType->handle($request->validated(), $request->user());

        return to_route('assays.create')->with('success', 'Analytisch basistype aangemaakt.');
    }

    public function edit(AssayType $assayType): View
    {
        return view('assay-types.edit', [
            'type' => $assayType->load('fields'),
        ]);
    }

    public function update(
        UpdateAssayTypeRequest $request,
        AssayType $assayType,
        UpdateAssayType $updateAssayType,
    ): RedirectResponse
    {
        $assayType = $updateAssayType->handle($assayType, $request->validated());

        return to_route('assay-types.edit', $assayType)->with('success', 'Analytisch basistype bijgewerkt.');
    }

    public function storeField(
        StoreAssayTypeFieldRequest $request,
        AssayType $assayType,
        CreateAssayTypeField $createAssayTypeField,
    ): RedirectResponse
    {
        $createAssayTypeField->handle($assayType, $request->validated());

        return to_route('assay-types.edit', $assayType)->with('success', 'Resultaatveld toegevoegd.');
    }

    public function editField(AssayType $assayType, AssayTypeField $field): View
    {
        abort_unless((int) $field->test_id === (int) $assayType->id, 404);

        return view('assay-types.fields.edit', [
            'type' => $assayType,
            'field' => $field,
        ]);
    }

    public function updateField(
        UpdateAssayTypeFieldRequest $request,
        AssayType $assayType,
        AssayTypeField $field,
        UpdateAssayTypeField $updateAssayTypeField,
    ): RedirectResponse {
        abort_unless((int) $field->test_id === (int) $assayType->id, 404);
        $updateAssayTypeField->handle($field, $request->validated());

        return to_route('assay-types.edit', $assayType)->with('success', 'Resultaatveld bijgewerkt.');
    }

    public function destroyField(
        AssayType $assayType,
        AssayTypeField $field,
        DeleteAssayTypeField $deleteAssayTypeField,
    ): RedirectResponse
    {
        abort_unless((int) $field->test_id === (int) $assayType->id, 404);
        $deleteAssayTypeField->handle($field);

        return to_route('assay-types.edit', $assayType)->with('success', 'Resultaatveld verwijderd.');
    }
}
