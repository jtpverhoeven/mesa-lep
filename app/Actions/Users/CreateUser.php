<?php

namespace App\Actions\Users;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateUser
{
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'alias' => $data['alias'] ?? null,
                'password' => $data['password'],
                'enabled' => $data['enabled'],
            ]);

            $user->profile()->create(Arr::only($data, [
                'title', 'first_name', 'last_name', 'gender', 'job_title',
                'phone_number', 'locale', 'avatar_path', 'dashboard_layout',
            ]));
            $user->syncRoles(Role::query()->whereKey($data['roles'] ?? [])->get());

            return $user;
        });
    }
}
