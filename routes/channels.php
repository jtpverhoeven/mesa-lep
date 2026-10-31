<?php

use App\Keyrings\KeyringType;
use App\Models\Sample;
use App\Models\User;
use App\Support\AccountImages;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('samples.{sampleId}', function (User $user, int $sampleId) {
    return $user->enabled
        && $user->canAny(['samples.view', 'projects.view'])
        && Sample::query()->whereKey($sampleId)->exists();
});

Broadcast::channel('keyrings.{type}.{typeId}', function (User $user, string $type, int $typeId) {
    if (! in_array($type, KeyringType::ROUTABLE_SLUGS, true) || ! KeyringType::fromSlug($type)->isAccessibleBy($user, $typeId)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar_url' => AccountImages::userAvatarUrl($user),
    ];
});

Broadcast::channel('keyrings.admin', function (User $user): bool {
    return $user->enabled && $user->can('administration.access') && $user->can('keyrings.manage');
});
