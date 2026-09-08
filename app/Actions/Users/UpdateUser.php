<?php

namespace App\Actions\Users;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    public function handle(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $userData = [
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'enabled' => $data['enabled'],
            ];

            if (! empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            $user->update($userData);
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                Arr::only($data, [
                    'username', 'title', 'first_name', 'last_name', 'gender', 'job_title',
                    'phone_number', 'locale', 'avatar_path', 'dashboard_layout',
                ]),
            );
            $user->syncRoles(Role::query()->whereKey($data['roles'] ?? [])->get());

            return $user->refresh();
        });
    }
}
