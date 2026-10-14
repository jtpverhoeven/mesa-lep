<?php

namespace App\Actions\Printing;

use App\Jobs\SendLabelPrinterSpoolJob;
use App\Models\LabelDesign;
use App\Models\LabelEvent;
use App\Models\Printer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LogicException;

class SendLabelPrintPlan
{
    public function __construct(private GetLabelPrintingSettings $settings) {}

    /**
     * @param  list<array{event_id: int, print_label: int, print_to: int, copies: int, zpl: string}>  $plan
     * @param  array{user_id?: int|null, user_name?: string|null, profile_id?: int, sample_id?: int}  $activityContext
     * @return list<array{printer_id: int, status: string, bytes: int, event_ids: list<int>, error: string|null}>
     */
    public function handle(array $plan, array $activityContext = []): array
    {
        if ($plan === []) {
            return [];
        }

        $settings = $this->settings->handle();
        $enabled = $settings['enabled'];
        $prefix = $settings['alignment'];
        $maxBytes = (int) config('printing.max_spool_bytes', 16 * 1024 * 1024);
        $activityContext = array_replace([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
        ], $activityContext);
        $spools = [];

        foreach ($plan as $job) {
            if (! isset($job['event_id'], $job['print_to'], $job['copies'], $job['zpl']) || ! is_int($job['event_id']) || ! is_int($job['print_to']) || $job['print_to'] < 1 || ! is_int($job['copies']) || $job['copies'] < 1 || $job['copies'] > LabelEvent::MAX_COPIES || ! is_string($job['zpl']) || trim($job['zpl']) === '') {
                throw new InvalidArgumentException('Invalid label print job.');
            }

            $printerId = $job['print_to'];
            $spools[$printerId] ??= ['zpl' => '', 'event_ids' => [], 'labels' => []];
            $jobBytes = strlen($job['zpl']) * $job['copies'];

            if ($jobBytes > $maxBytes - strlen($prefix) - strlen($spools[$printerId]['zpl'])) {
                throw new InvalidArgumentException('Label spool exceeds the configured byte limit.');
            }

            $spools[$printerId]['zpl'] .= str_repeat($job['zpl'], $job['copies']);
            $spools[$printerId]['event_ids'][] = $job['event_id'];
            $labelId = $job['print_label'] ?? 0;
            $spools[$printerId]['labels'][$labelId] ??= ['label_id' => $labelId, 'copies' => 0];
            $spools[$printerId]['labels'][$labelId]['copies'] += $job['copies'];
        }

        $printers = Printer::query()->whereKey(array_keys($spools))->get()->keyBy('id');
        $labelNames = LabelDesign::query()->whereKey(array_column($plan, 'print_label'))->pluck('name', 'id');

        if ($enabled && $printers->count() !== count($spools)) {
            throw new LogicException('A printer in the label print plan no longer exists.');
        }

        $results = [];

        foreach ($spools as $printerId => $spool) {
            $result = ['printer_id' => $printerId, 'status' => 'disabled', 'bytes' => 0, 'event_ids' => array_values(array_unique($spool['event_ids'])), 'error' => null];
            $labels = array_map(fn (array $label): array => [...$label, 'label_name' => $labelNames->get($label['label_id'])], array_values($spool['labels']));
            $context = [
                ...$activityContext,
                'printer_id' => $printerId,
                'printer_name' => $printers->get($printerId)?->name,
                'event_ids' => $result['event_ids'],
                'labels' => $labels,
                'total_labels' => array_sum(array_column($labels, 'copies')),
            ];

            if ($enabled) {
                SendLabelPrinterSpoolJob::dispatch($printerId, $spool['zpl'], $result['event_ids'], $context);
                $result['status'] = 'queued';
                $result['bytes'] = strlen($prefix) + strlen($spool['zpl']);
            } else {
                Log::channel('printing_debug')->debug($prefix.$spool['zpl'], $context);
            }

            Log::channel('printing')->info('Label spool '.$result['status'].'.', [...$context, 'bytes' => $result['bytes']]);
            $results[] = $result;
        }

        return $results;
    }
}
