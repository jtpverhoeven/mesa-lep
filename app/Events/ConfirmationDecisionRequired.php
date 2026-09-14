<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConfirmationDecisionRequired implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $sampleId,
        public int $analysisId,
        public string $assayName,
        public string $mode,
        public string $message = 'Een bevestigingsbeslissing is nodig.',
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("samples.{$this->sampleId}")];
    }

    public function broadcastAs(): string
    {
        return 'analysis.confirmation.decision-required';
    }

    public function broadcastWith(): array
    {
        return [
            'sample_id' => $this->sampleId,
            'analysis_id' => $this->analysisId,
            'assay_name' => $this->assayName,
            'mode' => $this->mode,
            'message' => $this->message,
        ];
    }
}
