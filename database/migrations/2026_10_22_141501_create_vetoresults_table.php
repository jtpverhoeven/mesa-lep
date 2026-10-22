<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vetoresults', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('said')->index();
            $table->string('parameter', 512);
            $table->string('result', 512);
            $table->string('reason', 512);
            $table->string('disposition', 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vetoresults');
    }
};
