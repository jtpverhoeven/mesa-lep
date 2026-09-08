<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'username',
    'title',
    'first_name',
    'last_name',
    'gender',
    'job_title',
    'phone_number',
    'locale',
    'avatar_path',
    'dashboard_layout',
])]
class Profile extends Model
{
    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}