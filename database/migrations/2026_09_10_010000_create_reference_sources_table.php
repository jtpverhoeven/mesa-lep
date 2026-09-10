<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referencesources', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->text('name')->nullable();
            $table->integer('client')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referencesources');
    }
};