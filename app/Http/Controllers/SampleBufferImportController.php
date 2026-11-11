<?php

namespace App\Http\Controllers;

use App\Actions\Clients\SearchClients;
use App\Actions\SampleBuffers\ImportSampleBuffers;
use App\Actions\SampleBuffers\PreviewSampleBufferImport;
use App\Http\Requests\CommitSampleBufferImportRequest;
use App\Http\Requests\PreviewSampleBufferImportRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SampleBufferImportController extends Controller
{
    public function index(): View
    {
        return view('sample-buffers.import');
    }

    public function template(): BinaryFileResponse
    {
        return response()->download(resource_path('files/maz_bulk.xlsm'), 'maz_bulk.xlsm');
    }

    public function clients(Request $request, SearchClients $searchClients): JsonResponse
    {
        return response()->json([
            'data' => $searchClients->handle((string) $request->query('query')),
        ]);
    }

    public function preview(PreviewSampleBufferImportRequest $request, PreviewSampleBufferImport $previewImport): JsonResponse
    {
        $client = Client::query()->findOrFail($request->integer('client'));

        return response()->json([
            'data' => $previewImport->handle($client, $request->file('file')->get(), $request->input('separator')),
        ]);
    }

    public function store(CommitSampleBufferImportRequest $request, ImportSampleBuffers $importBuffers): JsonResponse
    {
        $client = Client::query()->findOrFail($request->integer('client'));
        $buffers = $importBuffers->handle($client, $request->validated('rows'), $request->validated('meta_columns') ?? []);

        return response()->json([
            'data' => [
                'count' => $buffers->count(),
                'portal_url' => $request->user()->can('portal.access') ? route('sample-buffers.index') : null,
            ],
        ], 201);
    }
}
