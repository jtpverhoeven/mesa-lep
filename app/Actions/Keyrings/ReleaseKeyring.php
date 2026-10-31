<?php

namespace App\Actions\Keyrings;

use App\Keyrings\KeyringType;
use App\Models\Keyring;
use App\Models\User;

class ReleaseKeyring
{
    public function __construct(private ReleaseKeyringById $release) {}

    public function handle(KeyringType $type, int $typeId, User $user): bool
    {
        $keyringId = Keyring::query()->forResource($type, $typeId)->where('issued_to', $user->id)->value('id');

        return $keyringId !== null && $this->release->handle((int) $keyringId, $user);
    }
}
