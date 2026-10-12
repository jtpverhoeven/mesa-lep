<?php

namespace App\Http\Controllers;

use App\Actions\LabelEvents\DeleteLabelEvent;
use App\Actions\LabelEvents\SaveLabelEvent;
use App\Http\Requests\SaveLabelEventRequest;
use App\Models\Assay;
use App\Models\AssayType;
use App\Models\Client;
use App\Models\LabelDesign;
use App\Models\LabelEvent;
use App\Models\Printer;
use App\Models\SampleField;
use App\Models\SampleProcedure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LabelEventController extends Controller
{
    public function index(): View
    {
        return view('label-events.index', [
            'labelEvents' => LabelEvent::query()->with(['labelDesign:id,name', 'printer:id,name'])
                ->orderBy('event_group')->orderBy('id')->get(),
            'options' => $this->options(),
        ]);
    }

    public function create(Request $request): View
    {
        $request->validate(['group' => ['nullable', 'integer', Rule::in(array_keys(LabelEvent::GROUPS))]]);

        return view('label-events.create', [
            'labelEvent' => new LabelEvent(['event' => 'P', 'event_group' => $request->integer('group', 1), 'copies' => 1]),
            'options' => $this->options(),
        ]);
    }

    public function store(SaveLabelEventRequest $request, SaveLabelEvent $saveLabelEvent): RedirectResponse
    {
        $labelEvent = $saveLabelEvent->handle(new LabelEvent, $request->validated());

        return to_route('label-events.index', ['group' => $labelEvent->event_group])->with('success', 'Labelregel is aangemaakt.');
    }

    public function edit(LabelEvent $labelEvent): View
    {
        return view('label-events.edit', ['labelEvent' => $labelEvent, 'options' => $this->options()]);
    }

    public function update(SaveLabelEventRequest $request, LabelEvent $labelEvent, SaveLabelEvent $saveLabelEvent): RedirectResponse
    {
        $saveLabelEvent->handle($labelEvent, $request->validated());

        return to_route('label-events.edit', $labelEvent)->with('success', 'Labelregel is bijgewerkt.');
    }

    public function destroy(LabelEvent $labelEvent, DeleteLabelEvent $deleteLabelEvent): RedirectResponse
    {
        $group = $labelEvent->event_group;
        $deleteLabelEvent->handle($labelEvent);

        return to_route('label-events.index', ['group' => $group])->with('success', 'Labelregel is verwijderd.');
    }

    /** @return array<string, array|int> */
    private function options(): array
    {
        return [
            'groups' => LabelEvent::GROUPS,
            'conditions' => LabelEvent::CONDITIONS,
            'maxCopies' => LabelEvent::MAX_COPIES,
            'sampleTypes' => ['S' => 'Normaal', 'L' => 'Legionella', 'R' => 'RODAC'],
            'labelDesigns' => LabelDesign::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])->toArray(),
            'printers' => Printer::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])->toArray(),
            'sampleFields' => SampleField::query()->orderBy('position')->orderBy('id')->get(['name', 'alias'])->toArray(),
            'clients' => Client::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])->toArray(),
            'procedures' => SampleProcedure::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])->toArray(),
            'assays' => Assay::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])->toArray(),
            'assayTypes' => AssayType::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])->toArray(),
        ];
    }
}
