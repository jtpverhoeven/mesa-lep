<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('cvars')->where('cvar', 'GENERIC_LEGIONELLA_LABEL_ID')->exists()) {
            DB::table('cvars')->insert([
                'cvar' => 'GENERIC_LEGIONELLA_LABEL_ID',
                'value' => null,
                'default' => '0',
                'description' => 'Labelontwerp-ID voor generieke legionellastickers (Matrix A, B en C).',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('cvars')->where('cvar', 'GENERIC_LEGIONELLA_LABEL_ID')->delete();
    }
};
