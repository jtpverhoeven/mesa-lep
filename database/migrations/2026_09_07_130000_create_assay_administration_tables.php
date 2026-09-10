<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assaytypes', function (Blueprint $table) {            
            $table->integer('id', autoIncrement: true);
            $table->string('name', 128);
            $table->text('description');
            $table->integer('added_by');
            $table->string('added_date', 32);
            $table->integer('active')->default(1);
        });

        Schema::create('assaytypefields', function (Blueprint $table) {            
            $table->integer('id', autoIncrement: true);
            $table->integer('test_id');
            $table->string('name', 32);
            $table->string('alias', 64);
            $table->string('type', 32);
            $table->integer('pos');
            $table->integer('endresults_driver')->default(1);
            $table->integer('filter')->nullable()->default(0);
        });

        Schema::create('assayfields', function (Blueprint $table) {
            
            $table->integer('id', autoIncrement: true);
            $table->string('name', 64);
            $table->string('standard_value', 64);
            $table->integer('position');
        });

        Schema::create('assays', function (Blueprint $table) {
       
            $table->integer('id', autoIncrement: true);
            $table->integer('original_id');
            $table->string('name', 128);
            $table->integer('type_base');
            $table->string('media_id', 128);
            $table->integer('dillution');
            $table->integer('replicates');
            $table->integer('confirmation');
            $table->integer('confirmation_type')->default(1);
            $table->text('confirmation_script');
            $table->text('confirmation_support')->nullable();
            $table->integer('type');
            $table->text('meta_assays');
            $table->integer('max_count');
            $table->integer('min_count');
            $table->text('script');
            $table->text('custom_fields');
            $table->string('duration', 5);
            $table->string('start_from', 128);
            $table->integer('active')->default(1);
            $table->integer('hide_report')->default(0);
            $table->text('show_conf_table')->nullable();
            $table->integer('confirmation_init')->default(0);
            $table->integer('confirmation_depth')->nullable()->default(5);
            $table->integer('uses_indicator')->default(1);
            $table->integer('uses_trip_indicator')->default(1);
            $table->text('article_code');
            $table->tinyInteger('billable')->nullable()->default(1);
        });

        Schema::create('media', function (Blueprint $table) {
    
            $table->integer('id', autoIncrement: true);
            $table->text('name');
            $table->string('short_name', 32)->nullable();
            $table->integer('confirmation_media')->default(0);
            $table->integer('type')->default(1);
            $table->text('supplements')->nullable();
            $table->integer('hasDate')->default(1);
            $table->string('confirmation_controls', 128)->nullable();
            $table->integer('active')->default(1);
            $table->integer('used_for_prediction')->default(1);
            $table->integer('prediction_qom')->default(1);
            $table->integer('prediction_default_quant')->default(18);
            $table->string('acceptable_range', 255)->nullable();
        });

        Schema::create('matrix', function (Blueprint $table) {
    
            $table->integer('id', autoIncrement: true);
            $table->text('name');
            $table->text('icon')->nullable();
        });

        Schema::create('matrixcontent', function (Blueprint $table) {
    
            $table->integer('id', autoIncrement: true);
            $table->integer('assay_base');
            $table->integer('matrix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrixcontent');
        Schema::dropIfExists('matrix');
        Schema::dropIfExists('media');
        Schema::dropIfExists('assays');
        Schema::dropIfExists('assayfields');
        Schema::dropIfExists('assaytypefields');
        Schema::dropIfExists('assaytypes');
    }
};
