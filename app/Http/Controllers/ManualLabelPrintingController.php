<?php

namespace App\Http\Controllers;

use App\Actions\Printing\BuildManualLabelPrintPlan;
use App\Actions\Printing\GetLabelPrintingSettings;
use App\Actions\Printing\SelectManualPrintSamples;
use App\Actions\Printing\SendLabelPrintPlan;
use App\Http\Requests\ManualLabelPrintingRequest;
use App\Models\AssayType;
use App\Models\Cvar;
use App\Models\LabelDesign;
use App\Models\Printer;
use App\Models\PrintProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;

class ManualLabelPrintingController extends Controller
{
    public function index(SelectManualPrintSamples $selection, GetLabelPrintingSettings $settings): View
    {
        $extra = Cvar::query()->where('cvar', 'standard_extra_label')->first();

        return view('printing.index', ['options' => [
            'profiles' => PrintProfile::query()->orderBy('id')->get(['id', 'name']),
            'printers' => Printer::query()->orderBy('name')->orderBy('id')->get(['id', 'name']),
            'labels' => LabelDesign::query()->orderBy('name')->orderBy('id')->get(['id', 'name']),
            'assayTypes' => AssayType::query()->orderBy('name')->orderBy('id')->get(['id', 'name']),
            'dateTypes' => $selection->dateTypes(), 'today' => now()->format('Y-m-d'),
            'extraLabel' => (int) ($extra?->value ?? $extra?->default ?? 0),
            'enabled' => $settings->handle()['enabled'],
        ]]);
    }

    public function store(ManualLabelPrintingRequest $request, BuildManualLabelPrintPlan $builder, SendLabelPrintPlan $sender): JsonResponse
    {
        try {
            if ($request->filled('confirmation_token')) {
                $key = 'manual-print:'.$request->user()->id.':'.$request->validated('confirmation_token');
                $response = Cache::lock($key.':lock', 30)->get(function () use ($key, $sender): JsonResponse {
                    $plan = Cache::pull($key);

                    if (! is_array($plan)) {
                        throw ValidationException::withMessages(['confirmation_token' => 'Deze printbevestiging is verlopen of al gebruikt. Start de printopdracht opnieuw.']);
                    }

                    return $this->send($plan, $sender);
                });

                return $response ?: response()->json(['message' => 'Deze printopdracht wordt al verwerkt.'], 409);
            }

            $plan = $builder->handle($request->validated(), $request->user()->name);
            $limitSetting = Cvar::query()->where('cvar', 'MAX_LABEL_PRINT')->first();
            $limit = max(0, (int) ($limitSetting?->value ?? $limitSetting?->default ?? 250));

            if ($plan['total_labels'] > $limit) {
                $token = (string) Str::uuid();
                Cache::put('manual-print:'.$request->user()->id.':'.$token, $plan, now()->addMinutes(10));

                return response()->json(['confirmation_required' => true, 'confirmation_token' => $token,
                    'total_labels' => $plan['total_labels'], 'sample_count' => $plan['sample_count'],
                    'message' => 'Je staat op het punt '.$plan['total_labels'].' labels te printen. Weet je het zeker?'], 409);
            }

            return $this->send($plan, $sender);
        } catch (InvalidArgumentException|LogicException $exception) {
            throw ValidationException::withMessages(['options' => $exception->getMessage()]);
        }
    }

    /** @param array{labels: list<array<string, mixed>>, total_labels: int, sample_count: int} $plan */
    private function send(array $plan, SendLabelPrintPlan $sender): JsonResponse
    {
        $spools = DB::transaction(fn (): array => $sender->handle($plan['labels']));
        $disabled = $spools !== [] && $spools[0]['status'] === 'disabled';

        return response()->json(['total_labels' => $plan['total_labels'], 'sample_count' => $plan['sample_count'], 'disabled' => $disabled,
            'message' => $plan['total_labels'] === 0 ? 'Geen labels gevonden voor deze selectie.' : ($disabled ? 'Printen is uitgeschakeld. De labels zijn niet afgedrukt.' : $plan['total_labels'].' labels staan in de wachtrij.')], 202);
    }
}
