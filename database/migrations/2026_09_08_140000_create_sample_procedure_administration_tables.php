<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sampleprocedurefields', function (Blueprint $table) {

            $table->integer('id', autoIncrement: true);
            $table->string('name', 32);
            $table->string('alias', 128);
            $table->integer('position');
        });

        Schema::create('sampleprocedures', function (Blueprint $table) {
     
            $table->integer('id', autoIncrement: true);
            $table->string('name', 128);
            $table->integer('active')->default(1);
            $table->integer('hide')->default(0);
            $table->text('fields');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sampleprocedures');
        Schema::dropIfExists('sampleprocedurefields');
    }
};