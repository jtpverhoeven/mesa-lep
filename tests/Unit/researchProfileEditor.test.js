import { afterEach, beforeEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { createPinia, setActivePinia } from 'pinia';
import { createAssayDraft, referenceFields, referenceSourceProblem, referenceValues, validateAssayDraft } from '../../resources/js/researchProfileAssays.js';
import { useResearchProfileStore } from '../../resources/js/stores/researchProfileStore.js';

const originalFetch = globalThis.fetch;
beforeEach(() => {
    setActivePinia(createPinia());
    globalThis.window = { location: { origin: 'http://localhost', href: '/create' } };
    globalThis.document = { querySelector: () => ({ content: 'csrf-token' }) };
});
afterEach(() => { globalThis.fetch = originalFetch; delete globalThis.window; delete globalThis.document; });

test('directory profiles are sorted by client name then profile name without changing the source order', () => {
    const store = useResearchProfileStore();
    store.profiles = [
        { id: 1, scope: 'Beta', name: 'Alpha profile' },
        { id: 2, scope: 'Alpha', name: 'Zulu profile' },
        { id: 3, scope: 'alpha', name: 'alpha profile' },
        { id: 4, scope: 'Globaal', name: 'Shared profile' },
        { id: 5, scope: 'Beta', name: 'Zulu profile' },
    ];

    const profiles = store.sortedProfiles;

    assert.deepEqual(profiles.map((profile) => profile.id), [3, 2, 1, 5, 4]);
    assert.deepEqual(store.profiles.map((profile) => profile.id), [1, 2, 3, 4, 5]);
});

test('directory sorting updates when profiles are loaded or removed', () => {
    const store = useResearchProfileStore();
    assert.deepEqual(store.sortedProfiles, []);

    store.profiles = [
        { id: 1, scope: 'Beta', name: 'Profile' },
        { id: 2, scope: 'Alpha', name: 'Profile' },
    ];
    assert.deepEqual(store.sortedProfiles.map((profile) => profile.id), [2, 1]);

    store.profiles.splice(1, 1);
    assert.deepEqual(store.sortedProfiles.map((profile) => profile.id), [1]);
});

test('each workflow output has a field and existing custom reference keys are preserved', () => {
    const assay = {
        configuration: { reference_outputs: [
            { key: 'ref_ph', name: 'ph', label: 'pH' },
            { key: 'ref_temperature', name: 'temperature', label: 'Temperatuur' },
        ] },
        reference: { ref_ph: '7', ref_legacy: 'Present' },
    };

    assert.deepEqual(referenceFields(assay), [
        { key: 'ref_ph', name: 'ph', label: 'pH' },
        { key: 'ref_temperature', name: 'temperature', label: 'Temperatuur' },
        { key: 'ref_legacy', name: 'legacy', label: 'legacy' },
    ]);
    assert.deepEqual(createAssayDraft(assay).reference, { ref_ph: '7', ref_legacy: 'Present', ref_temperature: '' });
});

test('an unprefixed legacy reference binds to its workflow output without adding a duplicate field', () => {
    const assay = {
        configuration: { reference_outputs: [{ key: 'ref_kve', name: 'kve', label: 'kve' }] },
        reference: { kve: '100' },
    };

    assert.deepEqual(referenceFields(assay), [{ key: 'kve', name: 'kve', label: 'kve' }]);
    const draft = createAssayDraft(assay);
    assert.deepEqual(draft.reference, { kve: '100' });
    draft.reference.kve = '200';
    assert.deepEqual(referenceValues(draft), { kve: '200' });
    assert.deepEqual(assay.reference, { kve: '100' });
});

test('each output keeps its existing key convention including empty and zero-valued legacy references', () => {
    for (const value of ['', null, 0]) {
        const assay = {
            configuration: { reference_outputs: [
                { key: 'ref_kve', name: 'kve', label: 'kve' },
                { key: 'ref_ph', name: 'ph', label: 'pH' },
                { key: 'ref_temperature', name: 'temperature', label: 'Temperatuur' },
            ] },
            reference: { kve: value, ref_ph: '7', ref_legacy: 'Present' },
        };

        assert.deepEqual(referenceFields(assay).map((field) => field.key), ['kve', 'ref_ph', 'ref_temperature', 'ref_legacy']);
        assert.deepEqual(createAssayDraft(assay).reference, {
            kve: value == null ? '' : String(value), ref_ph: '7', ref_legacy: 'Present', ref_temperature: '',
        });
    }
});

test('changing a draft does not change the selected analysis before applying', () => {
    const assay = { reference: { ref_kve: 0 }, replicates: 1, dillutions_text: '-1=0.1' };
    const draft = createAssayDraft(assay);

    draft.reference.ref_kve = '100';
    draft.replicates = 3;
    draft.dillutions_text = '-2=0.01';

    assert.deepEqual(assay, { reference: { ref_kve: 0 }, replicates: 1, dillutions_text: '-1=0.1' });
});

test('zero is a reference value but empty fields are omitted from the save payload', () => {
    assert.deepEqual(referenceValues({ reference: { ref_zero: 0, ref_string: '0', ref_empty: '', ref_space: '  ', ref_null: null } }), {
        ref_zero: 0, ref_string: '0',
    });
    assert.match(referenceSourceProblem({ reference: { ref_kve: '0' } }, []), /verplicht/);
    assert.equal(referenceSourceProblem({ reference: { ref_kve: '' } }, []), '');
});

test('changing profile scope invalidates an unavailable source without deleting it or its reference', () => {
    const assay = { reference_source: 9, reference: { ref_ph: '7' } };

    assert.equal(referenceSourceProblem(assay, [{ id: 9 }]), '');
    assert.equal(referenceSourceProblem(assay, [], false), '');
    assert.match(referenceSourceProblem(assay, [{ id: 2 }]), /niet beschikbaar/);
    assert.equal(assay.reference_source, 9);
    assert.deepEqual(assay.reference, { ref_ph: '7' });
});

test('unknown workflows retain existing references without inventing an output', () => {
    assert.deepEqual(referenceFields({ configuration: { reference_outputs: null }, reference: {} }), []);
    assert.deepEqual(referenceFields({ reference: { ref_existing: 'Text' } }), [
        { key: 'ref_existing', name: 'existing', label: 'existing' },
    ]);
});

test('draft validation rejects missing sources and invalid analysis settings', () => {
    const draft = { reference: { ref_kve: '0' }, replicates: null, conf_trip: -1, dillutions_text: 'not a dilution' };

    assert.deepEqual(validateAssayDraft(draft, referenceSourceProblem(draft, [])), {
        reference_source: 'Een referentiebron is verplicht wanneer een referentiewaarde is ingevuld.',
        replicates: 'Vul een geheel getal van 0 of hoger in.',
        conf_trip: 'Vul een geheel getal van 0 of hoger in.',
        dillutions: 'Gebruik per regel een verdunning in de vorm -1=0.1.',
    });
    assert.deepEqual(validateAssayDraft({ replicates: 0, conf_trip: 0, dillutions_text: '-1=0.1\n\n-2=0.01' }, ''), {});
});

test('latest client source request wins even when an earlier response arrives later', async () => {
    const responses = [];
    globalThis.fetch = () => new Promise((resolve) => responses.push(resolve));
    const store = useResearchProfileStore();
    store.configure({ referenceSources: '/sources' });

    const first = store.loadReferenceSources(1);
    const second = store.loadReferenceSources(2);
    responses[1]({ ok: true, json: async () => ({ data: [{ id: 20 }] }) });
    await second;
    responses[0]({ ok: true, json: async () => ({ data: [{ id: 10 }] }) });
    await first;

    assert.deepEqual(store.referenceSources, [{ id: 20 }]);
    assert.equal(store.referenceSourcesLoading, false);
    assert.equal(store.referenceSourcesError, '');
});

test('failed source loading is explicit and a retry recovers', async () => {
    const store = useResearchProfileStore();
    store.configure({ referenceSources: '/sources' });
    globalThis.fetch = async () => ({ ok: false, json: async () => ({ message: 'Bronnen niet beschikbaar.' }) });

    await store.loadReferenceSources();

    assert.equal(store.referenceSourcesError, 'Bronnen niet beschikbaar.');
    assert.equal(store.referenceSourcesLoading, false);
    globalThis.fetch = async () => ({ ok: true, json: async () => ({ data: [{ id: 2 }] }) });
    await store.loadReferenceSources();
    assert.equal(store.referenceSourcesError, '');
    assert.deepEqual(store.referenceSources, [{ id: 2 }]);
});
