<?php

namespace App\Http\Controllers;

use App\Actions\LabelDesigns\CreateLabelDesign;
use App\Actions\LabelDesigns\DeleteLabelDesign;
use App\Actions\LabelDesigns\UpdateLabelDesign;
use App\Http\Requests\SaveLabelDesignRequest;
use App\Models\LabelDesign;
use App\Models\Printer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LabelDesignController extends Controller
{
    public function index(): View
    {
        return view('label-designs.index', [
            'labelDesigns' => LabelDesign::query()->with('printer:id,name')->orderBy('name')->orderBy('id')
                ->get(['id', 'name', 'default_sample', 'default_analysis', 'default_printer']),
        ]);
    }

    public function create(): View
    {
        return view('label-designs.create', ['labelDesign' => new LabelDesign, 'printers' => $this->printers()]);
    }

    public function store(SaveLabelDesignRequest $request, CreateLabelDesign $createLabelDesign): RedirectResponse
    {
        $labelDesign = $createLabelDesign->handle($request->validated());

        return to_route('label-designs.index')->with('success', 'Labelontwerp "'.$labelDesign->name.'" is aangemaakt.');
    }

    public function edit(LabelDesign $labelDesign): View
    {
        return view('label-designs.edit', ['labelDesign' => $labelDesign, 'printers' => $this->printers()]);
    }

    public function update(SaveLabelDesignRequest $request, LabelDesign $labelDesign, UpdateLabelDesign $updateLabelDesign): RedirectResponse
    {
        $labelDesign = $updateLabelDesign->handle($labelDesign, $request->validated());

        return to_route('label-designs.edit', $labelDesign)->with('success', 'Labelontwerp "'.$labelDesign->name.'" is bijgewerkt.');
    }

    public function destroy(LabelDesign $labelDesign, DeleteLabelDesign $deleteLabelDesign): RedirectResponse
    {
        $name = $labelDesign->name;
        $deleteLabelDesign->handle($labelDesign);

        return to_route('label-designs.index')->with('success', 'Labelontwerp "'.$name.'" is verwijderd.');
    }

    /** @return Collection<int, Printer> */
    private function printers(): Collection
    {
        return Printer::query()->orderBy('name')->orderBy('id')->get(['id', 'name']);
    }
}
