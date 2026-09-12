<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalysisResultCalculated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $sampleId,
        public int $analysisId,
        public array $calculation,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("samples.{$this->sampleId}")];
    }

    public function broadcastAs(): string
    {
        return 'analysis.result.calculated';
    }

    public function broadcastWith(): array
    {
        return [
            'sample_id' => $this->sampleId,
            'analysis_id' => $this->analysisId,
            'calculation' => $this->calculation,
            'is_ready' => (bool) ($this->calculation['isReady'] ?? false),
        ];
    }
}
