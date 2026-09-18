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
        Schema::create('portal', function (Blueprint $table) {
            $table->id();
            $table->string('pk', 128)->unique();
            $table->text('pv');
        });

        DB::table('portal')->insert([
            ['pk' => 'acceptType', 'pv' => 'application/json'],
            ['pk' => 'bearer', 'pv' => ''],
            ['pk' => 'lastSync', 'pv' => ''],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal');
    }
};
