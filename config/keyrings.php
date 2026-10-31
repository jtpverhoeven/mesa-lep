<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Keyring Backstop Lifetime
    |--------------------------------------------------------------------------
    |
    | Reverb presence decides whether a keyring holder is still active. The
    | expiry below is only a backstop for when Reverb cannot be reached: a
    | keyring is then kept until it expires and may be taken over after.
    | When empty, the session lifetime is used.
    |
    */

    'lifetime_minutes' => env('KEYRING_LIFETIME_MINUTES'),

    /*
    |--------------------------------------------------------------------------
    | Presence Query
    |--------------------------------------------------------------------------
    |
    | Broadcast connection and timeout (seconds) used to ask Reverb which
    | users are present on a keyring channel.
    |
    */

    'presence_connection' => env('KEYRING_PRESENCE_CONNECTION', 'reverb'),

    'presence_timeout' => (float) env('KEYRING_PRESENCE_TIMEOUT', 2),

];
