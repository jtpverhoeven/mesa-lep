<?php

namespace App\Actions\ConfirmationTables;

use App\Actions\Media\StoreEditorImages;
use App\Models\ConfirmationTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteConfirmationTable
{
    public function handle(ConfirmationTable $table): void
    {
        DB::transaction(function () use ($table): void {
            $table->assays()->detach();
            $table->delete();
        });

        Storage::disk('local')->deleteDirectory(StoreEditorImages::directory($table));
    }
}
