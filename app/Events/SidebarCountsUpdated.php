<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class SidebarCountsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /** @param  array<string, int>  $counts */
    public function __construct(public array $counts) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('sidebar-counts')];
    }

    public function broadcastAs(): string
    {
        return 'sidebar.counts.updated';
    }

    public function broadcastWith(): array
    {
        return ['counts' => $this->counts];
    }
}
