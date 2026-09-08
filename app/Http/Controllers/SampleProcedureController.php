<?php

namespace App\Http\Controllers;

use App\Actions\SampleProcedures\CreateSampleProcedure;
use App\Actions\SampleProcedures\DeactivateSampleProcedure;
use App\Actions\SampleProcedures\SetDefaultSampleProcedures;
use App\Actions\SampleProcedures\UpdateSampleProcedure;
use App\Http\Requests\SaveDefaultSampleProceduresRequest;
use App\Http\Requests\SaveSampleProcedureRequest;
use App\Models\Cvar;
use App\Models\SampleProcedure;
use App\Models\SampleProcedureField;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SampleProcedureController extends Controller
{
    public function index(): View
    {
        return view('sample-procedures.index', [
            'procedures' => SampleProcedure::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('sample-procedures.create', ['fields' => $this->fields()]);
    }

    public function store(SaveSampleProcedureRequest $request, CreateSampleProcedure $createSampleProcedure): RedirectResponse
    {
        $procedure = $createSampleProcedure->handle($request->validated());

        return to_route('sample-procedures.index')->with('success', 'Bemonsterprocedure "'.$procedure->name.'" is aangemaakt.');
    }

    public function edit(SampleProcedure $sampleProcedure): View
    {
        return view('sample-procedures.edit', [
            'procedure' => $sampleProcedure,
            'fields' => $this->fields(),
            'values' => $this->values($sampleProcedure),
        ]);
    }

    public function update(
        SaveSampleProcedureRequest $request,
        SampleProcedure $sampleProcedure,
        UpdateSampleProcedure $updateSampleProcedure,
    ): RedirectResponse {
        $procedure = $updateSampleProcedure->handle($sampleProcedure, $request->validated());

        return to_route('sample-procedures.edit', $procedure)->with('success', 'Bemonsterprocedure "'.$procedure->name.'" is bijgewerkt.');
    }

    public function destroy(SampleProcedure $sampleProcedure, DeactivateSampleProcedure $deactivateSampleProcedure): RedirectResponse
    {
        $deactivateSampleProcedure->handle($sampleProcedure);

        return to_route('sample-procedures.index')->with('success', 'Bemonsterprocedure is gedeactiveerd.');
    }

    public function defaults(): View
    {
        return view('sample-procedures.defaults', [
            'procedures' => SampleProcedure::where('active', 1)->orderBy('name')->get(),
            'defaults' => Cvar::whereIn('cvar', [
                'MESA_STD_SMPL_METHOD',
                'MESA_STD_LEGSMPL_METHOD',
                'MESA_STD_RODACSMPL_METHOD',
            ])->pluck('value', 'cvar'),
        ]);
    }

    public function updateDefaults(
        SaveDefaultSampleProceduresRequest $request,
        SetDefaultSampleProcedures $setDefaultSampleProcedures,
    ): RedirectResponse {
        $setDefaultSampleProcedures->handle($request->validated());

        return to_route('sample-procedures.defaults')->with('success', 'Standaard bemonsterprocedures zijn bijgewerkt.');
    }

    private function fields()
    {
        return SampleProcedureField::orderBy('position')->orderBy('name')->get();
    }

    private function values(SampleProcedure $procedure): array
    {
        $values = json_decode($procedure->fields, true);

        return is_array($values) ? $values : [];
    }
}