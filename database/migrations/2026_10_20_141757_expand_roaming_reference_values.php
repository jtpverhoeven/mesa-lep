<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roaminganalysis', function (Blueprint $table) {
            $table->text('reference')->change();
        });
    }

    public function down(): void
    {
        if (DB::table('roaminganalysis')->whereRaw('length(reference) > 32')->exists()) {
            throw new RuntimeException('Cannot narrow roaming reference values without losing data.');
        }

        Schema::table('roaminganalysis', function (Blueprint $table) {
            $table->string('reference', 32)->change();
        });
    }
};
