<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labeldesigns', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->string('name', 128);
            $table->text('zpl');
            $table->integer('default_sample')->default(0);
            $table->integer('default_analysis')->default(0);
            $table->integer('default_printer')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labeldesigns');
    }
};
