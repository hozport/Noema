import { bindNoemaMarkupField } from './markup-editor.js';
import { bindEntityLinkTooltips } from './markup-entity-tooltips.js';

document.addEventListener('DOMContentLoaded', () => {
    const resolveStatic = document.querySelector('main[data-markup-resolve-url]')?.dataset.markupResolveUrl;
    if (resolveStatic) {
        document.querySelectorAll('main .noema-markup-view').forEach((el) => {
            bindEntityLinkTooltips(el, resolveStatic, document.body);
        });
    }

    document.querySelectorAll('form[data-markup-entities-url]').forEach((form) => {
        const entitiesUrl = form.dataset.markupEntitiesUrl || '';
        const resolveUrl = form.dataset.markupResolveUrl || '';
        const linkModal = document.getElementById('linkEntityModal');
        const linkModuleSelect = document.getElementById('linkModuleSelect');
        const linkEntitySelect = document.getElementById('linkEntitySelect');
        const linkModalConfirm = document.getElementById('linkModalConfirm');
        const linkModalCancel = document.getElementById('linkModalCancel');

        form.querySelectorAll('[data-noema-markup-field]').forEach((field) => {
            const base = field.getAttribute('data-noema-markup-base');
            if (!base) {
                return;
            }
            bindNoemaMarkupField({
                formEl: form,
                viewWrap: document.getElementById(`${base}-view-wrap`),
                viewEl: document.getElementById(`${base}-view`),
                editWrap: document.getElementById(`${base}-edit-wrap`),
                cmHost: document.getElementById(`${base}-cm-host`),
                previewDialog: document.getElementById(`${base}-preview-dialog`),
                previewDialogBody: document.getElementById(`${base}-preview-body`),
                previewToggleBtn: document.getElementById(`${base}-preview-toggle`),
                hiddenContent: document.getElementById(`${base}-hidden`),
                linkModal,
                linkModuleSelect,
                linkEntitySelect,
                linkModalConfirm,
                linkModalCancel,
                entitiesUrl,
                resolveUrl,
            });
        });
    });
});
