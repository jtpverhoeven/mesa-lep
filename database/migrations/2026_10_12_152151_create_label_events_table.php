<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labelevents', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->string('event', 1);
            $table->integer('event_group');
            $table->text('event_data');
            $table->integer('print_label');
            $table->integer('print_to');
            $table->integer('copies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labelevents');
    }
};
