import DOMPurify from 'dompurify';

const dateFormat = new Intl.DateTimeFormat('nl-NL', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Europe/Amsterdam',
});

export function noteDate(value) {
    return value ? dateFormat.format(new Date(value)) : '';
}

export function noteHtml(html) {
    return DOMPurify.sanitize(html ?? '');
}

function escapeHtml(text) {
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

/** Plain-text feed body with the "@name" of every tagged user highlighted. */
export function feedBodyHtml(note) {
    const names = note.mentions.map((user) => escapeHtml(`@${user.name}`)).sort((a, b) => b.length - a.length);
    let html = escapeHtml(note.body);
    for (const name of names) html = html.split(name).join(`<span class="note-mention">${name}</span>`);

    return html;
}
