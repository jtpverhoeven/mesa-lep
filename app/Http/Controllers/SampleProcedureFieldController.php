<?php

namespace App\Http\Controllers;

use App\Actions\SampleProcedures\CreateSampleProcedureField;
use App\Actions\SampleProcedures\DeleteSampleProcedureField;
use App\Actions\SampleProcedures\UpdateSampleProcedureField;
use App\Http\Requests\SaveSampleProcedureFieldRequest;
use App\Models\SampleProcedureField;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SampleProcedureFieldController extends Controller
{
    public function index(): View
    {
        return view('sample-procedure-fields.index', [
            'fields' => SampleProcedureField::orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('sample-procedure-fields.create');
    }

    public function store(
        SaveSampleProcedureFieldRequest $request,
        CreateSampleProcedureField $createSampleProcedureField,
    ): RedirectResponse {
        $field = $createSampleProcedureField->handle($request->validated());

        return to_route('sample-procedure-fields.index')->with('success', 'Monsternameveld "'.$field->alias.'" is aangemaakt.');
    }

    public function edit(SampleProcedureField $sampleProcedureField): View
    {
        return view('sample-procedure-fields.edit', ['field' => $sampleProcedureField]);
    }

    public function update(
        SaveSampleProcedureFieldRequest $request,
        SampleProcedureField $sampleProcedureField,
        UpdateSampleProcedureField $updateSampleProcedureField,
    ): RedirectResponse {
        $field = $updateSampleProcedureField->handle($sampleProcedureField, $request->validated());

        return to_route('sample-procedure-fields.edit', $field)->with('success', 'Monsternameveld "'.$field->alias.'" is bijgewerkt.');
    }

    public function destroy(
        SampleProcedureField $sampleProcedureField,
        DeleteSampleProcedureField $deleteSampleProcedureField,
    ): RedirectResponse {
        $deleteSampleProcedureField->handle($sampleProcedureField);

        return to_route('sample-procedure-fields.index')->with('success', 'Monsternameveld is verwijderd.');
    }
}