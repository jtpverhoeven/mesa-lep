import { afterEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { sfx } from '../../resources/js/sfx.js';

const originalAudio = globalThis.Audio;
afterEach(() => {
    if (originalAudio === undefined) delete globalThis.Audio;
    else globalThis.Audio = originalAudio;
});

test('client wishes use the existing sound effects service and the correct audio file', async () => {
    let source;
    let played = false;
    globalThis.Audio = class {
        constructor(value) { source = value; }
        play() { played = true; return Promise.resolve(); }
    };

    await sfx.play('clientwishes');

    assert.equal(source, '/sfx/clientwishes.mp3');
    assert.equal(played, true);
});

test('audio playback failures remain observable by the caller', async () => {
    const failure = new Error('Playback blocked');
    globalThis.Audio = class {
        play() { return Promise.reject(failure); }
    };

    await assert.rejects(sfx.play('clientwishes'), failure);
});
