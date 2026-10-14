<?php

namespace App\Actions\Printing;

use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\SampleBuffer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DispatchLabelEvents
{
    public function __construct(private BuildLabelPrintPlan $buildLabelPrintPlan, private SendLabelPrintPlan $sendLabelPrintPlan) {}

    /**
     * @param  iterable<Sample|SampleAnalysis|SampleBuffer>  $records
     * @return list<array{printer_id: int, status: string, bytes: int, event_ids: list<int>, error: string|null}>
     */
    public function handle(iterable $records, ?int $printerOverride = null): array
    {
        $printedAt = now();
        $currentUser = Auth::user()?->name ?? '';
        $plan = [];
        $ruleHits = [];

        foreach ($records as $record) {
            $labels = match (true) {
                $record instanceof Sample => $this->buildLabelPrintPlan->forSampleRegistration($record, $printerOverride, $printedAt, currentUser: $currentUser),
                $record instanceof SampleAnalysis => $this->buildLabelPrintPlan->forAnalysisAddition($record, $printerOverride, $printedAt, currentUser: $currentUser),
                $record instanceof SampleBuffer => $this->buildLabelPrintPlan->forThtApproval($record, $printerOverride, $printedAt, currentUser: $currentUser),
            };

            array_push($plan, ...$labels);

            if ($labels !== []) {
                $ruleHits[] = [
                    ...match (true) {
                        $record instanceof Sample => ['event_group' => 1, 'sample_id' => $record->id, 'sample_barcode' => $record->barcode],
                        $record instanceof SampleAnalysis => ['event_group' => 2, 'sample_id' => $record->sample, 'sample_analysis_id' => $record->id, 'sample_barcode' => $record->sampleRecord?->barcode],
                        $record instanceof SampleBuffer => ['event_group' => 3, 'sample_buffer_id' => $record->id, 'tht_code' => $record->tht_code],
                    },
                    'event_ids' => array_values(array_unique(array_column($labels, 'event_id'))),
                    'label_ids' => array_values(array_unique(array_column($labels, 'print_label'))),
                    'printer_ids' => array_values(array_unique(array_column($labels, 'print_to'))),
                ];
            }
        }

        $spools = $this->sendLabelPrintPlan->handle($plan);

        foreach ($ruleHits as $hit) {
            Log::channel('printing')->info('Hit label event rules: '.implode(', ', $hit['event_ids']).'.', [
                ...$hit,
                'user_id' => Auth::id(),
                'user_name' => $currentUser,
            ]);
        }

        return $spools;
    }
}
