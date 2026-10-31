<?php

namespace App\Actions\Keyrings;

use App\Events\KeyringChanged;
use App\Models\Keyring;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReleaseKeyringById
{
    /**
     * Release a keyring owned by the user. Keyrings of other users are left untouched.
     */
    public function handle(int $keyringId, User $user, string $reason = 'released'): bool
    {
        return DB::transaction(function () use ($keyringId, $user, $reason): bool {
            $keyring = Keyring::query()->whereKey($keyringId)->where('issued_to', $user->id)->lockForUpdate()->first();

            if ($keyring === null) {
                return false;
            }

            $keyring->delete();
            KeyringChanged::dispatch($keyring->type, $keyring->type_id, null, $reason, $user->id);

            return true;
        });
    }
}
