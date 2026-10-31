<?php

namespace App\Actions\Keyrings;

use App\Events\KeyringChanged;
use App\Keyrings\KeyringPresence;
use App\Keyrings\KeyringType;
use App\Models\Keyring;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class AcquireKeyring
{
    public function __construct(
        private KeyringPresence $presence,
        private GetKeyringStatus $status,
    ) {}

    /**
     * @return array{locked: bool, lock_placed: bool, lock_id: int|null, locked_by_id: int|null, locked_by_name: string|null, locked_by_avatar: string|null, expires_at: string|null}
     */
    public function handle(KeyringType $type, int $typeId, User $user): array
    {
        return $this->status->present($this->claim($type, $typeId, $user), $user);
    }

    /**
     * Take the keyring when it is free, refresh it when owned, and apply the takeover rule when another user holds it.
     */
    public function claim(KeyringType $type, int $typeId, User $user): Keyring
    {
        try {
            $keyring = DB::transaction(fn (): Keyring => $this->attempt($type, $typeId, $user));
        } catch (UniqueConstraintViolationException) {
            $keyring = DB::transaction(fn (): Keyring => $this->attempt($type, $typeId, $user));
        }

        return $keyring->loadMissing('holder.profile');
    }

    private function attempt(KeyringType $type, int $typeId, User $user): Keyring
    {
        $keyring = Keyring::query()->forResource($type, $typeId)->lockForUpdate()->first();

        if ($keyring === null) {
            $keyring = Keyring::query()->create([
                'type' => $type,
                'type_id' => $typeId,
                'issued' => now()->timestamp,
                'issued_to' => $user->id,
                'expires' => Keyring::nextExpiry(),
            ]);
            KeyringChanged::dispatch($type, $typeId, $keyring->holderPayload(), 'acquired', null);

            return $keyring;
        }

        if ($keyring->issued_to === $user->id) {
            $keyring->update(['expires' => Keyring::nextExpiry()]);

            return $keyring;
        }

        $holderPresent = $this->presence->isPresent($type, $typeId, $keyring->issued_to);

        if ($holderPresent === true) {
            $keyring->update(['expires' => Keyring::nextExpiry()]);

            return $keyring;
        }

        if ($holderPresent === null && ! $keyring->isExpired()) {
            return $keyring;
        }

        $previousHolderId = $keyring->issued_to;
        $keyring->update([
            'issued' => now()->timestamp,
            'issued_to' => $user->id,
            'expires' => Keyring::nextExpiry(),
        ]);
        $keyring->unsetRelation('holder');
        KeyringChanged::dispatch($type, $typeId, $keyring->holderPayload(), $holderPresent === false ? 'abandoned' : 'expired', $previousHolderId);

        return $keyring;
    }
}
