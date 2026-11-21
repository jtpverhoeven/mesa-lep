export function auditUrl(template, token, value) {
    return value === undefined || value === null ? '' : template.replace(token, encodeURIComponent(String(value)));
}
