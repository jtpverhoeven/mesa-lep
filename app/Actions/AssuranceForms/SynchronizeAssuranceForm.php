<?php

namespace App\Actions\AssuranceForms;

use App\Confirmations\AssayConfirmationConfiguration;
use App\Confirmations\SynchronizeConfirmationScopes;
use App\Models\Assay;
use App\Models\AssuranceForm;
use App\Models\Media;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SynchronizeAssuranceForm
{
    public function __construct(
        private ResolveAssuranceDay $resolveDay,
        private RecalculateAssuranceForm $recalculate,
        private SynchronizeConfirmationScopes $confirmationScopes,
    ) {}

    public function handle(AssuranceForm $form, array $additionalMediaIds = []): AssuranceForm
    {
        return DB::transaction(function () use ($form, $additionalMediaIds): AssuranceForm {
            $form = AssuranceForm::query()->lockForUpdate()->findOrFail($form->getKey());
            $formDate = CarbonImmutable::createFromTimestamp((int) $form->date, 'Europe/Amsterdam')->format('Y-m-d');
            $samples = $this->samplesForDay($formDate);

            if ($samples->isEmpty()) {
                $form->delete();

                return $form;
            }

            $mediaIds = collect($additionalMediaIds)
                ->map(fn (mixed $mediaId): int => (int) $mediaId)
                ->filter(fn (int $mediaId): bool => $mediaId > 0)
                ->mapWithKeys(fn (int $mediaId): array => [$mediaId => true])
                ->all();
            $durations = [];

            foreach ($samples as $sample) {
                foreach ($sample->analyses as $analysis) {
                    $assay = $analysis->assayRecord;

                    if ($assay === null) {
                        continue;
                    }

                    foreach ($this->decodeMediaIds($assay->media_id) as $mediaId) {
                        $mediaIds[$mediaId] = true;
                    }

                    if ((string) $assay->duration !== '') {
                        $durations[(string) $assay->duration] = true;
                    }

                    if ((int) $analysis->conf_requested !== 1) {
                        continue;
                    }

                    if ($analysis->confirmationRecord === null) {
                        continue;
                    }

                    foreach ($this->confirmationMediaIds($analysis, $assay) as $mediaId) {
                        $mediaIds[$mediaId] = true;
                    }
                }
            }

            $media = $mediaIds === []
                ? collect()
                : Media::query()->whereIn('id', array_keys($mediaIds))->where('hasDate', 1)->get()->keyBy('id');
            $data = $form->decodedData();
            $data[3] = is_array($data[3] ?? null) ? $data[3] : [];
            $dynamicKeys = [];

            foreach ($media as $mediaItem) {
                $mediaId = (int) $mediaItem->id;
                $dynamicKeys[(string) $mediaId] = true;

                if (! array_key_exists($mediaId, $data[3])) {
                    $data[3][$mediaId] = '';
                }

                foreach ($this->supplements($mediaItem->supplements) as $supplement) {
                    $supplementId = (string) $supplement['supplementId'];
                    $fieldKey = 'extra_'.$mediaId.'_'.$supplementId;
                    $dynamicKeys[$fieldKey] = true;

                    if (! array_key_exists($fieldKey, $data[3])) {
                        $data[3][$fieldKey] = '';
                    }
                }
            }

            foreach (array_keys($data[3]) as $fieldKey) {
                if (! isset($dynamicKeys[(string) $fieldKey])) {
                    unset($data[3][$fieldKey]);
                }
            }

            foreach ($data[1] ?? [] as $duration => $fields) {
                if (! isset($durations[(string) $duration])) {
                    unset($data[1][$duration]);
                }
            }

            foreach ($data[5]['wasOutOfDateHere'] ?? [] as $mediaId => $sampleData) {
                if (! $media->has((int) $mediaId)) {
                    unset($data[5]['wasOutOfDateHere'][$mediaId]);
                }
            }

            $form->setDecodedData($data);
            $form->save();
            $this->recalculate->handle($form);

            return $form->fresh();
        });
    }

    public function hasSamplesForDay(string $formDate): bool
    {
        return $this->samplesForDay($formDate)->isNotEmpty();
    }

    private function decodeMediaIds(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (! is_array($value)) {
            return is_numeric($value) ? [(int) $value] : [];
        }

        return collect($value)
            ->map(fn (mixed $id): int => is_array($id) ? (int) ($id['mediaId'] ?? 0) : (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function confirmationMediaIds(SampleAnalysis $analysis, Assay $assay): array
    {
        $confirmation = $analysis->confirmationRecord;

        if ($confirmation === null) {
            return [];
        }

        $steps = AssayConfirmationConfiguration::decode($assay->confirmation_script);
        $support = AssayConfirmationConfiguration::decode($assay->confirmation_support);
        $racetrack = is_array($confirmation->racetrack) ? $confirmation->racetrack : [];
        $inUse = is_array($confirmation->in_use) ? $confirmation->in_use : [];
        $mediaIds = [];

        foreach ($this->confirmationScopes->scopes($analysis, $assay) as $scope) {
            if (! $scope['applicable']) {
                continue;
            }

            $df = (string) $scope['df'];
            $rep = (int) $scope['rep'];
            $scopeTrack = $racetrack[$df][(string) $rep] ?? $racetrack[$df][$rep] ?? [];

            foreach ($this->reachedStepIndices($steps, $scopeTrack) as $stepIndex) {
                $mediaId = (int) ($steps[$stepIndex]['mediaId'] ?? 0);

                if ($mediaId > 0) {
                    $mediaIds[$mediaId] = true;
                }
            }

            $scopeValues = $inUse[$df][(string) $rep] ?? $inUse[$df][$rep] ?? [];

            foreach ($support as $row) {
                $mediaId = (int) ($row['mediaId'] ?? 0);

                if ($mediaId > 0 && filter_var($scopeValues[(string) $mediaId] ?? $scopeValues[$mediaId] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $mediaIds[$mediaId] = true;
                }
            }
        }

        return array_keys($mediaIds);
    }

    /**
     * @param  array<int, array<string|int, mixed>>  $contenders
     * @return array<int, int>
     */
    private function reachedStepIndices(array $steps, array $contenders): array
    {
        $reached = [];

        foreach ($contenders as $contender) {
            foreach (array_values($steps) as $stepIndex => $step) {
                $reached[$stepIndex] = true;
                $answer = array_key_exists($stepIndex, $contender)
                    ? $contender[$stepIndex]
                    : ($contender[(string) $stepIndex] ?? '');

                if ($answer === null || $answer === '') {
                    break;
                }

                if ((string) ($step['disposition'] ?? '') !== '?' && $answer !== ($step['disposition'] ?? '')) {
                    break;
                }
            }
        }

        return array_keys($reached);
    }

    /**
     * @return array<int, array{supplementId: mixed, name: string}>
     */
    private function supplements(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        $supplements = [];

        foreach ($value as $supplement) {
            if (! is_array($supplement) || ! array_key_exists('supplementId', $supplement)) {
                continue;
            }

            $supplements[] = [
                'supplementId' => $supplement['supplementId'],
                'name' => (string) ($supplement['name'] ?? ''),
            ];
        }

        return $supplements;
    }

    private function samplesForDay(string $formDate): Collection
    {
        return $this->sampleQueryForDay($formDate)
            ->with(['analyses.assayRecord', 'analyses.confirmationRecord', 'analyses.results'])
            ->orderBy('id')
            ->get();
    }

    private function sampleQueryForDay(string $formDate): Builder
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $formDate, 'Europe/Amsterdam');
        $end = $start->addDay();
        $driver = DB::connection()->getDriverName();
        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');

        return Sample::query()->where(function (Builder $query) use ($driver, $start, $end, $startDate, $endDate): void {
            if ($driver === 'pgsql') {
                $query
                    ->where(function (Builder $query) use ($start, $end): void {
                        $query
                            ->whereRaw("sample_innoculated ~ '^[0-9]+$'")
                            ->whereRaw('CAST(sample_innoculated AS BIGINT) >= ?', [$start->timestamp])
                            ->whereRaw('CAST(sample_innoculated AS BIGINT) < ?', [$end->timestamp]);
                    })
                    ->orWhere(function (Builder $query) use ($startDate, $endDate): void {
                        $query
                            ->whereRaw("sample_innoculated ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}( [0-9]{2}:[0-9]{2}(:[0-9]{2})?)?$'")
                            ->where('sample_innoculated', '>=', $startDate)
                            ->where('sample_innoculated', '<', $endDate);
                    });

                return;
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $query
                    ->where(function (Builder $query) use ($start, $end): void {
                        $query
                            ->whereRaw("sample_innoculated REGEXP '^[0-9]+$'")
                            ->whereRaw('CAST(sample_innoculated AS SIGNED) >= ?', [$start->timestamp])
                            ->whereRaw('CAST(sample_innoculated AS SIGNED) < ?', [$end->timestamp]);
                    })
                    ->orWhere(function (Builder $query) use ($startDate, $endDate): void {
                        $query
                            ->whereRaw("sample_innoculated REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}( [0-9]{2}:[0-9]{2}(:[0-9]{2})?)?$'")
                            ->where('sample_innoculated', '>=', $startDate)
                            ->where('sample_innoculated', '<', $endDate);
                    });

                return;
            }

            $query
                ->where(function (Builder $query) use ($start, $end): void {
                    $query
                        ->whereRaw("sample_innoculated GLOB '[0-9]*'")
                        ->whereRaw('CAST(sample_innoculated AS INTEGER) >= ?', [$start->timestamp])
                        ->whereRaw('CAST(sample_innoculated AS INTEGER) < ?', [$end->timestamp]);
                })
                ->orWhere(function (Builder $query) use ($startDate, $endDate): void {
                    $query
                        ->where('sample_innoculated', '>=', $startDate)
                        ->where('sample_innoculated', '<', $endDate);
                });
        });
    }
}
