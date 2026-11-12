export const dilutionOptions = [
    { key: '0', value: '1', label: 'Onverdund' },
    ...Array.from({ length: 10 }, (_, index) => ({
        key: `-${index + 1}`,
        value: `0.${'0'.repeat(index)}1`,
        label: `-${index + 1}`,
    })),
];

export const NOT_A_DECADE_MESSAGE = 'Verdunningen moeten machten van tien zijn (1, 0.1, 0.01, ...).';
export const NOT_SEQUENTIAL_MESSAGE = 'Verdunningen moeten op elkaar aansluiten, bijvoorbeeld -1, -2 en -3.';
export const MALFORMED_MESSAGE = 'Gebruik een geldige verdunningscurve met een numerieke waarde per regel.';

export function toText(value) {
    return Object.entries(value ?? {}).map(([key, setting]) => `${key}=${setting}`).join('\n');
}

export function toObject(text) {
    const result = {};
    String(text ?? '').split(/\r?\n/).forEach((line) => {
        const [key, ...rest] = line.split('=');
        if (key?.trim() && rest.length) result[key.trim()] = rest.join('=').trim();
    });
    return result;
}

export function dilutionExponent(value) {
    const number = Number(value);
    if (!Number.isFinite(number) || number <= 0) return null;
    const exponent = Math.round(Math.log10(number));
    return Math.abs(number - 10 ** exponent) <= Math.abs(number) * 1e-9 ? exponent : null;
}

/** The legacy key for a dilution factor: 0 for undiluted, -N for 10^-N. */
export function dilutionKey(value) {
    const exponent = dilutionExponent(value);
    return exponent === null ? String(value) : String(exponent);
}

export function isSequential(values) {
    const exponents = values.map(dilutionExponent);
    if (exponents.some((exponent) => exponent === null)) return false;
    const unique = [...new Set(exponents)].sort((left, right) => right - left);
    return unique.every((exponent, index) => index === 0 || unique[index - 1] - exponent === 1);
}

/** A Dutch message describing why the posted text is not a usable dilution curve, or '' when it is. */
export function dilutionProblem(text) {
    const lines = String(text ?? '').split(/\r?\n/).filter((line) => line.trim());
    const values = [];

    for (const line of lines) {
        const [key, ...rest] = line.split('=');
        if (!key?.trim() || rest.length !== 1 || rest[0].trim() === '' || Number.isNaN(Number(rest[0]))) return MALFORMED_MESSAGE;
        values.push(rest[0].trim());
    }

    if (values.some((value) => dilutionExponent(value) === null || Number(value) > 1)) return NOT_A_DECADE_MESSAGE;
    return isSequential(values) ? '' : NOT_SEQUENTIAL_MESSAGE;
}

/** The editable state of the change-dilutions dialog, from the rows the analysis currently holds. */
export function createDilutionDraft(data) {
    const dilutions = data.dilutions ?? [];
    const replicates = Math.max(0, ...dilutions.map((dilution) => Number(dilution.replicates) || 0));
    return {
        analysis_id: data.analysis_id,
        name: data.name,
        usesDilution: Boolean(data.uses_dilution),
        usesReplicates: Boolean(data.uses_replicates),
        text: toText(Object.fromEntries(dilutions.map((dilution) => [dilutionKey(dilution.value), dilution.value]))),
        replicates,
        replicatesTouched: false,
        existing: Object.fromEntries(dilutions.map((dilution) => [Number(dilution.value), Number(dilution.replicates) || 0])),
    };
}

export function draftDilutionProblem(draft) {
    if (!draft) return '';
    if (!Object.keys(toObject(draft.text)).length) return 'Kies minimaal één verdunning.';
    return dilutionProblem(draft.text);
}

/**
 * Dilutions to post. Rows are only given a new replicate count when the user changed it,
 * so analyses that legitimately differ per dilution keep their existing counts.
 */
export function dilutionPayload(draft) {
    const values = [...new Map(Object.values(toObject(draft.text)).map((value) => [Number(value), String(value)])).values()];
    const fallback = draft.usesReplicates ? Number(draft.replicates) || 0 : 0;

    return values
        .sort((left, right) => Number(right) - Number(left))
        .map((value) => ({
            value,
            replicates: draft.usesReplicates && draft.replicatesTouched ? fallback : (draft.existing[Number(value)] ?? fallback),
        }));
}
