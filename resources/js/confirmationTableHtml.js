/**
 * Quill only understands plain `<td>` cells in a single `<tbody>`. Legacy tables also use
 * `<thead>`/`<th>` and pretty-printed whitespace, which would otherwise collapse into one cell.
 */
export function quillTableHtml(html) {
    if (!html || !/<table/i.test(html)) return html ?? '';

    const doc = new DOMParser().parseFromString(html, 'text/html');

    doc.querySelectorAll('table').forEach((table) => {
        const body = doc.createElement('tbody');

        table.querySelectorAll('tr').forEach((row) => {
            [...row.childNodes].filter((node) => node.nodeType === Node.TEXT_NODE).forEach((node) => node.remove());
            row.querySelectorAll(':scope > th').forEach((header) => {
                const cell = doc.createElement('td');
                [...header.attributes].forEach(({ name, value }) => cell.setAttribute(name, value));
                if (header.textContent.trim() !== '') {
                    const strong = doc.createElement('strong');
                    strong.append(...header.childNodes);
                    cell.append(strong);
                }
                header.replaceWith(cell);
            });
            row.querySelectorAll(':scope > td').forEach((cell) => {
                // A Quill cell holds exactly one line, so line breaks and blocks inside a cell become spaces.
                cell.querySelectorAll('br').forEach((lineBreak) => lineBreak.replaceWith(' '));
                cell.querySelectorAll('p, div, li').forEach((block) => block.replaceWith(...block.childNodes, ' '));
                cell.normalize();
                const walker = doc.createTreeWalker(cell, NodeFilter.SHOW_TEXT);
                for (let node = walker.nextNode(); node; node = walker.nextNode()) node.textContent = node.textContent.replace(/\s+/g, ' ');
                cell.innerHTML = cell.innerHTML.trim();
            });
            body.append(row);
        });

        table.replaceChildren(body);
    });

    return doc.body.innerHTML;
}
