<?php

namespace App\Http\Controllers;

use App\Actions\Samples\BarcodeGenerator;
use App\Actions\Samples\BulkSampleRegistrationOptions;
use App\Actions\Samples\CreateLegionellaSamples;
use App\Actions\Samples\CreateRodacSamples;
use App\Http\Requests\StoreBulkSamplesRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BulkSampleController extends Controller
{
    public function create(string $registrationType): View
    {
        return view('samples.bulk-create', ['registrationType' => $registrationType]);
    }

    public function formData(string $registrationType, BulkSampleRegistrationOptions $options, BarcodeGenerator $barcodes): JsonResponse
    {
        return response()->json($options->handle($registrationType, $barcodes));
    }

    public function clientProjects(string $registrationType, Client $client): JsonResponse
    {
        return response()->json(['data' => $client->projects()
            ->where('special_type', $registrationType === 'legionella' ? 1 : 2)
            ->where('subclient', 0)->where('auth_status', 0)->latest('id')
            ->withCount('samples')->get(['id', 'project_name', 'project_date'])]);
    }

    public function clientDetails(string $registrationType, Client $client): JsonResponse
    {
        $files = json_decode($client->attachment ?: '{}', true) ?: [];

        return response()->json(['data' => [
            'reference' => $client->reference,
            'telephone' => $client->telephone,
            'address' => trim($client->street_name.' '.$client->street_number.' '.$client->postal_code.' '.$client->place),
            'notes' => $client->notes,
            'files' => collect($files)->map(fn (array $file) => ['name' => $file['file_description'] ?? 'Bestand'])->values(),
        ]]);
    }

    public function preview(Request $request, string $registrationType, BarcodeGenerator $barcodes): JsonResponse
    {
        $data = $request->validate([
            'matrices' => ['required', 'array'],
            'matrices.*' => ['required', Rule::in($registrationType === 'legionella' ? ['A', 'B', 'C'] : ['normal'])],
        ]);
        $offsets = [];
        $predictions = [];
        $at = now();

        foreach ($data['matrices'] as $matrix) {
            $predictions[] = $barcodes->predict($offsets[$matrix] ?? 0, $at, $matrix === 'normal' ? null : $matrix);
            $offsets[$matrix] = ($offsets[$matrix] ?? 0) + 1;
        }

        return response()->json(['barcodes' => $predictions]);
    }

    public function store(StoreBulkSamplesRequest $request, string $registrationType, CreateLegionellaSamples $legionella, CreateRodacSamples $rodac): JsonResponse
    {
        $data = [...$request->validated(), 'registered_by' => $request->user()->id];
        $samples = ($registrationType === 'legionella' ? $legionella : $rodac)->handle($data);

        return response()->json([
            'message' => $samples->count().' monsters aangemeld.',
            'project_id' => $samples->first()->project,
            'samples' => $samples->map(fn ($sample) => ['id' => $sample->id, 'barcode' => $sample->barcode]),
            'analysis_ids' => $samples->flatMap(fn ($sample) => $sample->analyses()->pluck('id')),
        ], 201);
    }
}
