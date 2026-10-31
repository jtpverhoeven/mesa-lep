<?php

namespace App\Keyrings;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;
use Throwable;

class KeyringPresence
{
    public function __construct(private BroadcastManager $broadcast) {}

    /**
     * Ask Reverb whether the user is a member of the keyring presence channel.
     * Returns null when Reverb cannot answer, so callers fall back to the expiry backstop.
     */
    public function isPresent(KeyringType $type, int $typeId, int $userId): ?bool
    {
        $memberIds = $this->memberIds($type, $typeId);

        return $memberIds === null ? null : in_array($userId, $memberIds, true);
    }

    /**
     * @return list<int>|null
     */
    public function memberIds(KeyringType $type, int $typeId): ?array
    {
        $pusher = $this->client();

        if ($pusher === null) {
            return null;
        }

        try {
            $response = $pusher->get('/channels/presence-'.$type->channelName($typeId).'/users', [], true);
        } catch (Throwable $exception) {
            Log::warning('Keyring presence query failed.', ['channel' => $type->channelName($typeId), 'error' => $exception->getMessage()]);

            return null;
        }

        return collect($response['users'] ?? [])
            ->map(fn (array $user): int => (int) $user['id'])
            ->values()
            ->all();
    }

    private function client(): ?Pusher
    {
        $config = config('broadcasting.connections.'.config('keyrings.presence_connection'));

        if (! is_array($config) || ! in_array($config['driver'] ?? null, ['reverb', 'pusher'], true) || empty($config['key']) || empty($config['app_id'])) {
            return null;
        }

        $timeout = (float) config('keyrings.presence_timeout', 2);
        $config['options'] = [...($config['options'] ?? []), 'timeout' => $timeout];
        $config['client_options'] = [...($config['client_options'] ?? []), 'timeout' => $timeout, 'connect_timeout' => $timeout];

        return $this->broadcast->pusher($config);
    }
}
