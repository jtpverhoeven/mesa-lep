<?php

use App\Actions\Imports\RestoreLegacyConfirmationTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assay_confirmation_table', function (Blueprint $table) {
            $table->integer('assay_id');
            $table->integer('confirmation_table_id');

            $table->primary(['assay_id', 'confirmation_table_id']);
            $table->index('confirmation_table_id');
        });

        if (Schema::hasTable('assays') && Schema::hasTable('confirmationtables')) {
            app(RestoreLegacyConfirmationTables::class)->linkAssays();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assay_confirmation_table');
    }
};
