<?php

namespace App\Actions\Notes;

use App\ChangeTracking\ChangeTracker;
use App\Models\Note;
use App\Models\Sample;
use Illuminate\Support\Facades\DB;

class DeleteSampleNote
{
    public function __construct(private ChangeTracker $changeTracker) {}

    public function handle(Sample $sample, Note $note): void
    {
        DB::transaction(function () use ($sample, $note): void {
            $this->changeTracker->changed(29, project: $sample->project, sample: $sample->id,
                event: 'Monsternotitie verwijderd', from: $note->body, to: '');
            $note->delete();
        });
    }
}
