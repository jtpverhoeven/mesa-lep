<?php

namespace App\Actions\Worklists;

use App\Actions\Media\StoreEditorImages;
use App\Models\Worklist;
use Illuminate\Support\Facades\Storage;

class DeleteWorklist
{
    public function handle(Worklist $worklist): void
    {
        $worklist->delete();

        Storage::disk('local')->deleteDirectory(StoreEditorImages::directory($worklist));
    }
}
