<?php

namespace App\Actions\Reports;

use App\ClientPortal\ClientPortalService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class ResolveContactGroupNames
{
    /** @var array<int, array<string, string>> */
    private array $namesByClient = [];

    public function __construct(private ClientPortalService $portal) {}

    /**
     * Names of the selected groups joined with `;` (unknown ids give an empty name); null without receivers.
     *
     * @param  list<int|string>  $receivers
     */
    public function handle(int $clientId, array $receivers): ?string
    {
        if ($receivers === []) {
            return null;
        }

        $names = $this->namesByClient[$clientId] ??= $this->fetch($clientId);

        return implode(';', array_map(fn (int|string $id): string => $names[(string) $id] ?? '', $receivers));
    }

    /** @return array<string, string> */
    private function fetch(int $clientId): array
    {
        try {
            $payload = $this->portal->getContactLists($clientId)->throw()->json();
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            return [];
        }

        $names = [];

        foreach (is_array($payload) ? $payload : [] as $group) {
            if (is_array($group) && isset($group['id']) && is_scalar($group['id'])) {
                $names[(string) $group['id']] = (string) ($group['name'] ?? '');
            }
        }

        return $names;
    }
}
