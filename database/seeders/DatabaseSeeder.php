<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $administratorRole = Role::findOrCreate('administrator', 'web');
        $administratorRole->update(['superGroup' => true]);
        $administratorRole->syncPermissions(
            collect(PermissionCatalog::names())->map(fn ($name) => Permission::findOrCreate($name, 'web')),
        );

        $administrator = User::updateOrCreate(
            ['email' => 'jverhoeven@mun.ca'],
            [
                'name' => 'Joost Verhoeven',
                'password' => 'check',
                'enabled' => true,
            ],
        );

        $administrator->profile()->updateOrCreate([], [
            'username' => 'admin',
            'first_name' => 'Joost',
            'last_name' => 'Verhoeven',
            'job_title' => 'Coder',
            'locale' => 'nl',
        ]);
        $administrator->syncRoles($administratorRole);
    }
}
