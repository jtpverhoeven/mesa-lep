<?php

use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('samples.{sampleId}', function (User $user, int $sampleId) {
    return $user->enabled
        && $user->canAny(['samples.view', 'projects.view'])
        && Sample::query()->whereKey($sampleId)->exists();
});
