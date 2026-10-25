<?php

namespace Database\Seeders;

use App\Models\DocgenTemplate;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ReportConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        DocgenTemplate::query()->firstOrCreate(
            ['folder' => 'maz2020'],
            ['name' => 'MAZ 2020', 'active' => 1, 'view_order' => 1],
        );
        Permission::findOrCreate('reports.generate', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
