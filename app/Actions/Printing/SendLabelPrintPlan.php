<?php

namespace App\Actions\Printing;

use App\Models\Cvar;
use App\Models\LabelEvent;
use App\Models\Printer;
use App\Support\Printing\LabelPrinterTransport;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LogicException;
use Throwable;

class SendLabelPrintPlan
{
    public function __construct(private LabelPrinterTransport $transport) {}

    /**
     * @param  list<array{event_id: int, print_label: int, print_to: int, copies: int, zpl: string}>  $plan
     * @return list<array{printer_id: int, status: string, bytes: int, event_ids: list<int>, error: string|null}>
     */
    public function handle(array $plan): array
    {
        if ($plan === []) {
            return [];
        }

        $disabledName = config('printing.disabled_cvar', 'MESA_DISABLE_PRINTING');
        $alignmentName = config('printing.alignment_cvar', 'MESA_ZEBRA_ALIGN');
        $settings = Cvar::query()->whereIn('cvar', [$disabledName, $alignmentName])->get()->keyBy('cvar');
        $disabled = $settings->get($disabledName);
        $enabled = (string) ($disabled?->value ?? $disabled?->default ?? '0') !== '1';
        $alignment = $settings->get($alignmentName);
        $prefix = (string) ($alignment?->value ?? $alignment?->default ?? '');
        $maxBytes = (int) config('printing.max_spool_bytes', 16 * 1024 * 1024);
        $spools = [];

        foreach ($plan as $job) {
            if (! isset($job['event_id'], $job['print_to'], $job['copies'], $job['zpl']) || ! is_int($job['event_id']) || ! is_int($job['print_to']) || $job['print_to'] < 1 || ! is_int($job['copies']) || $job['copies'] < 1 || $job['copies'] > LabelEvent::MAX_COPIES || ! is_string($job['zpl']) || trim($job['zpl']) === '') {
                throw new InvalidArgumentException('Invalid label print job.');
            }

            $printerId = $job['print_to'];
            $spools[$printerId] ??= ['zpl' => $prefix, 'event_ids' => []];
            $jobBytes = strlen($job['zpl']) * $job['copies'];

            if ($jobBytes > $maxBytes - strlen($spools[$printerId]['zpl'])) {
                throw new InvalidArgumentException('Label spool exceeds the configured byte limit.');
            }

            $spools[$printerId]['zpl'] .= str_repeat($job['zpl'], $job['copies']);
            $spools[$printerId]['event_ids'][] = $job['event_id'];
        }

        $printers = $enabled ? Printer::query()->whereKey(array_keys($spools))->get()->keyBy('id') : collect();

        if ($enabled && $printers->count() !== count($spools)) {
            throw new LogicException('A printer in the label print plan no longer exists.');
        }

        $results = [];

        foreach ($spools as $printerId => $spool) {
            $result = ['printer_id' => $printerId, 'status' => 'disabled', 'bytes' => 0, 'event_ids' => array_values(array_unique($spool['event_ids'])), 'error' => null];

            if ($enabled) {
                try {
                    $this->transport->send($printers->get($printerId), $spool['zpl']);
                    $result['status'] = 'sent';
                    $result['bytes'] = strlen($spool['zpl']);
                } catch (Throwable $exception) {
                    $result['status'] = 'failed';
                    $result['error'] = $exception->getMessage();
                    Log::error('Label spool failed.', $result);
                }
            }

            if ($result['status'] !== 'failed') {
                Log::info('Label spool '.$result['status'].'.', $result);
            }

            $results[] = $result;
        }

        return $results;
    }
}
