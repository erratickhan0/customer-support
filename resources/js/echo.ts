import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo?: Echo<'pusher'>;
    }
}

window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;
const wsHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const wsPort = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);
const wssPort = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);
const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';

if (key) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key,
        wsHost,
        wsPort,
        wssPort,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
    });
}
