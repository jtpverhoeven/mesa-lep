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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            
            $table->string('username')->unique();
            $table->string('title', 50)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender', 20)->nullable();
            $table->string('job_title', 150)->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->string('locale', 2)->default('nl');
            $table->string('avatar_path')->nullable();
            $table->text('dashboard_layout')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};