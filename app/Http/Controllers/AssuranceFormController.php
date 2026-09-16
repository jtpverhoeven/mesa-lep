<?php

namespace App\Http\Controllers;

use App\Actions\AssuranceForms\BuildAssuranceFormState;
use App\Actions\AssuranceForms\GetOrCreateAssuranceForm;
use App\Actions\AssuranceForms\ResolveAssuranceDay;
use App\Actions\AssuranceForms\SynchronizeAssuranceForm;
use App\Actions\AssuranceForms\UpdateAssuranceExplanation;
use App\Actions\AssuranceForms\UpdateAssuranceFormField;
use App\Http\Requests\AssuranceForms\UpdateAssuranceExplanationRequest;
use App\Http\Requests\AssuranceForms\UpdateAssuranceFormFieldRequest;
use App\Models\AssuranceForm;
use App\Models\Cvar;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class AssuranceFormController extends Controller
{
    public function index(Request $request, ResolveAssuranceDay $resolveDay): View
    {
        $date = $request->query('date', CarbonImmutable::now('Europe/Amsterdam')->format('Y-m-d'));
        $formDate = $resolveDay->handle($date)['form_date'];

        return view('assurance-forms.index', [
            'formDate' => $formDate,
            'incompleteForms' => $this->incompleteForms(),
        ]);
    }

    public function show(
        Request $request,
        string $date,
        ResolveAssuranceDay $resolveDay,
        SynchronizeAssuranceForm $synchronize,
        BuildAssuranceFormState $state,
    ): JsonResponse|View {
        $day = $this->resolve($resolveDay, $date);

        if (! $request->expectsJson()) {
            return view('assurance-forms.index', [
                'formDate' => $day['form_date'],
                'incompleteForms' => $this->incompleteForms(),
            ]);
        }

        $form = AssuranceForm::query()->where('date', $day['timestamp'])->first();

        if ($form === null) {
            return response()->json(['data' => [
                'exists' => false,
                'form_date' => $day['form_date'],
                'display_date' => $day['display_date'],
                'incomplete_forms' => $this->incompleteForms(),
            ]]);
        }

        $form = $synchronize->handle($form);

        if (! $form->exists) {
            return response()->json(['data' => [
                'exists' => false,
                'form_date' => $day['form_date'],
                'display_date' => $day['display_date'],
                'incomplete_forms' => $this->incompleteForms(),
            ]]);
        }

        return response()->json(['data' => ['exists' => true, ...$this->formState($state, $form)]]);
    }

    public function store(
        string $date,
        ResolveAssuranceDay $resolveDay,
        GetOrCreateAssuranceForm $create,
        SynchronizeAssuranceForm $synchronize,
        BuildAssuranceFormState $state,
    ): JsonResponse {
        $day = $this->resolve($resolveDay, $date);

        if (! $synchronize->hasSamplesForDay($day['form_date'])) {
            return response()->json(['data' => [
                'exists' => false,
                'form_date' => $day['form_date'],
                'display_date' => $day['display_date'],
                'message' => 'Voor deze inzetdatum zijn geen monsters ingezet; er is geen formulier aangemaakt.',
                'incomplete_forms' => $this->incompleteForms(),
            ]]);
        }

        $form = $create->handle($day['form_date']);

        if (! $form->exists) {
            return response()->json(['data' => [
                'exists' => false,
                'form_date' => $day['form_date'],
                'display_date' => $day['display_date'],
                'message' => 'Voor deze inzetdatum zijn geen actieve monsters gevonden; er is geen formulier aangemaakt.',
                'incomplete_forms' => $this->incompleteForms(),
            ]]);
        }

        return response()->json(['data' => ['exists' => true, ...$this->formState($state, $form)]], 201);
    }

    public function updateField(
        UpdateAssuranceFormFieldRequest $request,
        AssuranceForm $assuranceForm,
        UpdateAssuranceFormField $update,
        BuildAssuranceFormState $state,
    ): JsonResponse {
        $data = $request->validated();
        $form = $update->handle($assuranceForm, $data['field_key'], (string) ($data['value'] ?? ''));

        return response()->json(['data' => $this->formState($state, $form)]);
    }

    public function updateExplanation(
        UpdateAssuranceExplanationRequest $request,
        AssuranceForm $assuranceForm,
        UpdateAssuranceExplanation $update,
        BuildAssuranceFormState $state,
    ): JsonResponse {
        $data = $request->validated();
        $form = $update->handle($assuranceForm, $data['field_key'], (string) ($data['explanation'] ?? ''));

        return response()->json(['data' => $this->formState($state, $form)]);
    }

    public function print(
        AssuranceForm $assuranceForm,
        SynchronizeAssuranceForm $synchronize,
        BuildAssuranceFormState $state,
    ): View {
        $form = $synchronize->handle($assuranceForm);

        return view('assurance-forms.print', ['form' => $state->handle($form)]);
    }

    private function resolve(ResolveAssuranceDay $resolveDay, string $date): array
    {
        try {
            return $resolveDay->handle($date);
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }
    }

    private function formState(BuildAssuranceFormState $state, AssuranceForm $form): array
    {
        return [
            'exists' => true,
            ...$state->handle($form),
            'incomplete_forms' => $this->incompleteForms(),
        ];
    }

    /**
     * @return array<int, array{id: int, form_date: string, display_date: string}>
     */
    private function incompleteForms(): array
    {
        $days = max(0, (int) (Cvar::query()->where('cvar', 'BORG_TIME_BEFORE_CHECK')->value('value') ?? 10));
        $cutoff = CarbonImmutable::now('Europe/Amsterdam')->subDays($days);

        return AssuranceForm::query()
            ->where('is_complete', false)
            ->orderBy('date')
            ->get(['id', 'date'])
            ->filter(fn (AssuranceForm $form): bool => CarbonImmutable::createFromTimestamp((int) $form->date, 'UTC')
                ->setTimezone('Europe/Amsterdam')
                ->lessThan($cutoff))
            ->map(fn (AssuranceForm $form): array => [
                'id' => (int) $form->id,
                'form_date' => CarbonImmutable::createFromTimestamp((int) $form->date, 'Europe/Amsterdam')->format('Y-m-d'),
                'display_date' => CarbonImmutable::createFromTimestamp((int) $form->date, 'Europe/Amsterdam')->format('d-m-Y'),
            ])
            ->values()
            ->all();
    }
}
