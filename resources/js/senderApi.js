function headers() {
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    };
}

export function fillUrl(pattern, replacements) {
    return Object.entries(replacements).reduce((url, [token, value]) => url.replace(token, encodeURIComponent(value)), pattern);
}

export async function getJson(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (!response.ok) throw new Error(`Request failed (${response.status})`);

    return response.json();
}

export async function postJson(url, body) {
    const response = await fetch(url, { method: 'POST', headers: headers(), credentials: 'same-origin', body: JSON.stringify(body) });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(data.message || `Request failed (${response.status})`);
        error.data = data;
        throw error;
    }

    return data;
}
