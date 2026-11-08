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
        Schema::create('samplefiles', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('sample_id');
            $table->text('hash_name');
            $table->text('original_name');
            $table->timestamp('created_at')->useCurrent();
            $table->integer('visible_for_client')->default(1);
            $table->integer('client_id');
            $table->integer('sent_to_client')->default(0);
            $table->index('sample_id', 'idx_samplefiles_sample_id');
            $table->index('hash_name', 'idx_samplefiles_hash_name');
        });

        Schema::create('filehistories', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('sample_id');
            $table->integer('samplefile_id');
            $table->text('hash_name');
            $table->timestamp('sent_on')->useCurrent();
            $table->integer('sent_by');
            $table->text('sent_to');
            $table->text('sent_method');
            $table->integer('transaction_id')->nullable();
            $table->index('sample_id', 'idx_filehistories_sample_id');
            $table->index('samplefile_id', 'idx_filehistories_samplefile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filehistories');
        Schema::dropIfExists('samplefiles');
    }
};
