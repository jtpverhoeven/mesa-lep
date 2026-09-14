<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConfirmationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $sampleId,
        public int $analysisId,
        public string $revision,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("samples.{$this->sampleId}")];
    }

    public function broadcastAs(): string
    {
        return 'analysis.confirmation.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'sample_id' => $this->sampleId,
            'analysis_id' => $this->analysisId,
            'revision' => $this->revision,
        ];
    }
}
