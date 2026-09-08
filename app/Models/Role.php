<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

#[Fillable(['name', 'guard_name', 'groupLeader', 'superGroup'])]
class Role extends SpatieRole
{
    protected function casts(): array
    {
        return [
            'superGroup' => 'boolean',
        ];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'groupLeader');
    }
}