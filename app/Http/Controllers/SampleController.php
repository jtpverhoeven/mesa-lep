<?php

namespace App\Http\Controllers;

use App\Actions\Clients\SearchClients;
use App\Actions\SampleAnalyses\GetSampleAnalysisOptions;
use App\Actions\SampleBuffers\CreateSampleBuffer;
use App\Actions\Samples\BarcodeGenerator;
use App\Actions\Samples\CreateSampleWithAnalyses;
use App\Http\Requests\StoreSampleRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectField;
use App\Models\SampleField;
use App\Models\SampleProcedure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SampleController extends Controller
{
    public function create(): View
    {
        return view('samples.create');
    }

    public function formData(BarcodeGenerator $barcodeGenerator): JsonResponse
    {
        return response()->json([
            'barcode' => $barcodeGenerator->predict(),
            'project_fields' => ProjectField::query()->orderBy('position')->get(['name', 'alias', 'type', 'std_value']),
            'sample_fields' => SampleField::query()->orderBy('position')->get(['name', 'alias', 'type', 'std_value']),
            'sampling_methods' => SampleProcedure::query()->where('active', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function searchClients(Request $request, SearchClients $searchClients): JsonResponse
    {
        return response()->json([
            'data' => $searchClients->handle((string) $request->query('query')),
        ]);
    }

    public function clientProjects(Client $client): JsonResponse
    {
        $projects = $client->projects()
            ->where('special_type', 0)
            ->where('auth_status', 0)
            ->latest('id')
            ->get(['id', 'project_name', 'project_date']);

        return response()->json(['data' => $projects]);
    }

    public function project(Project $project): JsonResponse
    {
        return response()->json(['data' => [
            'id' => $project->id,
            'client' => $project->client,
            'project_name' => $project->project_name,
            'custom_fields' => json_decode($project->custom_fields ?: '{}', true) ?: [],
        ]]);
    }

    public function analysisOptions(Client $client, GetSampleAnalysisOptions $options): JsonResponse
    {
        return response()->json(['data' => $options->handle($client->id)]);
    }

    public function store(
        StoreSampleRequest $request,
        CreateSampleWithAnalyses $createSample,
        CreateSampleBuffer $createSampleBuffer,
        BarcodeGenerator $barcodeGenerator,
    ): JsonResponse {
        $data = [
            ...$request->validated(),
            'registered_by' => $request->user()->id,
        ];

        if ($data['register_as'] !== 'standard') {
            $buffer = $createSampleBuffer->handle($data);

            return response()->json([
                'message' => $buffer->tht
                    ? 'THT-monster "'.$buffer->tht_code.'" is in de THT-lijst geplaatst.'
                    : 'Monster is in het voorportaal geplaatst.',
                'destination' => $buffer->tht ? 'tht' : 'buffer',
                'buffer' => ['id' => $buffer->id, 'code' => $buffer->tht_code],
                'next_barcode' => $barcodeGenerator->predict(),
            ], 201);
        }

        $sample = $createSample->handle($data);

        return response()->json([
            'message' => 'Monster "'.$sample->barcode.'" is aangemeld.',
            'sample' => [
                'id' => $sample->id,
                'barcode' => $sample->barcode,
                'project_id' => $sample->project,
                'project_name' => Project::query()->whereKey($sample->project)->value('project_name'),
            ],
            'destination' => 'standard',
            'next_barcode' => $barcodeGenerator->predict(),
        ], 201);
    }

    public function nextBarcode(BarcodeGenerator $barcodeGenerator): JsonResponse
    {
        return response()->json(['barcode' => $barcodeGenerator->predict()]);
    }
}
