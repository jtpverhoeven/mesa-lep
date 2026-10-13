<?php

namespace App\Http\Controllers;

use App\Actions\PrintProfiles\BuildPrintProfilePlan;
use App\Actions\PrintProfiles\DeletePrintProfile;
use App\Actions\PrintProfiles\QueuePrintProfile;
use App\Actions\PrintProfiles\SavePrintProfile;
use App\Http\Requests\SavePrintProfileRequest;
use App\Http\Requests\TestPrintProfileRequest;
use App\Models\LabelDesign;
use App\Models\Printer;
use App\Models\PrintProfile;
use App\Models\Sample;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class PrintProfileController extends Controller
{
    public function index(): View
    {
        return view('print-profiles.index', ['printProfiles' => PrintProfile::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])]);
    }

    public function create(): View
    {
        return $this->editor(new PrintProfile(['name' => '', 'profile' => '10 EXIT']));
    }

    public function edit(PrintProfile $printProfile): View
    {
        return $this->editor($printProfile);
    }

    public function store(SavePrintProfileRequest $request, SavePrintProfile $savePrintProfile): RedirectResponse
    {
        $profile = $savePrintProfile->handle(new PrintProfile, $request->validated());

        return to_route('print-profiles.edit', $profile)->with('success', 'Printprofiel is aangemaakt.');
    }

    public function update(SavePrintProfileRequest $request, PrintProfile $printProfile, SavePrintProfile $savePrintProfile): RedirectResponse
    {
        $savePrintProfile->handle($printProfile, $request->validated());

        return to_route('print-profiles.edit', $printProfile)->with('success', 'Printprofiel is bijgewerkt.');
    }

    public function destroy(PrintProfile $printProfile, DeletePrintProfile $deletePrintProfile): RedirectResponse
    {
        $deletePrintProfile->handle($printProfile);

        return to_route('print-profiles.index')->with('success', 'Printprofiel is verwijderd.');
    }

    public function test(TestPrintProfileRequest $request, BuildPrintProfilePlan $buildPrintProfilePlan): JsonResponse
    {
        $sample = Sample::query()->where('barcode', $request->validated('barcode'))->firstOrFail();

        try {
            $plan = $buildPrintProfilePlan->handle(new PrintProfile(['profile' => $request->validated('profile')]), $sample,
                $request->filled('printer_override') ? $request->integer('printer_override') : null,
                dateOffset: $request->integer('date_offset'), currentUser: $request->user()->name);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['profile' => $exception->getMessage()]);
        }

        return response()->json([...$plan, 'barcode' => $sample->barcode, 'printer_count' => count(array_unique(array_column($plan['labels'], 'print_to')))]);
    }

    public function run(TestPrintProfileRequest $request, PrintProfile $printProfile, QueuePrintProfile $queuePrintProfile): JsonResponse
    {
        $sample = Sample::query()->where('barcode', $request->validated('barcode'))->firstOrFail();

        try {
            $queuePrintProfile->handle($printProfile, $sample,
                $request->filled('printer_override') ? $request->integer('printer_override') : null,
                $request->integer('date_offset'), $request->user()->name);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['profile' => $exception->getMessage()]);
        }

        return response()->json(['message' => 'Printprofiel staat in de wachtrij.'], 202);
    }

    private function editor(PrintProfile $profile): View
    {
        return view('print-profiles.editor', [
            'printProfile' => $profile,
            'options' => [
                'printers' => Printer::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])->toArray(),
                'labelDesigns' => LabelDesign::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])->toArray(),
            ],
        ]);
    }
}
