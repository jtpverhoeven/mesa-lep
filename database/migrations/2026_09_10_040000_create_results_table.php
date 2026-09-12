<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('sample');
            $table->integer('sa_id');
            $table->integer('follow_no');
            $table->integer('profile');
            $table->integer('assay');
            $table->integer('assay_base');
            $table->integer('roaming_id');
            $table->string('df', 32);
            $table->integer('rep');
            $table->text('data');

            $table->index('sample', 'idx_results_sample');
            $table->index('sa_id', 'idx_results_sa_id');
            $table->index(['follow_no', 'sample'], 'idx_results_follow_no_sample');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
