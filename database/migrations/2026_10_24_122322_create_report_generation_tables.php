<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docgentemplates', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->text('name');
            $table->text('folder');
            $table->integer('active')->default(1);
            $table->integer('view_order')->nullable();
        });

        Schema::create('docgenassociations', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('assay_id');
            $table->integer('original_assay_id');
            $table->integer('occurs_in');
            $table->index(['occurs_in', 'assay_id']);
            $table->index('assay_id');
        });

        Schema::create('footers', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->text('name');
            $table->string('lang', 255);
            $table->tinyInteger('q')->nullable();
            $table->text('data');
        });

        Schema::create('exports', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('transaction')->nullable()->index();
            $table->integer('client')->index();
            $table->integer('revision');
            $table->integer('project')->index();
            $table->integer('print_version');
            $table->integer('type');
            $table->string('naming_strategy', 32)->nullable()->default('reference');
            $table->string('naming_description', 64)->nullable()->default('');
            $table->string('template', 128)->nullable();
            $table->text('hash');
            $table->string('date', 64);
            $table->string('report_reference', 128)->nullable();
            $table->string('true_reference', 64)->nullable();
            $table->integer('temporary')->default(0);
            $table->integer('was_authorized')->default(0);
            $table->integer('sample_id')->nullable();
            $table->text('sample_id_scope')->nullable();
            $table->index(['client', 'project']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
        Schema::dropIfExists('footers');
        Schema::dropIfExists('docgenassociations');
        Schema::dropIfExists('docgentemplates');
    }
};
