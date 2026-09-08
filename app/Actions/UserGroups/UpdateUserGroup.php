<?php

namespace App\Actions\UserGroups;

use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class UpdateUserGroup
{
    public function handle(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $userIds = $data['users'] ?? [];
            $leaderId = $data['groupLeader'] ?? null;
            $isSuperGroup = (bool) ($data['superGroup'] ?? false);
            $permissionNames = $isSuperGroup ? PermissionCatalog::names() : ($data['permissions'] ?? []);

            $role->update([
                'name' => $data['name'],
                'groupLeader' => in_array($leaderId, $userIds) ? $leaderId : null,
                'superGroup' => $isSuperGroup,
            ]);
            $role->users()->sync($userIds);

            $permissions = collect($permissionNames)->map(
                fn (string $name) => Permission::findOrCreate($name, 'web')
            );
            $role->syncPermissions($permissions);

            return $role->refresh();
        });
    }
}