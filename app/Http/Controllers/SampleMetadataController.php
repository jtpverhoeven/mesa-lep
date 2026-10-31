<?php

namespace App\Http\Controllers;

use App\Actions\Metadata\CreateMetadata;
use App\Actions\Metadata\DeleteMetadata;
use App\Actions\Metadata\UpdateMetadata;
use App\Actions\Projects\EnsureEditable;
use App\Models\Metadata;
use App\Models\Sample;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SampleMetadataController extends Controller
{
    public function __construct(private EnsureEditable $ensureEditable) {}

    public function store(Request $request, Sample $sample, CreateMetadata $createMetadata): JsonResponse
    {
        $data = $this->validateMetadata($request);
        $metadata = $this->whileEditable($sample, fn (Sample $lockedSample): Metadata => $createMetadata->handle($lockedSample, $data['name'], $data['value'] ?? ''));

        return response()->json(['data' => $metadata], 201);
    }

    public function update(
        Request $request,
        Sample $sample,
        Metadata $metadata,
        UpdateMetadata $updateMetadata,
    ): JsonResponse {
        $this->ensureBelongsToSample($sample, $metadata);
        $data = $this->validateMetadata($request);

        return response()->json([
            'data' => $this->whileEditable($sample, fn (): Metadata => $updateMetadata->handle($metadata, $data['name'], $data['value'] ?? '')),
        ]);
    }

    public function destroy(
        Request $request,
        Sample $sample,
        Metadata $metadata,
        DeleteMetadata $deleteMetadata,
    ): JsonResponse {
        $this->ensureBelongsToSample($sample, $metadata);
        $this->whileEditable($sample, fn () => $deleteMetadata->handle($metadata));

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

    /**
     * @template TResult
     *
     * @param  Closure(Sample): TResult  $write
     * @return TResult
     */
    private function whileEditable(Sample $sample, Closure $write): mixed
    {
        return DB::transaction(fn (): mixed => $write($this->ensureEditable->forSample($sample->id)));
    }

    private function ensureBelongsToSample(Sample $sample, Metadata $metadata): void
    {
        abort_unless((int) $metadata->sample === (int) $sample->id, 404);
    }
}
