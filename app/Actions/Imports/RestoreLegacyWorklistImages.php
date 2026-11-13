<?php

namespace App\Actions\Imports;

use App\Actions\Media\StoreEditorImages;
use App\Models\Worklist;
use Illuminate\Support\Facades\Storage;

class RestoreLegacyWorklistImages
{
    public function __construct(private StoreEditorImages $images) {}

    public function reset(): void
    {
        Storage::disk('local')->deleteDirectory('worklists');
    }

    /**
     * Moves the images embedded in the extra pages from the export's worklistUpload folder into storage.
     */
    public function handle(string $directory): void
    {
        $imageDirectory = $directory.DIRECTORY_SEPARATOR.'worklistUpload';

        Worklist::query()->whereNotNull('extra_pages')->each(function (Worklist $worklist) use ($imageDirectory): void {
            $html = $this->images->handle($worklist, (string) $worklist->extra_pages, is_dir($imageDirectory) ? $imageDirectory : null);

            if ($html !== $worklist->extra_pages) {
                $worklist->update(['extra_pages' => $html]);
            }
        });
    }
}
