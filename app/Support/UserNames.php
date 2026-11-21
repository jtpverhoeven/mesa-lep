<?php

namespace App\Support;

use App\Models\User;

/**
 * Display names as legacy profilesController::getUserFullName gave them: the profile's full name,
 * "Onbekend" when the user cannot be found. Resolved names are remembered per instance.
 */
class UserNames
{
    public const UNKNOWN = 'Onbekend';

    /** @var array<int, string> */
    private array $names = [];

    public function name(mixed $userId): string
    {
        if (! is_numeric($userId) || (int) $userId <= 0) {
            return self::UNKNOWN;
        }

        return $this->names[(int) $userId] ??= $this->resolve((int) $userId);
    }

    private function resolve(int $userId): string
    {
        $user = User::query()->with('profile:id,user_id,first_name,last_name')->find($userId, ['id', 'name']);
        $fullName = trim(($user?->profile?->first_name ?? '').' '.($user?->profile?->last_name ?? ''));

        return $fullName !== '' ? $fullName : ($user?->name ?? self::UNKNOWN);
    }
}
