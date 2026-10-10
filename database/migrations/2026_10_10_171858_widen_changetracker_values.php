<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('changetracker', function (Blueprint $table) {
            $table->text('from')->nullable()->change();
            $table->text('to')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('changetracker')->whereRaw('length("from") > 256 OR length("to") > 256')->exists()) {
            throw new RuntimeException('Cannot narrow audit values without losing revision history.');
        }

        Schema::table('changetracker', function (Blueprint $table) {
            $table->string('from', 256)->nullable()->change();
            $table->string('to', 256)->nullable()->change();
        });
    }
};
