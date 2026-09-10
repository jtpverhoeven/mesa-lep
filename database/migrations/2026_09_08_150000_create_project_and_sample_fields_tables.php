<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projectfields', function (Blueprint $table) {
     
            $table->integer('id', autoIncrement: true);
            $table->string('name', 32);
            $table->string('alias', 64);
            $table->string('type', 10);
            $table->text('std_value');
            $table->integer('position');
            $table->integer('keep_current')->default(1);
            $table->softDeletes();
        });

        Schema::create('samplefields', function (Blueprint $table) {

            $table->integer('id', autoIncrement: true);
            $table->string('name', 32);
            $table->string('alias', 64);
            $table->string('type', 10);
            $table->text('std_value');
            $table->integer('position');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samplefields');
        Schema::dropIfExists('projectfields');
    }
};