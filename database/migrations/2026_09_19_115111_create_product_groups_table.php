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
        Schema::create('productgroups', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('client_id');
            $table->integer('portal_id');
            $table->text('name');
            $table->integer('default')->default(0);
            $table->integer('visible')->nullable()->default(1);
            $table->index('client_id', 'idx_productgroups_client_id');
            $table->index('portal_id', 'idx_productgroups_portal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productgroups');
    }
};
