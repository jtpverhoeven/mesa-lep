<?php

namespace App\Events;

use App\Keyrings\KeyringType;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class ProjectEditabilityChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public int $projectId,
        public bool $readOnly,
        public array $reasons,
        public ?string $lockMessage = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel(KeyringType::Project->channelName($this->projectId))];
    }

    public function broadcastAs(): string
    {
        return 'project.editability.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'project_id' => $this->projectId,
            'read_only' => $this->readOnly,
            'reasons' => $this->reasons,
            'lock_message' => $this->lockMessage,
        ];
    }
}
