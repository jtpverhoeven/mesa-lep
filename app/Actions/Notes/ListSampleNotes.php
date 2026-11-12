<?php

namespace App\Actions\Notes;

use App\Models\Note;
use App\Models\Sample;

class ListSampleNotes
{
    public function __construct(private PresentNote $present) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function handle(Sample $sample): array
    {
        return $sample->internalNotes()
            ->with(['user:id,name', 'user.profile:id,user_id,avatar_path', 'mentions:id,name'])
            ->get()
            ->map(fn (Note $note): array => $this->present->handle($note))
            ->all();
    }
}
