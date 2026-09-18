<?php

namespace App\Http\Controllers;

use App\Actions\Metadata\CreateMetadata;
use App\Actions\Metadata\DeleteMetadata;
use App\Actions\Metadata\UpdateMetadata;
use App\Models\Metadata;
use App\Models\Sample;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SampleMetadataController extends Controller
{
    public function store(Request $request, Sample $sample, CreateMetadata $createMetadata): JsonResponse
    {
        $this->ensureModifiable($sample);
        $data = $this->validateMetadata($request);
        $metadata = $createMetadata->handle($sample, $data['name'], $data['value'] ?? '');

        return response()->json(['data' => $metadata], 201);
    }

    public function update(
        Request $request,
        Sample $sample,
        Metadata $metadata,
        UpdateMetadata $updateMetadata,
    ): JsonResponse {
        $this->ensureModifiable($sample);
        $this->ensureBelongsToSample($sample, $metadata);
        $data = $this->validateMetadata($request);

        return response()->json([
            'data' => $updateMetadata->handle($metadata, $data['name'], $data['value'] ?? ''),
        ]);
    }

    public function destroy(
        Request $request,
        Sample $sample,
        Metadata $metadata,
        DeleteMetadata $deleteMetadata,
    ): JsonResponse {
        $this->ensureModifiable($sample);
        $this->ensureBelongsToSample($sample, $metadata);
        $deleteMetadata->handle($metadata);

        return response()->json(status: 204);
    }

    /** @return array{name: string, value: string|null} */
    private function validateMetadata(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'value' => ['present', 'nullable', 'string', 'max:65535'],
        ]);
    }

    private function ensureModifiable(Sample $sample): void
    {
        abort_if(
            $sample->project()->where(fn ($query) => $query->where('auth_status', '!=', 0)->orWhere('locked', '!=', 0))->exists(),
            403,
            'Dit project is vergrendeld of geautoriseerd.',
        );
    }

    private function ensureBelongsToSample(Sample $sample, Metadata $metadata): void
    {
        abort_unless((int) $metadata->sample === (int) $sample->id, 404);
    }
}
