import { bindCardMarkupEditor } from './markup-editor.js';

const DRAFT_DEBOUNCE_MS = 400;

function draftKey(worldId, storyId, cardId) {
    return `wb:cardDraft:${worldId}:${storyId}:${cardId}`;
}

function updateCounter(el, counterEl, opts) {
    if (!el || !counterEl) {
        return;
    }
    const len = el.value.length;
    const max =
        opts.maxLength != null ? opts.maxLength : el.maxLength > 0 ? el.maxLength : null;
    const soft = opts.soft;
    const hard = opts.hard;
    counterEl.textContent = max ? `${len} / ${max}` : String(len);
    counterEl.classList.remove('text-warning', 'text-error', 'text-base-content/50');
    if (max && len > max) {
        counterEl.classList.add('text-error');
    } else if (hard != null && len >= hard) {
        counterEl.classList.add('text-error');
    } else if (soft != null && len >= soft) {
        counterEl.classList.add('text-warning');
    } else {
        counterEl.classList.add('text-base-content/50');
    }
}

function bindCounter(el, counterEl, opts) {
    if (!el || !counterEl) {
        return;
    }
    const run = () => updateCounter(el, counterEl, opts);
    el.addEventListener('input', run);
    run();
}

let editDraftTimer = null;

function clearEditDraft(worldId, storyId, cardId) {
    if (!cardId) {
        return;
    }
    try {
        sessionStorage.removeItem(draftKey(worldId, storyId, cardId));
    } catch (_) {
        /* ignore */
    }
}

function scheduleEditDraftSave(worldId, storyId, cardId, title, content) {
    clearTimeout(editDraftTimer);
    editDraftTimer = setTimeout(() => {
        try {
            sessionStorage.setItem(
                draftKey(worldId, storyId, cardId),
                JSON.stringify({ title, content, t: Date.now() })
            );
        } catch (_) {
            /* ignore */
        }
    }, DRAFT_DEBOUNCE_MS);
}

function initCardPageEditor() {
    const root = document.getElementById('card-page-root');
    const editForm = document.getElementById('cardPageEditForm');
    const titleInput = document.getElementById('cardPageTitleInput');
    const contentInput = document.getElementById('cardPageContent');
    const highlightField = document.getElementById('cardPageHighlightField');
    const markupBoundary = document.getElementById('cardPageMarkupBoundary');

    if (!root || !editForm || !titleInput || !contentInput) {
        return;
    }

    const worldId = root.dataset.worldId;
    const storyId = root.dataset.storyId;
    const cardId = root.dataset.cardId;
    const canUseDraft = !!(worldId && storyId && cardId);

    const entitiesUrl = root.dataset.markupEntitiesUrl ?? '';
    const resolveUrl = root.dataset.markupResolveUrl ?? '';

    const titleCounter = document.getElementById('cardPageTitleCounter');
    const contentCounter = document.getElementById('cardPageContentCounter');
    bindCounter(titleInput, titleCounter, { soft: 200, maxLength: 255 });
    bindCounter(contentInput, contentCounter, { soft: 90000, hard: 100000 });

    bindCardMarkupEditor({
        formEl: editForm,
        cmRootMaxHeight: 'min(72vh, 48rem)',
        markupEditBoundary: markupBoundary ?? undefined,
        viewWrap: document.getElementById('cardPageMarkupViewWrap'),
        viewEl: document.getElementById('cardPageMarkupView'),
        editWrap: document.getElementById('cardPageMarkupEditWrap'),
        cmHost: document.getElementById('cardPageCmHost'),
        hiddenContent: contentInput,
        linkModal: document.getElementById('linkEntityModal'),
        linkModuleSelect: document.getElementById('linkModuleSelect'),
        linkEntitySelect: document.getElementById('linkEntitySelect'),
        linkModalConfirm: document.getElementById('linkModalConfirm'),
        linkModalCancel: document.getElementById('linkModalCancel'),
        entitiesUrl,
        resolveUrl,
    });

    const highlightOnFromField = () => highlightField?.value === '1';

    let serverSnapshot = {
        title: titleInput.value ?? '',
        content: contentInput.value ?? '',
        highlightOn: highlightOnFromField(),
    };

    if (canUseDraft) {
        try {
            const raw = sessionStorage.getItem(draftKey(worldId, storyId, cardId));
            if (raw) {
                const d = JSON.parse(raw);
                const dt = d.title ?? '';
                const dc = d.content ?? '';
                if (dt !== serverSnapshot.title || dc !== serverSnapshot.content) {
                    if (
                        window.confirm(
                            'Найден сохранённый черновик карточки. Восстановить из черновика?'
                        )
                    ) {
                        titleInput.value = dt;
                        contentInput.value = dc;
                        serverSnapshot = {
                            title: dt,
                            content: dc,
                            highlightOn: highlightOnFromField(),
                        };
                    } else {
                        clearEditDraft(worldId, storyId, cardId);
                    }
                }
            }
        } catch (_) {
            /* ignore */
        }
    }

    if (window.noemaCardMarkup) {
        window.noemaCardMarkup.syncFromServer(contentInput.value);
    } else {
        const ve = document.getElementById('cardPageMarkupView');
        if (ve) {
            ve.textContent = contentInput.value || '';
        }
    }

    updateCounter(titleInput, titleCounter, { soft: 200, maxLength: 255 });
    updateCounter(contentInput, contentCounter, { soft: 90000, hard: 100000 });

    const pinBtn = document.getElementById('cardPageHighlightBtn');

    function syncPinButton() {
        if (!pinBtn || !highlightField) {
            return;
        }
        const on = highlightOnFromField();
        pinBtn.classList.toggle('card-page-pin-btn--active', on);
        pinBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
        pinBtn.title = on
            ? 'Снять закрепление (применится после «Сохранить»)'
            : 'Закрепить карточку на сетке (применится после «Сохранить»)';
        pinBtn.setAttribute(
            'aria-label',
            on ? 'Снять закрепление карточки' : 'Закрепить карточку'
        );
    }

    syncPinButton();

    window.toggleCardPagePin = function toggleCardPagePin() {
        if (!highlightField) {
            return;
        }
        highlightField.value = highlightField.value === '1' ? '0' : '1';
        syncPinButton();
    };

    const isDirty = () => {
        window.noemaCardMarkup?.syncBeforeSubmit();
        return (
            (titleInput.value ?? '') !== serverSnapshot.title ||
            (contentInput.value ?? '') !== serverSnapshot.content ||
            highlightOnFromField() !== serverSnapshot.highlightOn
        );
    };

    const onEditInput = () => {
        if (!canUseDraft) {
            return;
        }
        scheduleEditDraftSave(worldId, storyId, cardId, titleInput.value, contentInput.value);
    };
    titleInput.addEventListener('input', onEditInput);
    contentInput.addEventListener('input', onEditInput);

    editForm.addEventListener('submit', () => {
        if (canUseDraft) {
            clearEditDraft(worldId, storyId, cardId);
        }
    });

    const backLink = document.getElementById('cardPageBackLink');
    backLink?.addEventListener('click', (e) => {
        if (!isDirty()) {
            return;
        }
        if (!window.confirm('Остались несохранённые изменения. Уйти без сохранения?')) {
            e.preventDefault();
        }
    });

    window.submitCardPageDecompose = function submitCardPageDecompose() {
        if (!window.confirm('Каждый абзац станет отдельной карточкой. Продолжить?')) {
            return;
        }
        document.getElementById('cardPageDecomposeForm')?.submit();
    };
}

document.addEventListener('DOMContentLoaded', () => {
    initCardPageEditor();
});
