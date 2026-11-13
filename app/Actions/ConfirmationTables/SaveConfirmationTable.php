<?php

namespace App\Actions\ConfirmationTables;

use App\Models\ConfirmationTable;
use Illuminate\Support\Facades\DB;

class SaveConfirmationTable
{
    public function __construct(private StoreConfirmationTableImages $images) {}

    /**
     * @param  array{name: string, html?: ?string}  $data
     */
    public function handle(ConfirmationTable $table, array $data): ConfirmationTable
    {
        return DB::transaction(function () use ($table, $data): ConfirmationTable {
            // Quill's semantic HTML turns every space into &nbsp;, which would stop table cells from wrapping.
            $html = str_replace(['&nbsp;', "\u{a0}"], ' ', $data['html'] ?? '');

            $table->fill(['name' => $data['name'], 'html' => $html])->save();

            $html = $this->images->handle($table, $table->html);

            if ($html !== $table->html) {
                $table->update(['html' => $html]);
            }

            return $table;
        });
    }
}
