<?php

namespace App\Actions\Notes;

use App\Models\Note;
use App\Support\AccountImages;

class PresentNote
{
    /** @return array<string, mixed>|null */
    public function optional(?Note $note): ?array
    {
        if ($note === null) {
            return null;
        }

        $note->loadMissing(['user:id,name', 'user.profile:id,user_id,avatar_path', 'mentions:id,name']);

        return $this->handle($note);
    }

    /**
     * Callers eager load `user.profile` and `mentions` when presenting many notes.
     *
     * @return array<string, mixed>
     */
    public function handle(Note $note): array
    {
        return [
            'id' => $note->id,
            'body' => $note->body,
            'author' => $note->user === null ? null : [
                'id' => $note->user->id,
                'name' => $note->user->name,
                'avatar_url' => AccountImages::userAvatarUrl($note->user),
            ],
            'is_own' => $note->user_id !== null && $note->user_id === auth()->id(),
            'created_at' => $note->created_at?->toIso8601String(),
            'updated_at' => $note->updated_at?->toIso8601String(),
            'mentions' => $note->mentions->map->only(['id', 'name'])->values()->all(),
        ];
    }
}
