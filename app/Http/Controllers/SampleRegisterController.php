<?php

namespace App\Http\Controllers;

use App\Actions\Samples\RegisterSampleInoculation;
use App\Models\Sample;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SampleRegisterController extends Controller
{
    public function index(): View
    {
        return view('samples.register');
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'string', Rule::in(['1', '2', '3'])],
            'before_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:64'],
        ]);
        $listType = (string) ($validated['type'] ?? '1');
        $search = trim((string) ($validated['search'] ?? ''));

        $query = Sample::query()
            ->with(['client', 'project', 'analyses.assayRecord'])
            ->orderByDesc('id');

        if ($search === '') {
            $query->where('sample_type', $this->sampleType($listType));
        } else {
            $this->applySearch($query, $search);
        }

        if (isset($validated['before_id'])) {
            $query->where('id', '<', (int) $validated['before_id']);
        }

        $samples = $query->limit(51)->get();
        $hasMore = $samples->count() > 50;
        $samples = $samples->take(50)->values();

        return response()->json(['data' => [
            'samples' => $samples->map(fn (Sample $sample): array => $this->sampleData($sample))->all(),
            'next_cursor' => $hasMore && $samples->isNotEmpty() ? (int) $samples->last()->id : null,
            'has_more' => $hasMore,
        ]]);
    }

    public function inoculate(Request $request, RegisterSampleInoculation $register): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:64'],
            'overwrite' => ['sometimes', 'boolean'],
        ]);
        $sample = $this->findSample((string) $validated['barcode']);

        if ($sample === null) {
            throw ValidationException::withMessages(['barcode' => 'Onbekende barcode.']);
        }

        try {
            $sample = $register->handle($sample, (bool) ($validated['overwrite'] ?? false));
        } catch (ValidationException $exception) {
            if (array_key_exists('already_started', $exception->errors())) {
                return response()->json([
                    'code' => 'already_started',
                    'message' => $exception->errors()['already_started'][0],
                ], 409);
            }

            throw $exception;
        }

        return response()->json(['data' => [
            'message' => 'Monster '.$sample->barcode.' is ingezet.',
            'sample' => $this->sampleData($sample->load(['client', 'project', 'analyses.assayRecord'])),
        ]]);
    }

    private function applySearch(Builder $query, string $search): void
    {
        $barcode = trim(explode('.', $search, 2)[0]);

        $query->where(function (Builder $sampleQuery) use ($barcode): void {
            $sampleQuery->where('barcode', $barcode);

            if (ctype_digit($barcode) && strlen($barcode) < 8) {
                $sampleQuery->orWhere('follow_no', (int) $barcode);
            }
        });
    }

    private function findSample(string $barcode): ?Sample
    {
        $baseBarcode = trim(explode('.', trim($barcode), 2)[0]);
        $sample = Sample::query()->where('barcode', $baseBarcode)->first();

        if ($sample !== null) {
            return $sample;
        }

        $upperBarcode = strtoupper($baseBarcode);
        $sampleType = 'S';
        $matrixType = '-';
        $followNumber = $upperBarcode;

        foreach (['LA' => 'A', 'LB' => 'B', 'LC' => 'C', 'L' => '-'] as $suffix => $matrix) {
            if (str_ends_with($upperBarcode, $suffix)) {
                $sampleType = 'L';
                $matrixType = $matrix;
                $followNumber = substr($upperBarcode, 0, -strlen($suffix));
                break;
            }
        }

        if (! ctype_digit($followNumber) || strlen($followNumber) >= 8) {
            return null;
        }

        return Sample::query()
            ->where('follow_no', (int) $followNumber)
            ->where('sample_type', $sampleType)
            ->where('leg_type', $matrixType)
            ->orderByDesc('id')
            ->first();
    }

    private function sampleType(string $listType): string
    {
        return ['1' => 'S', '2' => 'L', '3' => 'R'][$listType] ?? 'S';
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleData(Sample $sample): array
    {
        $project = $sample->getRelation('project');
        $client = $sample->getRelation('client');
        $projectFields = json_decode($project?->custom_fields ?: '{}', true);
        $projectFields = is_array($projectFields) ? $projectFields : [];
        $inoculatedAt = $this->dateParts($sample->sample_innoculated);
        $flags = [
            'listeria' => false,
            'salmonella' => false,
            'campylobacter' => false,
            'stec' => false,
        ];

        foreach ($sample->analyses as $analysis) {
            $name = strtolower((string) $analysis->getRelation('assayRecord')?->name);

            foreach (array_keys($flags) as $flag) {
                if (str_contains($name, $flag)) {
                    $flags[$flag] = true;
                }
            }
        }

        return [
            'id' => (int) $sample->id,
            'barcode' => (string) $sample->barcode,
            'client' => (string) ($client?->name ?: 'Onbekend'),
            'description' => (string) $sample->description,
            'received_date' => (string) ($projectFields['project_ontvangst'] ?? 'Onbekend'),
            'received_time' => (string) ($projectFields['project_tijd_ontvangst'] ?? 'Onbekend'),
            'inoculation_date' => $inoculatedAt['date'],
            'inoculation_time' => $inoculatedAt['time'],
            'started' => $inoculatedAt['date'] !== '',
            'stored_in' => (string) ($sample->stored_in ?? ''),
            'diluted_at' => (string) ($sample->diluted_at ?? ''),
            'has_note' => filled($sample->sample_note),
            'flags' => $flags,
            'sample_type' => (string) $sample->sample_type,
        ];
    }

    /**
     * @return array{date: string, time: string}
     */
    private function dateParts(mixed $value): array
    {
        if ($value === null || trim((string) $value) === '' || trim((string) $value) === '0') {
            return ['date' => '', 'time' => ''];
        }

        $date = CarbonImmutable::createFromTimestamp((int) $value, 'UTC')
            ->setTimezone('Europe/Amsterdam');

        return [
            'date' => $date->format('d-m-Y'),
            'time' => $date->format('H:i'),
        ];
    }
}
