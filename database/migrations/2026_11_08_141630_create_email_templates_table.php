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
        Schema::create('emailtemplates', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->string('name', 128)->nullable();
            $table->text('template')->nullable();
            $table->text('bcc')->nullable();
            $table->integer('defaultmail')->default(0);
            $table->integer('defaultmail_with_files')->default(0);
            $table->integer('defaultmail_for_files')->default(0);
            $table->text('template_en')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emailtemplates');
    }
};
