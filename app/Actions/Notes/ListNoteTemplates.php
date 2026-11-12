<?php

namespace App\Actions\Notes;

use App\Models\NoteTemplate;

class ListNoteTemplates
{
    /**
     * @return list<array{id: int, name: string, content: string}>
     */
    public function handle(): array
    {
        return NoteTemplate::query()->orderBy('name')->get(['id', 'name', 'content'])->map->only(['id', 'name', 'content'])->all();
    }
}
