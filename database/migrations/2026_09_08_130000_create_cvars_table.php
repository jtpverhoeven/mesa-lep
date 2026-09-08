<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cvars', function (Blueprint $table) {
            $table->id();
            $table->string('cvar', 128);
            $table->string('value', 1024)->nullable();
            $table->string('default', 128);
            $table->text('description');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cvars');
    }
};