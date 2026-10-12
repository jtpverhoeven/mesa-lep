<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printers', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->string('name', 128);
            $table->string('adres', 45);
            $table->integer('port');
            $table->integer('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printers');
    }
};
