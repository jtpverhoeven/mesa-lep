export function referenceFields(assay) {
    if (!assay) return [];
    const reference = assay.reference ?? {};
    const fields = (assay.configuration?.reference_outputs ?? []).map((field) => {
        if (Object.hasOwn(reference, field.key) || !Object.hasOwn(reference, field.name)) return field;
        return { ...field, key: field.name };
    });
    for (const key of Object.keys(reference)) {
        if (!fields.some((field) => field.key === key)) fields.push({ key, name: key.replace(/^ref_/, ''), label: key.replace(/^ref_/, '') });
    }
    return fields;
}

export function referenceValues(assay) {
    return Object.fromEntries(Object.entries(assay.reference ?? {}).filter(([, value]) => value != null && String(value).trim() !== ''));
}

export function referenceSourceProblem(assay, sources, sourcesReady = true, context = 'dit profiel') {
    if (Object.keys(referenceValues(assay)).length && !assay.reference_source) return 'Een referentiebron is verplicht wanneer een referentiewaarde is ingevuld.';
    if (assay.reference_source && sourcesReady && !sources.some((source) => Number(source.id) === Number(assay.reference_source))) return `Deze referentiebron is niet beschikbaar voor ${context}. Kies een andere bron.`;
    return '';
}

export function createAssayDraft(assay) {
    const draft = {
        ...assay,
        reference: Object.fromEntries(Object.entries(assay.reference ?? {}).map(([key, value]) => [key, value == null ? '' : String(value)])),
    };
    for (const field of referenceFields(assay)) draft.reference[field.key] ??= '';
    return draft;
}

export function validateAssayDraft(assay, sourceProblem) {
    const errors = {};
    if (sourceProblem) errors.reference_source = sourceProblem;
    for (const key of ['replicates', 'conf_trip']) {
        if (!Number.isInteger(assay[key]) || assay[key] < 0) errors[key] = 'Vul een geheel getal van 0 of hoger in.';
    }
    if (assay.dillutions_text.split(/\r?\n/).some((line) => line.trim() && !/^\S[^=]*=\s*\S.*$/.test(line.trim()))) {
        errors.dillutions = 'Gebruik per regel een verdunning in de vorm -1=0.1.';
    }
    return errors;
}
