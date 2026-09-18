import { inject } from 'vue';

const sounds = Object.freeze({
    chat: '/sfx/chat.mp3',
    confirmations: '/sfx/confirmations.mp3',
    do: '/sfx/do.mp3',
    error: '/sfx/error.mp3',
    notification: '/sfx/notification.mp3',
    warning: '/sfx/warning.mp3',
});

const sfxKey = Symbol('sfx');

export const sfx = Object.freeze({
    play(name) {
        const source = sounds[name];

        if (!source) {
            return Promise.reject(new Error(`Unknown sound effect: ${name}`));
        }

        return new Audio(source).play();
    },
});

export function useSfx() {
    const service = inject(sfxKey);

    if (!service) {
        throw new Error('The sound effects plugin has not been installed.');
    }

    return service;
}

export const SfxPlugin = {
    install(app) {
        app.provide(sfxKey, sfx);
        app.config.globalProperties.$sfx = sfx;
    },
};