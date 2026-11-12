<?php

namespace App\Actions\Notes;

use App\Events\UsersMentionedInNote;
use App\Models\Note;
use App\Models\User;

class SyncNoteMentions
{
    /**
     * Only users whose "@name" appears in the body stay mentioned.
     *
     * @param  list<int>  $userIds
     */
    public function handle(Note $note, array $userIds): void
    {
        $mentioned = User::query()
            ->whereKey($userIds)
            ->where('enabled', true)
            ->get(['id', 'name'])
            ->filter(fn (User $user): bool => str_contains($note->body, '@'.$user->name))
            ->pluck('id')
            ->all();

        $changes = $note->mentions()->sync($mentioned);

        if ($changes['attached'] !== []) {
            UsersMentionedInNote::dispatch($note, array_map('intval', $changes['attached']));
        }
    }
}
