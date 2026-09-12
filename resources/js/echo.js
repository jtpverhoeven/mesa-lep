import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echo;

export function getEcho() {
    if (echo) return echo;

    const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';
    const port = Number(import.meta.env.VITE_REVERB_PORT ?? (forceTLS ? 443 : 80));

    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: port,
        wssPort: port,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
    });

    return echo;
}