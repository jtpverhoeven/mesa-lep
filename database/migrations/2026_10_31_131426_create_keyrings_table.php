<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keyrings', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->string('type', 32);
            $table->integer('type_id');
            $table->integer('issued');
            $table->integer('issued_to')->index();
            $table->integer('expires');
            $table->unique(['type', 'type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keyrings');
    }
};
