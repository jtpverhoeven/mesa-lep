<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('changetracker', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('user_id');
            $table->string('timestamp', 64);
            $table->string('type', 32);
            $table->integer('assurance_form')->nullable();
            $table->integer('project')->nullable();
            $table->integer('sample')->nullable();
            $table->integer('said')->nullable();
            $table->text('event')->nullable();
            $table->string('from', 256)->nullable();
            $table->string('to', 256)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('changetracker');
    }
};
