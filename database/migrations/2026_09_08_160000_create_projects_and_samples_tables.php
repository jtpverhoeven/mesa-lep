<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {            
            $table->integer('id', autoIncrement: true);
            $table->string('reference', 32)->nullable();
            $table->integer('client');
            $table->integer('subclient');
            $table->string('project_name', 128)->nullable();
            $table->text('project_notes')->nullable();
            $table->string('project_date', 32)->nullable();
            $table->integer('last_edit')->nullable();
            $table->text('custom_fields')->nullable();
            $table->integer('revision')->default(1);
            $table->integer('auth_status')->default(0);
            $table->integer('auth_by')->nullable();
            $table->integer('auth_on')->nullable();
            $table->integer('print_version')->default(0);
            $table->integer('predicted_end')->nullable();
            $table->text('project_extra')->nullable();
            $table->integer('is_ready')->default(0);
            $table->integer('special_type')->default(0);
            $table->integer('rap_stat')->default(0);
            $table->integer('rap_by')->nullable();
            $table->string('rap_on', 32)->nullable();
            $table->integer('rap_rev')->nullable();
            $table->string('became_ready_on', 32)->nullable();
            $table->integer('started')->default(0);
            $table->integer('added_by')->nullable();
            $table->integer('portal_id')->nullable();
            $table->integer('locked')->default(0);
            $table->integer('locked_by')->nullable();
            $table->string('lock_pass', 255)->nullable();
            $table->text('lock_message')->nullable();
            $table->text('print_info')->nullable();
        });

        Schema::create('samples', function (Blueprint $table) {
            
            $table->integer('id', autoIncrement: true);
            $table->string('barcode', 32);
            $table->string('tht_code', 32)->nullable();
            $table->integer('follow_no');
            $table->text('description');
            $table->text('client_description')->nullable();
            $table->integer('sampling_method');
            $table->string('date_registered', 32);
            $table->integer('registered_by');
            $table->integer('client');
            $table->integer('subclient');
            $table->integer('project');
            $table->text('custom_fields');
            $table->integer('predicted_end');
            $table->string('sample_innoculated', 64);
            $table->string('stored_in', 5)->nullable();
            $table->string('diluted_at', 5)->nullable();
            $table->text('sample_note')->nullable();
            $table->string('sample_type', 1)->default('S');
            $table->string('leg_type', 1)->default('-')->nullable();
            $table->text('sample_extra')->nullable();
            $table->integer('isEmpty')->default(1);
            $table->integer('source')->default(0);
            $table->text('analyses_data');
            $table->text('portal_analyses')->nullable();
            $table->timestamp('updated_at')->useCurrent();
            $table->integer('portal_sample_id')->nullable();
            $table->integer('portal_product_group_id')->nullable();
            $table->integer('portal_project_id')->nullable();
            $table->text('portal_notes')->nullable();
            $table->index('project', 'idx_samples_project');
            $table->index('sample_innoculated', 'idx_samples_sample_innoculated');
            $table->index('isEmpty', 'idx_samples_isEmpty');
            $table->index('client', 'idx_samples_client');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
        Schema::dropIfExists('projects');
    }
};