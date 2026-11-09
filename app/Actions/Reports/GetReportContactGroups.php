<?php

namespace App\Actions\Reports;

use App\ClientPortal\ClientPortalService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

class GetReportContactGroups
{
    public function __construct(private ClientPortalService $portal) {}

    /**
     * @param  list<int|string>  $preselected
     * @return array{groups: list<array{id: string, name: string, members: list<array{name: string, email: string}>, checked: bool}>, error: ?string}
     */
    public function handle(int $clientId, array $preselected = []): array
    {
        try {
            $response = $this->portal->getContactLists($clientId);
            $response->throw();
            $payload = $response->json();

            if (! is_array($payload)) {
                throw new UnexpectedValueException('The portal returned invalid contact groups.');
            }
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            report($exception);
            Log::warning('Contact groups could not be loaded.', ['client_id' => $clientId]);

            return ['groups' => [], 'error' => 'Contactgroepen konden niet worden geladen. U kunt wel een extra geadresseerde invullen.'];
        }

        $preselected = array_map('strval', $preselected);
        $groups = array_values(array_filter($payload, fn (mixed $group): bool => is_array($group) && isset($group['id']) && is_scalar($group['id'])));

        return [
            'groups' => array_map(fn (array $group): array => [
                'id' => (string) $group['id'],
                'name' => (string) ($group['name'] ?? ''),
                'members' => $this->members($group['members'] ?? []),
                'checked' => count($groups) === 1 || in_array((string) $group['id'], $preselected, true),
            ], $groups),
            'error' => null,
        ];
    }

    /** @return list<array{name: string, email: string}> */
    private function members(mixed $members): array
    {
        if (! is_array($members)) {
            return [];
        }

        return array_values(array_map(
            fn (array $member): array => ['name' => (string) ($member['name'] ?? ''), 'email' => (string) ($member['email'] ?? '')],
            array_filter($members, 'is_array'),
        ));
    }
}
