<?php

namespace App\Actions\Imports;

use App\Actions\Media\StoreEditorImages;
use App\Models\Assay;
use App\Models\ConfirmationTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RestoreLegacyConfirmationTables
{
    public function __construct(private StoreEditorImages $images) {}

    /** Clears everything this restore derives, so a re-import starts from the CSV exports alone. */
    public function reset(): void
    {
        DB::table('assay_confirmation_table')->truncate();
        Storage::disk('local')->deleteDirectory('confirmation-tables');
    }

    /**
     * Moves legacy images into storage and links the assays to their tables.
     *
     * @param  string  $directory  The CSV export directory; legacy images are read from its worklistUpload folder.
     */
    public function handle(string $directory): void
    {
        $imageDirectory = $directory.DIRECTORY_SEPARATOR.'worklistUpload';

        ConfirmationTable::query()->each(function (ConfirmationTable $table) use ($imageDirectory): void {
            $html = $this->images->handle($table, (string) $table->html, is_dir($imageDirectory) ? $imageDirectory : null);

            if ($html !== $table->html) {
                $table->update(['html' => $html]);
            }
        });

        $this->linkAssays();
    }

    /** Links every assay to the table(s) named by its legacy `show_conf_table` value. */
    public function linkAssays(): void
    {
        $tableIds = ConfirmationTable::query()->pluck('id')->flip();
        $links = [];

        Assay::query()->whereNotNull('show_conf_table')->get(['id', 'show_conf_table'])->each(function (Assay $assay) use ($tableIds, &$links): void {
            foreach (explode(',', $assay->show_conf_table) as $tableId) {
                $tableId = trim($tableId);

                if (ctype_digit($tableId) && $tableIds->has((int) $tableId)) {
                    $links[] = ['assay_id' => $assay->id, 'confirmation_table_id' => (int) $tableId];
                }
            }
        });

        foreach (array_chunk($links, 500) as $chunk) {
            DB::table('assay_confirmation_table')->insertOrIgnore($chunk);
        }
    }
}
