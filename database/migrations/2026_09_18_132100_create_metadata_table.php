<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metadata', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('sample');
            $table->string('name', 128);
            $table->text('value');
            $table->integer('meta_data_key_id')->nullable();
            $table->integer('meta_order')->nullable();
            $table->index('sample', 'idx_metadata_sample');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metadata');
    }
};
