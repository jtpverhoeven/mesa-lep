<?php

namespace App\Actions\Profiles;

use App\Models\User;

class UpdateAccountAlias
{
    public function handle(User $user, ?string $alias): void
    {
        $user->update(['alias' => $alias]);
    }
}
