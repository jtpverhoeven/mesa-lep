<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class AccountImages
{
    public static function signaturePath(User $user, string $language): string
    {
        return 'signatures/'.$user->id.match ($language) {
            'nl' => '',
            'en' => 'En',
            default => throw new InvalidArgumentException('Unsupported signature language.'),
        }.'.png';
    }

    public static function path(User $user, string $kind): ?string
    {
        return $kind === 'avatar' ? $user->profile?->avatar_path : self::signaturePath($user, $kind);
    }

    public static function url(User $user, string $kind): ?string
    {
        $path = self::path($user, $kind);

        return $path && Storage::disk('local')->exists($path)
            ? route('account.images.show', ['kind' => $kind])
            : null;
    }
}
