<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worklists', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->text('analyses');
            $table->integer('no_columns')->default(1);
            $table->string('title', 128)->nullable();
            $table->string('subtitle', 128)->nullable();
            $table->string('footer_text', 128)->nullable();
            $table->text('columns')->nullable();
            $table->text('colum_groups')->nullable();
            $table->text('extra_rows')->nullable();
            $table->text('extra_pages')->nullable();
            $table->string('orientation', 1)->default('P');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worklists');
    }
};
