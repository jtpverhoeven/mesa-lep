<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matrix', function (Blueprint $table): void {
            $table->integer('active')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('matrix', function (Blueprint $table): void {
            $table->dropColumn('active');
        });
    }
};