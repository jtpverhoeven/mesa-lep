<?php

namespace App\Http\Controllers;

use App\Actions\SampleFiles\DeleteSampleFile;
use App\Actions\SampleFiles\ListSampleFiles;
use App\Actions\SampleFiles\SetSampleFileVisibility;
use App\Actions\SampleFiles\StoreSampleFile;
use App\Http\Requests\StoreSampleFileRequest;
use App\Models\Sample;
use App\Models\SampleFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SampleFileController extends Controller
{
    public function index(Request $request, Sample $sample, ListSampleFiles $list): JsonResponse
    {
        $this->authorizeView($request);

        return response()->json(['data' => $list->handle($sample)]);
    }

    public function store(
        StoreSampleFileRequest $request,
        Sample $sample,
        StoreSampleFile $store,
        ListSampleFiles $list,
    ): JsonResponse {
        $store->handle(
            $sample,
            $request->file('file'),
            $request->boolean('photo_mode'),
            $request->boolean('instant_visible'),
        );

        return response()->json(['data' => $list->handle($sample)], 201);
    }

    public function updateVisibility(
        Request $request,
        Sample $sample,
        SampleFile $sampleFile,
        SetSampleFileVisibility $setVisibility,
        ListSampleFiles $list,
    ): JsonResponse {
        $this->authorizeView($request);
        $data = $request->validate(['visible_for_client' => ['required', 'boolean']]);
        $setVisibility->handle($sampleFile, (bool) $data['visible_for_client']);

        return response()->json(['data' => $list->handle($sample)]);
    }

    public function destroy(
        Request $request,
        Sample $sample,
        SampleFile $sampleFile,
        DeleteSampleFile $delete,
        ListSampleFiles $list,
    ): JsonResponse {
        $this->authorizeView($request);
        $delete->handle($sampleFile);

        return response()->json(['data' => $list->handle($sample)]);
    }

    public function download(Request $request, Sample $sample, SampleFile $sampleFile): StreamedResponse
    {
        $this->authorizeView($request);
        $this->ensureStored($sampleFile);

        return Storage::disk('local')->download($sampleFile->storagePath(), $sampleFile->original_name);
    }

    public function preview(Request $request, Sample $sample, SampleFile $sampleFile): StreamedResponse
    {
        $this->authorizeView($request);
        $this->ensureStored($sampleFile);

        return Storage::disk('local')->response(
            $sampleFile->storagePath(),
            $sampleFile->original_name,
            [
                'Content-Security-Policy' => "sandbox; default-src 'none'; img-src data: blob:; style-src 'unsafe-inline'",
                'X-Content-Type-Options' => 'nosniff',
            ],
            disposition: 'inline',
        );
    }

    private function authorizeView(Request $request): void
    {
        abort_unless(
            $request->user()?->can('samples.view') || $request->user()?->can('projects.view'),
            403,
        );
    }

    private function ensureStored(SampleFile $sampleFile): void
    {
        abort_unless(Storage::disk('local')->exists($sampleFile->storagePath()), 404);
    }
}
