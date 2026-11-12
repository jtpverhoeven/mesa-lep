<?php

namespace App\Events;

use App\Models\Note;
use Illuminate\Foundation\Events\Dispatchable;

class UsersMentionedInNote
{
    use Dispatchable;

    /**
     * @param  list<int>  $userIds  Users newly mentioned by this save.
     */
    public function __construct(public Note $note, public array $userIds) {}
}
