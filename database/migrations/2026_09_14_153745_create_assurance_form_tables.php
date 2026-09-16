<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assuranceforms', function (Blueprint $table): void {
            $table->integer('id', autoIncrement: true);
            $table->string('date', 32);
            $table->text('data')->nullable();
            $table->integer('is_complete')->default(0);
            $table->index('date', 'idx_assuranceforms_date');
            $table->index('is_complete', 'idx_assuranceforms_is_complete');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assuranceforms');
    }
};
