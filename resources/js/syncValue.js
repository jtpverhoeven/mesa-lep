// Keeps an input in step with the server value without overwriting what the user is typing in the focused field.
function apply(el, value) {
    const next = value ?? '';

    if (document.activeElement !== el && el.value !== String(next)) el.value = next;
}

export const vSyncValue = {
    mounted: (el, binding) => { el.value = binding.value ?? ''; },
    updated: (el, binding) => apply(el, binding.value),
};
