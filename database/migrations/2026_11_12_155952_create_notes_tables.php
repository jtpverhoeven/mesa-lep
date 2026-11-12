<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('notable_type', 32);
            $table->unsignedBigInteger('notable_id');
            $table->string('kind', 16);
            $table->longText('body');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin', 16)->nullable();
            $table->timestamps();

            $table->index(['notable_type', 'notable_id', 'kind']);
        });

        Schema::create('note_mentions', function (Blueprint $table) {
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['note_id', 'user_id']);
        });

        Schema::create('note_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128)->unique();
            $table->longText('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_templates');
        Schema::dropIfExists('note_mentions');
        Schema::dropIfExists('notes');
    }
};
