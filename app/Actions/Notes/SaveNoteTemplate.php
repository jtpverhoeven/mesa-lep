<?php

namespace App\Actions\Notes;

use App\Models\NoteTemplate;
use App\Support\NoteHtml;

class SaveNoteTemplate
{
    /** Saving under an existing name overwrites that template. */
    public function handle(string $name, string $content): NoteTemplate
    {
        return NoteTemplate::query()->updateOrCreate(
            ['name' => trim($name)],
            ['content' => NoteHtml::clean($content)],
        );
    }
}
