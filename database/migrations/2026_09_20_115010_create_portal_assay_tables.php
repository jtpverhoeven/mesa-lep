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
        Schema::create('portalassays', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->text('common_name');
            $table->text('common_name_en')->nullable();
            $table->integer('alertable')->default(1);
            $table->integer('active')->default(1);
            $table->integer('selectable')->default(1);
            $table->integer('border_reaction')->default(0);
        });

        Schema::create('portalassaycontent', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('assay_id');
            $table->integer('common_id');
            $table->integer('original_id');

            $table->index('assay_id');
            $table->index('common_id');
        });

        Schema::create('client_portal_assay', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('client_id');
            $table->integer('portal_assay_id');

            $table->index('client_id');
            $table->index('portal_assay_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_portal_assay');
        Schema::dropIfExists('portalassaycontent');
        Schema::dropIfExists('portalassays');
    }
};
