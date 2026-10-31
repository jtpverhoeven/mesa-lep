<?php

namespace App\Actions\Keyrings;

use App\Keyrings\KeyringType;
use App\Models\Keyring;
use App\Models\User;

class GetKeyringStatus
{
    /**
     * @return array{locked: bool, lock_placed: bool, lock_id: int|null, locked_by_id: int|null, locked_by_name: string|null, locked_by_avatar: string|null, expires_at: string|null}
     */
    public function handle(KeyringType $type, int $typeId, ?User $viewer): array
    {
        return $this->present(
            Keyring::query()->forResource($type, $typeId)->with('holder.profile')->first(),
            $viewer,
        );
    }

    /**
     * @return array{locked: bool, lock_placed: bool, lock_id: int|null, locked_by_id: int|null, locked_by_name: string|null, locked_by_avatar: string|null, expires_at: string|null}
     */
    public function present(?Keyring $keyring, ?User $viewer): array
    {
        if ($keyring === null) {
            return [
                'locked' => false,
                'lock_placed' => false,
                'lock_id' => null,
                'locked_by_id' => null,
                'locked_by_name' => null,
                'locked_by_avatar' => null,
                'expires_at' => null,
            ];
        }

        $holder = $keyring->holderPayload();
        $heldByViewer = $viewer !== null && $keyring->issued_to === $viewer->id;

        return [
            'locked' => ! $heldByViewer,
            'lock_placed' => $heldByViewer,
            'lock_id' => $keyring->id,
            'locked_by_id' => $holder['holder_id'],
            'locked_by_name' => $holder['name'],
            'locked_by_avatar' => $holder['avatar_url'],
            'expires_at' => $holder['expires_at'],
        ];
    }
}
