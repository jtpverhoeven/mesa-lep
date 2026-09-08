<?php

namespace App\Policies;

use App\Models\Assay;
use App\Models\User;

class AssayPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('administration.access');
    }

    public function create(User $user): bool
    {
        return $user->can('assays.manage');
    }

    public function update(User $user, Assay $assay): bool
    {
        return $user->can('assays.manage');
    }
}
