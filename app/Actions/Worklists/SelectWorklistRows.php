<?php

namespace App\Actions\Worklists;

use App\Models\Assay;
use App\Models\Client;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\Worklist;
use Illuminate\Database\Eloquent\Builder;

class SelectWorklistRows
{
    /**
     * One row per sample and follow number that has one of the worklist's analyses and was inoculated in the window.
     *
     * @return list<array{sample_number: string, barcode: string, description: string, client: string, ticks: list<bool>}>
     */
    public function handle(Worklist $worklist, int $start, int $end): array
    {
        $originals = $this->originalIds($worklist->analyses ?? []);

        if ($originals === []) {
            return [];
        }

        $samples = Sample::query()
            ->where('sample_innoculated', '>=', (string) $start)
            ->where('sample_innoculated', '<', (string) $end);

        $matches = SampleAnalysis::query()
            ->whereIn('sample', $samples->clone()->select('id'))
            ->where(fn (Builder $query) => $query->whereIn('assay_base', $originals)->orWhereIn('original_assay_base', $originals))
            ->orderBy('id')
            ->get(['id', 'sample', 'follow_number']);

        if ($matches->isEmpty()) {
            return [];
        }

        $sampleRecords = $samples->clone()->whereKey($matches->pluck('sample')->unique())->get()->keyBy('id');
        $clients = Client::query()->whereKey($sampleRecords->pluck('client')->unique())->pluck('name', 'id');
        $analysesByFollow = SampleAnalysis::query()
            ->whereIn('sample', $sampleRecords->keys())
            ->get(['sample', 'follow_number', 'assay_base', 'original_assay_base'])
            ->groupBy(fn (SampleAnalysis $analysis): string => $analysis->sample.'.'.$analysis->follow_number);
        $columns = collect($worklist->columnList())
            ->map(fn (array $column): array => $this->originalIds($column['tick_on']))
            ->all();

        return $matches
            ->unique(fn (SampleAnalysis $match): string => $match->sample.'.'.$match->follow_number)
            ->map(function (SampleAnalysis $match) use ($sampleRecords, $clients, $analysesByFollow, $columns): array {
                $sample = $sampleRecords[$match->sample];
                $present = $analysesByFollow[$match->sample.'.'.$match->follow_number]
                    ->flatMap(fn (SampleAnalysis $analysis): array => [(int) $analysis->assay_base, (int) $analysis->original_assay_base]);

                return [
                    'sample_number' => (string) $sample->follow_no,
                    'barcode' => $sample->barcode.'.'.$match->follow_number.'.1',
                    'description' => (string) $sample->description,
                    'client' => $clients[$sample->client] ?? 'Fout: Klant ID bestaat niet (ID:'.$sample->client.')',
                    'ticks' => array_map(fn (array $originals): bool => $originals !== [] && $present->intersect($originals)->isNotEmpty(), $columns),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Worklists store the assay revision that was picked; samples may carry any revision of it.
     *
     * @param  list<int>  $assayIds
     * @return list<int>
     */
    private function originalIds(array $assayIds): array
    {
        if ($assayIds === []) {
            return [];
        }

        return Assay::query()->whereKey($assayIds)->pluck('original_id')->filter()->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }
}
