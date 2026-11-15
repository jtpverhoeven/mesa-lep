<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('cvars')->where('cvar', 'RODAC_ASSAYS_FOR_BILLING')->exists()) {
            DB::table('cvars')->insert([
                'cvar' => 'RODAC_ASSAYS_FOR_BILLING',
                'value' => null,
                'default' => '[]',
                'description' => 'JSON-lijst met originele assay-ID\'s; normale monsters met uitsluitend deze analyses worden bij facturatie als RODAC geteld.',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('cvars')->where('cvar', 'RODAC_ASSAYS_FOR_BILLING')->delete();
    }
};
