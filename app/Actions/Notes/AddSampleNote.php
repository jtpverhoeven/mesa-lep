<?php

namespace App\Actions\Notes;

use App\Enums\NoteKind;
use App\Models\Note;
use App\Models\Sample;
use Illuminate\Support\Facades\DB;

class AddSampleNote
{
    public function __construct(private SyncNoteMentions $syncMentions) {}

    /**
     * @param  list<int>  $mentionedUserIds
     */
    public function handle(Sample $sample, string $body, ?int $userId, array $mentionedUserIds = []): ?Note
    {
        $body = trim($body);

        if ($body === '') {
            return null;
        }

        return DB::transaction(function () use ($sample, $body, $userId, $mentionedUserIds): Note {
            $note = $sample->notes()->create(['kind' => NoteKind::Internal, 'body' => $body, 'user_id' => $userId]);
            $this->syncMentions->handle($note, $mentionedUserIds);

            return $note;
        });
    }
}
