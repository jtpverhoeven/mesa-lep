import { after, afterEach, before, beforeEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { createRenderer, h, nextTick, ref, ssrContextKey } from 'vue';
import { createPinia, setActivePinia } from 'pinia';
import { createServer } from 'vite';
import vue from '@vitejs/plugin-vue';

let server;
let selector;
let useClientSelectorStore;
let SfxPlugin;
let app;
let played;
const originalFetch = globalThis.fetch;
const originalAudio = globalThis.Audio;
const renderer = createRenderer({
    createElement: () => ({}),
    createText: () => ({}),
    createComment: () => ({}),
    insert() {},
    remove() {},
    setText() {},
    setElementText() {},
    patchProp() {},
    parentNode: () => null,
    nextSibling: () => null,
});

before(async () => {
    server = await createServer({
        configFile: false,
        plugins: [vue()],
        server: { middlewareMode: true, hmr: false },
        appType: 'custom',
    });
    const component = await server.ssrLoadModule('/resources/js/components/SampleClientSelector.vue');
    ({ useClientSelectorStore } = await server.ssrLoadModule('/resources/js/stores/clientSelectorStore.js'));
    ({ SfxPlugin } = await server.ssrLoadModule('/resources/js/sfx.js'));
    selector = { ...component.default, render: () => null };
});
after(async () => { await server?.close(); });
beforeEach(() => {
    played = [];
    globalThis.Audio = class {
        constructor(source) { this.source = source; }
        play() { played.push(this.source); return Promise.resolve(); }
    };
    setActivePinia(createPinia());
    useClientSelectorStore().configure('/clients/search', '/clients/__CLIENT__/details');
});
afterEach(() => {
    app?.unmount();
    app = null;
    globalThis.fetch = originalFetch;
    if (originalAudio === undefined) delete globalThis.Audio;
    else globalThis.Audio = originalAudio;
});

function mountSelector(enabled = ref(true)) {
    app = renderer.createApp({
        render: () => h(selector, { showDetails: enabled.value, registrationStore: {} }),
    });
    app.provide(ssrContextKey, { modules: new Set() });
    app.use(SfxPlugin);
    app.mount({});
}

async function selectClient(id, details) {
    globalThis.fetch = async () => ({ ok: true, json: async () => ({ data: details }) });
    const store = useClientSelectorStore();
    store.select({ id, name: `Client ${id}` });
    await nextTick();
    await new Promise((resolve) => setImmediate(resolve));
    await nextTick();
}

test('the default plain selector does not fetch details or play a sound', async () => {
    let requests = 0;
    globalThis.fetch = async () => { requests++; };
    app = renderer.createApp({ render: () => h(selector, { registrationStore: {} }) });
    app.provide(ssrContextKey, { modules: new Set() });
    app.use(SfxPlugin);
    app.mount({});

    useClientSelectorStore().select({ id: 1, name: 'Plain client' });
    await nextTick();

    assert.equal(requests, 0);
    assert.deepEqual(played, []);
});

test('enabled details play the wishes sound once for notes and once for a different client with only files', async () => {
    mountSelector();

    await selectClient(1, { notes: '<p>Koel vervoeren</p>', files: [] });
    await nextTick();
    assert.deepEqual(played, ['/sfx/clientwishes.mp3']);

    await selectClient(2, { notes: '', files: [{ name: 'Instructie' }] });
    assert.deepEqual(played, ['/sfx/clientwishes.mp3', '/sfx/clientwishes.mp3']);
});

test('clients without wishes or files remain silent and clearing never replays the sound', async () => {
    mountSelector();

    await selectClient(1, { notes: null, files: [] });
    useClientSelectorStore().clear();
    await nextTick();

    assert.deepEqual(played, []);
});

test('blocked audio is reported without losing the loaded client details', async (context) => {
    const failure = new Error('Playback blocked');
    const warning = context.mock.method(console, 'warn', () => {});
    globalThis.Audio = class {
        play() { return Promise.reject(failure); }
    };
    mountSelector();

    await selectClient(1, { notes: 'Wishes', files: [] });

    assert.equal(useClientSelectorStore().details.notes, 'Wishes');
    assert.equal(warning.mock.callCount(), 1);
    assert.equal(warning.mock.calls[0].arguments[1], failure);
});

test('disabling the optional details clears them and silences subsequent selections', async () => {
    const enabled = ref(true);
    mountSelector(enabled);
    await selectClient(1, { notes: 'Wishes', files: [] });

    enabled.value = false;
    await nextTick();
    await selectClient(2, { notes: 'Other wishes', files: [] });

    assert.equal(useClientSelectorStore().details, null);
    assert.deepEqual(played, ['/sfx/clientwishes.mp3']);
});

test('unmounting the selector clears details and cancels an unfinished request', async () => {
    let signal;
    let resolveResponse;
    globalThis.fetch = (url, options) => {
        signal = options.signal;
        return new Promise((resolve) => { resolveResponse = resolve; });
    };
    mountSelector();
    useClientSelectorStore().select({ id: 1, name: 'Pending client' });
    await nextTick();

    app.unmount();
    app = null;
    resolveResponse({ ok: true, json: async () => ({ data: { notes: 'Late wishes', files: [] } }) });
    await new Promise((resolve) => setImmediate(resolve));

    assert.equal(signal.aborted, true);
    assert.equal(useClientSelectorStore().details, null);
    assert.deepEqual(played, []);
});
