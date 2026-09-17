<?php

namespace App\Http\Controllers;

use App\Actions\AssuranceForms\GetOrCreateAssuranceForm;
use App\Actions\AssuranceForms\QueueAssuranceFormSynchronization;
use App\Actions\AssuranceForms\ResolveAssuranceDay;
use App\Actions\Samples\RegisterSampleInoculation;
use App\Models\Cvar;
use App\Models\Project;
use App\Models\Sample;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SampleRegisterController extends Controller
{
    private const STORAGE_CVAR = 'MESA_CURRENT_BIN';

    private const DILUTION_CVAR = 'MESA_CURRENT_DILUTION';

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
            'settings' => $this->settingsForUser((string) $request->user()->getAuthIdentifier()),
        ]]);
    }

    public function inoculate(Request $request, RegisterSampleInoculation $register): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:64'],
            'overwrite' => ['sometimes', 'boolean'],
            'storage' => ['sometimes', 'nullable', 'string', 'max:5'],
            'dilution_at' => ['sometimes', 'nullable', 'string', 'max:5'],
        ]);
        $sample = $this->findSample((string) $validated['barcode']);

        if ($sample === null) {
            throw ValidationException::withMessages(['barcode' => 'Onbekende barcode.']);
        }

        $settings = $this->settingsForUser((string) $request->user()->getAuthIdentifier());
        $storedIn = array_key_exists('storage', $validated) ? (string) ($validated['storage'] ?? '') : $settings['storage'];
        $dilutedAt = array_key_exists('dilution_at', $validated) ? (string) ($validated['dilution_at'] ?? '') : $settings['dilution_at'];

        try {
            $sample = $register->handle($sample, (bool) ($validated['overwrite'] ?? false), $storedIn, $dilutedAt);
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

    public function settings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'storage' => ['nullable', 'string', 'max:5'],
            'dilution_at' => ['nullable', 'string', 'max:5'],
        ]);
        $userId = (string) $request->user()->getAuthIdentifier();

        $this->saveUserSetting(self::STORAGE_CVAR, $userId, (string) ($validated['storage'] ?? ''), 'A');
        $this->saveUserSetting(self::DILUTION_CVAR, $userId, (string) ($validated['dilution_at'] ?? ''), '1');

        return response()->json(['data' => $this->settingsForUser($userId)]);
    }

    public function updateConditions(
        Request $request,
        Sample $sample,
        ResolveAssuranceDay $resolveDay,
        GetOrCreateAssuranceForm $getOrCreateForm,
        QueueAssuranceFormSynchronization $queueAssuranceSync,
    ): JsonResponse {
        $validated = $request->validate([
            'innoc' => ['required', 'date_format:d-m-Y H:i'],
            'storage' => ['nullable', 'string', 'max:5'],
            'diluted_at' => ['nullable', 'string', 'max:5'],
        ]);
        $inoculatedAt = $this->parseInoculationDate((string) $validated['innoc']);
        $previousInoculation = '';

        $updated = DB::transaction(function () use ($sample, $inoculatedAt, $validated, &$previousInoculation): Sample {
            $lockedSample = Sample::query()->lockForUpdate()->findOrFail($sample->getKey());
            $project = (int) $lockedSample->project > 0
                ? Project::query()->lockForUpdate()->find($lockedSample->project)
                : null;

            if ($project?->locked || $project?->auth_status) {
                throw ValidationException::withMessages([
                    'sample' => 'Dit project is vergrendeld of geautoriseerd.',
                ]);
            }

            $previousInoculation = (string) ($lockedSample->sample_innoculated ?? '');

            if (! $this->hasInoculation($previousInoculation)) {
                throw ValidationException::withMessages([
                    'sample' => 'Kan niet wijzigen. Dit monster is nog niet gescand.',
                ]);
            }

            $lockedSample->sample_innoculated = (string) $inoculatedAt->timestamp;
            $lockedSample->stored_in = (string) ($validated['storage'] ?? '');
            $lockedSample->diluted_at = (string) ($validated['diluted_at'] ?? '');
            $lockedSample->save();

            return $lockedSample->fresh();
        });

        $newFormDate = $resolveDay->handle($updated->sample_innoculated)['form_date'];
        $getOrCreateForm->handle($newFormDate);
        $queueAssuranceSync->handleMany([$previousInoculation, $updated->sample_innoculated]);

        return response()->json(['data' => [
            'message' => 'Monster '.$updated->barcode.' is bijgewerkt.',
            'sample' => $this->sampleData($updated->load(['client', 'project', 'analyses.assayRecord'])),
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
     * @return array{storage: string, dilution_at: string}
     */
    private function settingsForUser(string $userId): array
    {
        return [
            'storage' => $this->userSetting(self::STORAGE_CVAR, $userId, 'A'),
            'dilution_at' => $this->userSetting(self::DILUTION_CVAR, $userId, '1'),
        ];
    }

    private function userSetting(string $name, string $userId, string $fallback): string
    {
        $values = json_decode((string) Cvar::query()->where('cvar', $name)->value('value'), true);

        if (! is_array($values) || ! array_key_exists($userId, $values)) {
            return $fallback;
        }

        $value = trim((string) $values[$userId]);

        return $value !== '' ? $value : $fallback;
    }

    private function saveUserSetting(string $name, string $userId, string $value, string $fallback): void
    {
        $cvar = Cvar::query()->firstOrNew(['cvar' => $name]);
        $values = json_decode((string) $cvar->value, true);
        $values = is_array($values) ? $values : [];
        $values[$userId] = $value;
        $cvar->value = json_encode($values, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
        $cvar->default ??= $fallback;
        $cvar->description ??= 'Monsterregistratie instelling per gebruiker.';
        $cvar->save();
    }

    private function parseInoculationDate(string $value): CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('!d-m-Y H:i', trim($value), 'Europe/Amsterdam');
        } catch (\Throwable) {
            throw ValidationException::withMessages(['innoc' => 'Gebruik het formaat dd-mm-jjjj uu:mm.']);
        }

        if (! $date instanceof CarbonImmutable || $date->format('d-m-Y H:i') !== trim($value)) {
            throw ValidationException::withMessages(['innoc' => 'Gebruik het formaat dd-mm-jjjj uu:mm.']);
        }

        return $date;
    }

    private function hasInoculation(string $value): bool
    {
        $value = trim($value);

        return $value !== '' && $value !== '0';
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
