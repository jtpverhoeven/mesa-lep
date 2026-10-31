<?php

namespace App\Actions\Keyrings;

use App\ChangeTracking\ChangeTracker;
use App\Events\KeyringChanged;
use App\Keyrings\KeyringType;
use App\Models\Keyring;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ForceReleaseKeyring
{
    public function __construct(private ChangeTracker $changeTracker) {}

    public function handle(Keyring $keyring, User $actor): void
    {
        DB::transaction(function () use ($keyring, $actor): void {
            $lockedKeyring = Keyring::query()
                ->with('holder:id,name')
                ->lockForUpdate()
                ->findOrFail($keyring->getKey());
            $type = $lockedKeyring->type;
            $previousHolderId = $lockedKeyring->issued_to;

            $this->changeTracker->changed(
                StealKeyring::CHANGE_TYPE,
                project: $type === KeyringType::Sample ? null : $lockedKeyring->type_id,
                sample: $type === KeyringType::Sample ? $lockedKeyring->type_id : null,
                event: 'Vergrendeling ('.$type->slug().') geforceerd verwijderd',
                from: $lockedKeyring->holder?->name ?? (string) $previousHolderId,
                to: $actor->name,
                userId: $actor->id,
            );

            $lockedKeyring->delete();

            KeyringChanged::dispatch($type, $lockedKeyring->type_id, null, 'force_deleted', $previousHolderId);
        });
    }
}
