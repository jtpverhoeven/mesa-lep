<?php

namespace App\Events;

use App\Keyrings\KeyringType;
use App\Models\Keyring;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class KeyringChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array{holder_id: int, name: string|null, avatar_url: string|null, lock_id: int, expires_at: string}|null  $holder
     */
    public function __construct(
        public KeyringType $type,
        public int $typeId,
        public ?array $holder,
        public string $reason,
        public ?int $previousHolderId = null,
    ) {}

    public static function for(KeyringType $type, int $typeId, ?Keyring $keyring, string $reason, ?int $previousHolderId = null): self
    {
        return new self($type, $typeId, $keyring?->holderPayload(), $reason, $previousHolderId);
    }

    public function broadcastOn(): array
    {
        return [new PresenceChannel($this->type->channelName($this->typeId))];
    }

    public function broadcastAs(): string
    {
        return 'keyring.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type->slug(),
            'type_id' => $this->typeId,
            'holder' => $this->holder,
            'reason' => $this->reason,
            'previous_holder_id' => $this->previousHolderId,
        ];
    }
}
