<?php

namespace App\Actions\Profiles;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateAccountProfile
{
    /** @param array{title?: ?string, first_name: string, last_name: string, gender?: ?string, job_title?: ?string, phone_number?: ?string} $data */
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $user->update(['name' => trim($data['first_name'].' '.$data['last_name'])]);
            $this->ensureProfile($user)->fill($data)->save();
        });
    }

    public function ensureProfile(User $user): Profile
    {
        [$firstName, $lastName] = array_pad(explode(' ', trim($user->name), 2), 2, '');

        return $user->profile()->firstOrCreate([], [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }
}
