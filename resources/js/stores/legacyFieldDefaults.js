export function legacyFieldDefault(value) {
    const now = new Date();
    const tomorrow = new Date(now);
    const yesterday = new Date(now);
    tomorrow.setDate(now.getDate() + 1);
    yesterday.setDate(now.getDate() - 1);
    const formatDate = (date) => [String(date.getDate()).padStart(2, '0'), String(date.getMonth() + 1).padStart(2, '0'), date.getFullYear()].join('-');

    return {
        '{today}': formatDate(now),
        '{tommorow}': formatDate(tomorrow),
        '{yesterday}': formatDate(yesterday),
        '{current_time}': now.toTimeString().slice(0, 5),
    }[value] ?? value ?? '';
}

export function legacyFieldValues(fields, values = {}) {
    return Object.fromEntries(fields.map((field) => [field.name, values[field.name] ?? legacyFieldDefault(field.std_value)]));
}