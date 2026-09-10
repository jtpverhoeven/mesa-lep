<?php

namespace App\Http\Controllers;

use App\Actions\Clients\SearchClients;
use App\Actions\Samples\BarcodeGenerator;
use App\Actions\Samples\CreateSample;
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

	public function store(StoreSampleRequest $request, CreateSample $createSample, BarcodeGenerator $barcodeGenerator): JsonResponse
	{
		$sample = $createSample->handle([
			...$request->validated(),
			'registered_by' => $request->user()->id,
		]);

		return response()->json([
			'message' => 'Monster "'.$sample->barcode.'" is aangemeld.',
			'sample' => ['id' => $sample->id, 'barcode' => $sample->barcode, 'project_id' => $sample->project],
			'next_barcode' => $barcodeGenerator->predict(),
		], 201);
	}

	public function nextBarcode(BarcodeGenerator $barcodeGenerator): JsonResponse
	{
		return response()->json(['barcode' => $barcodeGenerator->predict()]);
	}
}