<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('researchprofiles', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('original_id');
            $table->string('name', 128);
            $table->integer('global');
            $table->integer('client');
            $table->integer('active')->default(1);
            $table->integer('portal_visible')->default(0);
            $table->integer('lims_visible')->nullable()->default(1);
        });

        Schema::create('assayprofiles', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('research_profile');
            $table->integer('assay');
            $table->text('dillutions');
            $table->integer('replicates')->default(0);
            $table->string('reference', 128);
            $table->integer('hidden')->default(0);
            $table->integer('project_order')->default(1);
            $table->integer('conf_trip')->default(0);
            $table->integer('reference_source')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assayprofiles');
        Schema::dropIfExists('researchprofiles');
    }
};