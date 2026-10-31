<?php

namespace App\Actions\Keyrings;

use App\ChangeTracking\ChangeTracker;
use App\Events\KeyringChanged;
use App\Keyrings\KeyringType;
use App\Models\Keyring;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StealKeyring
{
    public const CHANGE_TYPE = 28;

    public function __construct(
        private AcquireKeyring $acquire,
        private GetKeyringStatus $status,
        private ChangeTracker $changeTracker,
    ) {}

    /**
     * @return array{locked: bool, lock_placed: bool, lock_id: int|null, locked_by_id: int|null, locked_by_name: string|null, locked_by_avatar: string|null, expires_at: string|null}
     */
    public function handle(KeyringType $type, int $typeId, User $actor): array
    {
        $keyring = DB::transaction(function () use ($type, $typeId, $actor): ?Keyring {
            $keyring = Keyring::query()->forResource($type, $typeId)->with('holder')->lockForUpdate()->first();

            if ($keyring === null || $keyring->issued_to === $actor->id) {
                return null;
            }

            $victim = $keyring->holder;
            $previousHolderId = $keyring->issued_to;
            $swapped = Keyring::query()
                ->whereKey($keyring->id)
                ->where('issued_to', $previousHolderId)
                ->update([
                    'issued' => now()->timestamp,
                    'issued_to' => $actor->id,
                    'expires' => Keyring::nextExpiry(),
                ]);

            $keyring = Keyring::query()->with('holder.profile')->findOrFail($keyring->id);

            if ($swapped === 0) {
                return $keyring;
            }

            $this->changeTracker->changed(
                self::CHANGE_TYPE,
                project: $type === KeyringType::Sample ? null : $typeId,
                sample: $type === KeyringType::Sample ? $typeId : null,
                event: 'Vergrendeling ('.$type->slug().') overgenomen',
                from: $victim?->name ?? (string) $previousHolderId,
                to: $actor->name,
                userId: $actor->id,
            );
            KeyringChanged::dispatch($type, $typeId, $keyring->holderPayload(), 'stolen', $previousHolderId);

            return $keyring;
        });

        return $keyring === null
            ? $this->acquire->handle($type, $typeId, $actor)
            : $this->status->present($keyring, $actor);
    }
}
