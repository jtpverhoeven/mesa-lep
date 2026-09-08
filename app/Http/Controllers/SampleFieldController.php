<?php

namespace App\Http\Controllers;

use App\Actions\SampleFields\CreateSampleField;
use App\Actions\SampleFields\DeleteSampleField;
use App\Actions\SampleFields\UpdateSampleField;
use App\Http\Requests\SaveSampleFieldRequest;
use App\Models\SampleField;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SampleFieldController extends Controller
{
    public function index(): View
    {
        return view('sample-fields.index', ['fields' => SampleField::orderBy('position')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('sample-fields.create');
    }

    public function store(SaveSampleFieldRequest $request, CreateSampleField $createSampleField): RedirectResponse
    {
        $field = $createSampleField->handle($request->validated());

        return to_route('sample-fields.index')->with('success', 'Monsterveld "'.$field->alias.'" is aangemaakt.');
    }

    public function edit(SampleField $sampleField): View
    {
        return view('sample-fields.edit', ['field' => $sampleField]);
    }

    public function update(SaveSampleFieldRequest $request, SampleField $sampleField, UpdateSampleField $updateSampleField): RedirectResponse
    {
        $field = $updateSampleField->handle($sampleField, $request->validated());

        return to_route('sample-fields.edit', $field)->with('success', 'Monsterveld "'.$field->alias.'" is bijgewerkt.');
    }

    public function destroy(SampleField $sampleField, DeleteSampleField $deleteSampleField): RedirectResponse
    {
        $deleteSampleField->handle($sampleField);

        return to_route('sample-fields.index')->with('success', 'Monsterveld is verwijderd.');
    }
}