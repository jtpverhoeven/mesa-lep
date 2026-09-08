<?php

namespace App\Http\Controllers;

use App\Actions\Assays\CreateAssayField;
use App\Actions\Assays\DeleteAssayField;
use App\Actions\Assays\UpdateAssayField;
use App\Http\Requests\StoreAssayFieldRequest;
use App\Http\Requests\UpdateAssayFieldRequest;
use App\Models\AssayField;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssayFieldController extends Controller
{
    public function index(): View
    {
        return view('assay-fields.index', [
            'fields' => AssayField::where('active', 1)
                ->orderBy('position')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('assay-fields.create');
    }

    public function store(
        StoreAssayFieldRequest $request,
        CreateAssayField $createAssayField,
    ): RedirectResponse {
        $assayField = $createAssayField->handle($request->validated());

        return to_route('assay-fields.index')->with('success', 'Analyseveld "'.$assayField->name.'" is aangemaakt.');
    }

    public function edit(AssayField $assayField): View
    {
        $this->ensureActive($assayField);

        return view('assay-fields.edit', ['assayField' => $assayField]);
    }

    public function update(
        UpdateAssayFieldRequest $request,
        AssayField $assayField,
        UpdateAssayField $updateAssayField,
    ): RedirectResponse {
        $this->ensureActive($assayField);
        $assayField = $updateAssayField->handle($assayField, $request->validated());

        return to_route('assay-fields.edit', $assayField)->with('success', 'Analyseveld "'.$assayField->name.'" is bijgewerkt.');
    }

    public function destroy(
        AssayField $assayField,
        DeleteAssayField $deleteAssayField,
    ): RedirectResponse {
        $this->ensureActive($assayField);
        $deleteAssayField->handle($assayField);

        return to_route('assay-fields.index')->with('success', 'Analyseveld is verwijderd.');
    }

    private function ensureActive(AssayField $assayField): void
    {
        abort_unless((int) $assayField->active === 1, 404);
    }
}