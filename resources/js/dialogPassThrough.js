export function createAppDialogPassThrough({ width = '520px', titleId = null, role = null } = {}) {
    return {
        mask: { class: 'app-dialog-mask' },
        root: {
            class: 'app-dialog',
            style: { '--app-dialog-width': width },
            ...(role ? { role } : {}),
            ...(titleId ? { 'aria-labelledby': titleId } : {}),
        },
        header: { class: 'app-dialog-header' },
        title: { class: 'app-dialog-title', ...(titleId ? { id: titleId } : {}) },
        headerActions: { class: 'app-dialog-header-actions' },
        content: { class: 'app-dialog-content' },
        message: { class: 'app-dialog-message' },
        footer: { class: 'app-dialog-footer' },
    };
}