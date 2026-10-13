<?php

namespace App\Jobs;

use App\Actions\Printing\GetLabelPrintingSettings;
use App\Models\Printer;
use App\Support\Printing\LabelPrinterTransport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SendLabelPrinterSpoolJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public int $timeout;

    /**
     * @param  list<int>  $eventIds
     */
    public function __construct(
        public int $printerId,
        public string $zpl,
        public array $eventIds,
    ) {
        $this->timeout = (int) ceil(2 * (float) config('printing.timeout_seconds', 5))
            + (int) config('printing.lock_wait_seconds', 10) + 5;
        $this->onConnection(config('printing.connection', 'database'));
        $this->onQueue(config('printing.queue', 'printing'));
        $this->afterCommit();
    }

    public function handle(LabelPrinterTransport $transport, GetLabelPrintingSettings $settings): void
    {
        Cache::lock('label-printer:'.$this->printerId, $this->timeout + 5)
            ->block((int) config('printing.lock_wait_seconds', 10), function () use ($transport, $settings): void {
                $currentSettings = $settings->handle();
                $context = ['printer_id' => $this->printerId, 'event_ids' => $this->eventIds];

                if (! $currentSettings['enabled']) {
                    Log::info('Label spool disabled.', $context);

                    return;
                }

                $zpl = $currentSettings['alignment'].$this->zpl;

                if (strlen($zpl) > (int) config('printing.max_spool_bytes', 16 * 1024 * 1024)) {
                    throw new InvalidArgumentException('Label spool exceeds the configured byte limit.');
                }

                $printer = Printer::query()->findOrFail($this->printerId);
                $transport->send($printer, $zpl);
                Log::info('Label spool sent.', [...$context, 'bytes' => strlen($zpl)]);
            });
    }
}
