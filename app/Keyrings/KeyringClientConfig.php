<?php

namespace App\Keyrings;

use App\Models\User;

class KeyringClientConfig
{
    /**
     * Front-end settings for the keyring store.
     *
     * @return array{base_url: string, user_id: int, can_steal: bool}
     */
    public static function for(User $user): array
    {
        return [
            'base_url' => url('/keyrings'),
            'user_id' => $user->id,
            'can_steal' => $user->can('keyrings.steal'),
        ];
    }
}
