<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('cvars')->where('cvar', 'SAMPLE_PHOTO_MAX_EDGE')->exists()) {
            DB::table('cvars')->insert([
                'cvar' => 'SAMPLE_PHOTO_MAX_EDGE',
                'value' => null,
                'default' => '2048',
                'description' => 'Maximale breedte of hoogte van een monsterfoto in pixels (256-8192).',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('cvars')->where('cvar', 'SAMPLE_PHOTO_MAX_EDGE')->delete();
    }
};
