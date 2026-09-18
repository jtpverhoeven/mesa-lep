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
        Schema::create('samplebuffers', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('client');
            $table->integer('source')->default(1);
            $table->string('project', 32);
            $table->string('portal_follow_no', 32)->default('');
            $table->string('sampling_date', 32);
            $table->integer('sampling_method');
            $table->text('sample_name');
            $table->text('sample_details');
            $table->integer('tht');
            $table->date('tht_date')->nullable();
            $table->text('meta');
            $table->text('analyses_selected');
            $table->text('misc_directions')->nullable();
            $table->integer('authorized')->default(0);
            $table->text('project_name')->nullable();
            $table->text('portal_order_info')->nullable();
            $table->text('portal_analyses')->nullable();
            $table->text('portal_meta')->nullable();
            $table->text('portal_notes')->nullable();
            $table->integer('portal_id')->nullable();
            $table->integer('portal_product_group_id')->nullable();
            $table->integer('portal_project')->nullable();
            $table->text('receive_time')->nullable();
            $table->text('receive_date')->nullable();
            $table->string('tht_code', 32)->nullable();
            $table->string('date_registered', 32)->nullable();
            $table->string('sample_research_type', 32)->nullable();
            $table->text('sample_properties')->nullable();

            $table->index(['authorized', 'tht']);
            $table->index('client');
            $table->index('project');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('samplebuffers');
    }
};
