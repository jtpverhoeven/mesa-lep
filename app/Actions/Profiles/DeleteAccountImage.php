<?php

namespace App\Actions\Profiles;

use App\Models\User;
use App\Support\AccountImages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeleteAccountImage
{
    public function handle(User $user, string $kind): void
    {
        DB::transaction(function () use ($user, $kind): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $user->load('profile');
            $path = AccountImages::path($user, $kind);

            if ($path && ! Storage::disk('local')->delete($path)) {
                throw new RuntimeException('Account image could not be deleted.');
            }

            if ($kind === 'avatar') {
                $user->profile?->update(['avatar_path' => null]);
            }
        });
    }
}
