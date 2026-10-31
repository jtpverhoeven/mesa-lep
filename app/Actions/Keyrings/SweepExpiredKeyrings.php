<?php

namespace App\Actions\Keyrings;

use App\Events\KeyringChanged;
use App\Keyrings\KeyringPresence;
use App\Models\Keyring;
use Illuminate\Support\Facades\DB;

class SweepExpiredKeyrings
{
    public function __construct(private KeyringPresence $presence) {}

    /**
     * Backstop for keyrings past their expiry. Holders that Reverb still sees keep their keyring.
     *
     * @return array{released: int, extended: int}
     */
    public function handle(): array
    {
        $counts = ['released' => 0, 'extended' => 0];

        Keyring::query()
            ->where('expires', '<', now()->timestamp)
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $keyringId) use (&$counts): void {
                $outcome = DB::transaction(function () use ($keyringId): ?string {
                    $keyring = Keyring::query()->whereKey($keyringId)->lockForUpdate()->first();

                    if ($keyring === null || ! $keyring->isExpired()) {
                        return null;
                    }

                    if ($this->presence->isPresent($keyring->type, $keyring->type_id, $keyring->issued_to) === true) {
                        $keyring->update(['expires' => Keyring::nextExpiry()]);

                        return 'extended';
                    }

                    $keyring->delete();
                    KeyringChanged::dispatch($keyring->type, $keyring->type_id, null, 'expired', $keyring->issued_to);

                    return 'released';
                });

                if ($outcome !== null) {
                    $counts[$outcome]++;
                }
            });

        return $counts;
    }
}
