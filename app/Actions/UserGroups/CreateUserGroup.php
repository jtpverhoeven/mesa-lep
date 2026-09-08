<?php

namespace App\Actions\UserGroups;

use App\Models\Role;

class CreateUserGroup
{
    public function __construct(private UpdateUserGroup $updateUserGroup) {}

    public function handle(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        return $this->updateUserGroup->handle($role, $data);
    }
}