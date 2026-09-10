<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sampleanalysis', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('profile_group');
            $table->integer('sample');
            $table->integer('follow_number');
            $table->integer('profile');
            $table->integer('assay');
            $table->integer('assay_base');
            $table->integer('roaming_id')->nullable();
            $table->integer('predicted_end');
            $table->integer('original_assay_base')->nullable();
            $table->integer('conf_requested')->default(0);
            $table->integer('is_ready')->default(0);
            $table->integer('project')->nullable();
            $table->integer('project_order')->default(1);
            $table->text('storedResult')->nullable();

            $table->index('sample', 'idx_sampleanalysis_sample');
            $table->index('project', 'idx_sampleanalysis_project');
            $table->index('profile_group', 'idx_sampleanalysis_profile_group');
            $table->index(['sample', 'follow_number'], 'idx_sampleanalysis_sample_follow_number');
            $table->index('assay_base', 'idx_sampleanalysis_assay_base');
            $table->index('original_assay_base', 'idx_sampleanalysis_original_assay_base');
            $table->index(
                ['assay_base', 'original_assay_base'],
                'idx_sampleanalysis_assay_base_original_assay_base'
            );
        });

        Schema::create('roaminganalysis', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('said');
            $table->integer('assay');
            $table->text('dillutions');
            $table->integer('replicates')->default(0);
            $table->string('reference', 32);
            $table->string('reference_scope', 5)->default('');
            $table->integer('reference_source')->nullable();

            $table->index('said', 'idx_roaminganalysis_said');
            $table->index('assay', 'idx_roaminganalysis_assay');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roaminganalysis');
        Schema::dropIfExists('sampleanalysis');
    }
};
