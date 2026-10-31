<?php

namespace App\Actions\Keyrings;

use App\Models\Keyring;
use App\Models\User;

class ReleaseUserKeyrings
{
    public function __construct(private ReleaseKeyringById $release) {}

    public function handle(User $user): int
    {
        return Keyring::query()
            ->where('issued_to', $user->id)
            ->pluck('id')
            ->filter(fn (int $keyringId): bool => $this->release->handle($keyringId, $user))
            ->count();
    }
}
