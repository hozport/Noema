import Sortable from 'sortablejs';
import axios from 'axios';
import { bindCardMarkupEditor } from './markup-editor.js';
import { bindEntityLinkTooltips } from './markup-entity-tooltips.js';
import {
    installFocusTrap,
    bindDialogUnsavedGuard,
    createGuardedClose,
} from './modal-accessibility.js';

const DRAFT_DEBOUNCE_MS = 400;

function draftKey(worldId, storyId, cardId) {
    return `wb:cardDraft:${worldId}:${storyId}:${cardId}`;
}

function getPageRoot() {
    return document.getElementById('story-page-root');
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

function initStoryPageModals() {
    const root = getPageRoot();
    const storySettingsModal = document.getElementById('storySettingsModal');
    const editModal = document.getElementById('editModal');
    const worldId = root?.dataset.worldId ?? editModal?.dataset.worldId;
    const storyId = root?.dataset.storyId ?? editModal?.dataset.storyId;
    /** Черновики в sessionStorage только если на странице есть id мира и истории */
    const canUseDraft = !!(worldId && storyId);

    const editForm = document.getElementById('editForm');
    const titleInput = document.getElementById('editModalTitleInput');
    const contentInput = document.getElementById('editModalContent');
    const highlightField = document.getElementById('editModalHighlightField');

    if (storySettingsModal) {
        installFocusTrap(storySettingsModal);
        const nameInput = document.getElementById('storySettingsName');
        const cycleInput = document.getElementById('storySettingsCycle');
        const synopsisInput = document.getElementById('storySettingsSynopsis');
        const cardDisplayModeInput = document.getElementById('storySettingsCardDisplayMode');
        const cardDisplayToggle = document.getElementById('storySettingsCardDisplayToggle');
        let settingsSnapshot = { name: '', cycle: '', synopsis: '', cardDisplayMode: 'modal' };

        const syncCardDisplayToggleFromHidden = () => {
            if (!cardDisplayToggle || !cardDisplayModeInput) {
                return;
            }
            cardDisplayToggle.checked = cardDisplayModeInput.value === 'page';
        };

        cardDisplayToggle?.addEventListener('change', () => {
            if (!cardDisplayModeInput) {
                return;
            }
            cardDisplayModeInput.value = cardDisplayToggle.checked ? 'page' : 'modal';
        });

        const settingsDirty = () =>
            (nameInput?.value ?? '') !== settingsSnapshot.name ||
            (cycleInput?.value ?? '') !== settingsSnapshot.cycle ||
            (synopsisInput?.value ?? '') !== settingsSnapshot.synopsis ||
            (cardDisplayModeInput?.value ?? 'modal') !== settingsSnapshot.cardDisplayMode;

        const settingsGuardedClose = createGuardedClose(storySettingsModal, settingsDirty);
        bindDialogUnsavedGuard(storySettingsModal, settingsDirty);

        storySettingsModal.addEventListener('toggle', (e) => {
            if (e.target !== storySettingsModal || !storySettingsModal.open) {
                return;
            }
            syncCardDisplayToggleFromHidden();
            settingsSnapshot = {
                name: nameInput?.value ?? '',
                cycle: cycleInput?.value ?? '',
                synopsis: synopsisInput?.value ?? '',
                cardDisplayMode: cardDisplayModeInput?.value ?? 'modal',
            };
            requestAnimationFrame(() => nameInput?.focus());
        });

        storySettingsModal.querySelectorAll('.story-dialog__scrim').forEach((el) => {
            el.addEventListener('click', settingsGuardedClose);
        });
        storySettingsModal.querySelectorAll('[data-story-settings-close]').forEach((el) => {
            el.addEventListener('click', settingsGuardedClose);
        });

        const nameCounter = document.getElementById('storySettingsNameCounter');
        const cycleCounter = document.getElementById('storySettingsCycleCounter');
        const synCounter = document.getElementById('storySettingsSynopsisCounter');
        bindCounter(nameInput, nameCounter, { soft: 200, maxLength: 255 });
        bindCounter(cycleInput, cycleCounter, { maxLength: 255 });
        bindCounter(synopsisInput, synCounter, { soft: 6000, hard: 8000 });

        const settingsForm = storySettingsModal.querySelector('form.story-dialog__panel');
        settingsForm?.addEventListener('submit', () => {
            settingsSnapshot = {
                name: nameInput?.value ?? '',
                cycle: cycleInput?.value ?? '',
                synopsis: synopsisInput?.value ?? '',
                cardDisplayMode: cardDisplayModeInput?.value ?? 'modal',
            };
        });

        syncCardDisplayToggleFromHidden();
    }

    /* Не требовать worldId/storyId: иначе при отсутствии #story-page-root или пустых data-* весь модал карточки не инициализируется (в т.ч. редактор разметки). */
    if (!editModal || !editForm || !titleInput || !contentInput) {
        return;
    }

    installFocusTrap(editModal);

    const highlightOnFromField = () => highlightField?.value === '1';

    let editSnapshot = { title: '', content: '', highlightOn: false };
    let currentCardId = null;

    const editDirty = () => {
        window.noemaCardMarkup?.syncBeforeSubmit();
        return (
            (titleInput.value ?? '') !== editSnapshot.title ||
            (contentInput.value ?? '') !== editSnapshot.content ||
            highlightOnFromField() !== editSnapshot.highlightOn
        );
    };

    const editGuardedClose = createGuardedClose(editModal, editDirty);
    bindDialogUnsavedGuard(editModal, editDirty);

    editModal.querySelectorAll('.story-dialog__scrim').forEach((el) => {
        el.addEventListener('click', editGuardedClose);
    });
    editModal.querySelectorAll('[data-edit-modal-close]').forEach((el) => {
        el.addEventListener('click', editGuardedClose);
    });

    const titleCounter = document.getElementById('editModalTitleCounter');
    const contentCounter = document.getElementById('editModalContentCounter');
    bindCounter(titleInput, titleCounter, { soft: 200, maxLength: 255 });
    bindCounter(contentInput, contentCounter, { soft: 90000, hard: 100000 });

    /* Редактор и клик по предпросмотру должны работать всегда; URL сущностей нужны только для ссылок и тултипов */
    bindCardMarkupEditor({
        markupEditBoundary: document.getElementById('editModalMarkupBoundary'),
        viewWrap: document.getElementById('editModalMarkupViewWrap'),
        viewEl: document.getElementById('editModalMarkupView'),
        editWrap: document.getElementById('editModalMarkupEditWrap'),
        cmHost: document.getElementById('editModalCmHost'),
        hiddenContent: contentInput,
        linkModal: document.getElementById('linkEntityModal'),
        linkModuleSelect: document.getElementById('linkModuleSelect'),
        linkEntitySelect: document.getElementById('linkEntitySelect'),
        linkModalConfirm: document.getElementById('linkModalConfirm'),
        linkModalCancel: document.getElementById('linkModalCancel'),
        entitiesUrl:
            root?.dataset.markupEntitiesUrl ?? editModal?.dataset.markupEntitiesUrl ?? '',
        resolveUrl:
            root?.dataset.markupResolveUrl ?? editModal?.dataset.markupResolveUrl ?? '',
    });

    const onEditInput = () => {
        if (!currentCardId || !canUseDraft) {
            return;
        }
        scheduleEditDraftSave(worldId, storyId, currentCardId, titleInput.value, contentInput.value);
    };
    titleInput.addEventListener('input', onEditInput);
    contentInput.addEventListener('input', onEditInput);

    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        window.noemaCardMarkup?.syncBeforeSubmit();
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const formData = new FormData(editForm);
        try {
            const res = await fetch(editForm.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                },
                body: formData,
            });
            let data = null;
            try {
                data = await res.json();
            } catch (_) {
                data = null;
            }
            if (!res.ok) {
                if (res.status === 422 && data) {
                    const fromErrors = data.errors
                        ? Object.values(data.errors)
                              .flat()
                              .filter(Boolean)
                              .join(' ')
                        : '';
                    const msg = fromErrors || data.message || 'Не удалось сохранить карточку.';
                    window.alert(msg);
                    return;
                }
                window.location.reload();
                return;
            }
            if (currentCardId && canUseDraft) {
                clearEditDraft(worldId, storyId, currentCardId);
            }
            editModal.close();
            window.location.reload();
        } catch (_) {
            window.location.reload();
        }
    });

    window.openEditModal = function openEditModal(
        triggerEl,
        actionUrl,
        title,
        content,
        number,
        decomposeUrl,
        deleteUrl
    ) {
        const wrap = triggerEl && triggerEl.closest ? triggerEl.closest('.story-card-wrap') : null;
        const cardId = wrap?.dataset.cardId ? String(wrap.dataset.cardId) : null;
        currentCardId = cardId;
        editForm.dataset.cardId = cardId || '';

        titleInput.value = title || '';
        let n = number;
        if (wrap && wrap.dataset.cardNumber) {
            const parsed = parseInt(wrap.dataset.cardNumber, 10);
            if (Number.isFinite(parsed) && parsed > 0) {
                n = parsed;
            }
        }
        titleInput.placeholder = 'Карточка ' + n;
        contentInput.value = content || '';
        editForm.action = actionUrl;
        document.getElementById('modalDecomposeForm').action = decomposeUrl;
        document.getElementById('modalDeleteForm').action = deleteUrl;

        if (highlightField) {
            highlightField.value =
                wrap && wrap.classList.contains('story-card-wrap--highlighted') ? '1' : '0';
        }

        editSnapshot = {
            title: titleInput.value,
            content: contentInput.value,
            highlightOn: highlightOnFromField(),
        };

        if (cardId && canUseDraft) {
            try {
                const raw = sessionStorage.getItem(draftKey(worldId, storyId, cardId));
                if (raw) {
                    const d = JSON.parse(raw);
                    const dt = d.title ?? '';
                    const dc = d.content ?? '';
                    if (dt !== editSnapshot.title || dc !== editSnapshot.content) {
                        if (
                            window.confirm(
                                'Найден сохранённый черновик карточки. Восстановить из черновика?'
                            )
                        ) {
                            titleInput.value = dt;
                            contentInput.value = dc;
                            editSnapshot = {
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

        editSnapshot = {
            title: titleInput.value,
            content: contentInput.value,
            highlightOn: highlightOnFromField(),
        };

        if (window.noemaCardMarkup) {
            window.noemaCardMarkup.syncFromServer(contentInput.value);
        } else {
            const ve = document.getElementById('editModalMarkupView');
            if (ve) {
                ve.textContent = contentInput.value || '';
            }
        }

        updateCounter(titleInput, titleCounter, { soft: 200, maxLength: 255 });
        updateCounter(contentInput, contentCounter, { soft: 90000, hard: 100000 });

        window.syncEditModalHighlightButton();
        editModal.showModal();
        requestAnimationFrame(() => titleInput.focus());
    };

    window.syncEditModalHighlightButton = function syncEditModalHighlightButton() {
        const btn = document.getElementById('editModalHighlightBtn');
        if (!btn || !highlightField) {
            return;
        }
        const on = highlightOnFromField();
        btn.classList.toggle('card-page-pin-btn--active', on);
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        btn.title = on
            ? 'Снять закрепление (применится после «Сохранить»)'
            : 'Закрепить карточку на сетке (применится после «Сохранить»)';
        btn.setAttribute(
            'aria-label',
            on ? 'Снять закрепление карточки' : 'Закрепить карточку'
        );
    };

    window.toggleEditModalHighlight = function toggleEditModalHighlight() {
        if (!highlightField) {
            return;
        }
        highlightField.value = highlightField.value === '1' ? '0' : '1';
        window.syncEditModalHighlightButton();
    };

    window.submitModalDecompose = function submitModalDecompose() {
        if (!window.confirm('Каждый абзац станет отдельной карточкой. Продолжить?')) {
            return;
        }
        document.getElementById('modalDecomposeForm').submit();
    };

    window.submitModalDelete = function submitModalDelete() {
        if (!window.confirm('Удалить эту карточку?')) {
            return;
        }
        document.getElementById('modalDeleteForm').submit();
    };
}

function initStoryCardsFilter() {
    const search = document.getElementById('story-cards-search');
    const list = document.getElementById('story-cards-sortable');
    if (!search || !list) {
        return;
    }

    search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        list.querySelectorAll('.story-card-wrap').forEach((wrap) => {
            const blob = (wrap.dataset.searchText ?? '').toLowerCase();
            const match = !q || blob.includes(q);
            wrap.classList.toggle('hidden', !match);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initStoryPageModals();
    initStoryCardsFilter();

    const list = document.getElementById('story-cards-sortable');
    const pageRoot = getPageRoot();
    const gridResolveUrl = pageRoot?.dataset.markupResolveUrl;
    if (gridResolveUrl && list) {
        bindEntityLinkTooltips(list, gridResolveUrl, document.body);
    }

    if (!list) {
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!list.dataset.reorderUrl || !token) {
        return;
    }

    Sortable.create(list, {
        animation: 150,
        draggable: '.story-card-wrap',
        filter: 'button, textarea, .story-card-more',
        preventOnFilter: true,
        ghostClass: 'story-card-ghost',
        onEnd() {
            const ids = [...list.querySelectorAll('.story-card-wrap[data-card-id]')].map((el) =>
                parseInt(el.dataset.cardId, 10)
            );

            axios
                .post(
                    list.dataset.reorderUrl,
                    { order: ids },
                    {
                        headers: {
                            'X-CSRF-TOKEN': token,
                            Accept: 'application/json',
                        },
                    }
                )
                .then(() => {
                    list.querySelectorAll('.story-card-wrap').forEach((wrap, i) => {
                        const n = i + 1;
                        const num = wrap.querySelector('.card-order-number');
                        if (num) {
                            num.textContent = String(n);
                        }
                        wrap.dataset.cardNumber = String(n);
                        const titleEl = wrap.querySelector('.story-card-title-display');
                        const rawTitle = (wrap.getAttribute('data-card-title') ?? '').trim();
                        if (titleEl) {
                            titleEl.textContent = rawTitle !== '' ? rawTitle : 'Карточка ' + n;
                        }
                    });
                })
                .catch(() => {
                    window.location.reload();
                });
        },
    });
});
