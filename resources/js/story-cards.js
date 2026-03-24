import Sortable from 'sortablejs';
import axios from 'axios';
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
    const worldId = root?.dataset.worldId;
    const storyId = root?.dataset.storyId;

    const storySettingsModal = document.getElementById('storySettingsModal');
    const editModal = document.getElementById('editModal');
    const editForm = document.getElementById('editForm');
    const titleInput = document.getElementById('editModalTitleInput');
    const contentInput = document.getElementById('editModalContent');

    if (storySettingsModal) {
        installFocusTrap(storySettingsModal);
        const nameInput = document.getElementById('storySettingsName');
        const synopsisInput = document.getElementById('storySettingsSynopsis');
        let settingsSnapshot = { name: '', synopsis: '' };

        const settingsDirty = () =>
            (nameInput?.value ?? '') !== settingsSnapshot.name ||
            (synopsisInput?.value ?? '') !== settingsSnapshot.synopsis;

        const settingsGuardedClose = createGuardedClose(storySettingsModal, settingsDirty);
        bindDialogUnsavedGuard(storySettingsModal, settingsDirty);

        storySettingsModal.addEventListener('toggle', (e) => {
            if (e.target !== storySettingsModal || !storySettingsModal.open) {
                return;
            }
            settingsSnapshot = {
                name: nameInput?.value ?? '',
                synopsis: synopsisInput?.value ?? '',
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
        const synCounter = document.getElementById('storySettingsSynopsisCounter');
        bindCounter(nameInput, nameCounter, { soft: 200, maxLength: 255 });
        bindCounter(synopsisInput, synCounter, { soft: 6000, hard: 8000 });

        const settingsForm = storySettingsModal.querySelector('form.story-dialog__panel');
        settingsForm?.addEventListener('submit', () => {
            settingsSnapshot = {
                name: nameInput?.value ?? '',
                synopsis: synopsisInput?.value ?? '',
            };
        });
    }

    if (!editModal || !editForm || !titleInput || !contentInput || !worldId || !storyId) {
        return;
    }

    installFocusTrap(editModal);

    let editSnapshot = { title: '', content: '' };
    let currentCardId = null;

    const editDirty = () =>
        (titleInput.value ?? '') !== editSnapshot.title ||
        (contentInput.value ?? '') !== editSnapshot.content;

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

    const onEditInput = () => {
        if (!currentCardId) {
            return;
        }
        scheduleEditDraftSave(worldId, storyId, currentCardId, titleInput.value, contentInput.value);
    };
    titleInput.addEventListener('input', onEditInput);
    contentInput.addEventListener('input', onEditInput);

    editForm.addEventListener('submit', () => {
        if (currentCardId) {
            clearEditDraft(worldId, storyId, currentCardId);
        }
    });

    window.openEditModal = function openEditModal(
        triggerEl,
        actionUrl,
        title,
        content,
        number,
        decomposeUrl,
        deleteUrl,
        highlightUrl
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
        editForm.dataset.highlightUrl = highlightUrl || '';

        editSnapshot = {
            title: titleInput.value,
            content: contentInput.value,
        };

        if (cardId) {
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
                            editSnapshot = { title: dt, content: dc };
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
        };

        updateCounter(titleInput, titleCounter, { soft: 200, maxLength: 255 });
        updateCounter(contentInput, contentCounter, { soft: 90000, hard: 100000 });

        window.syncEditModalHighlightButton(wrap);
        editModal.showModal();
        requestAnimationFrame(() => titleInput.focus());
    };

    window.syncEditModalHighlightButton = function syncEditModalHighlightButton(wrap) {
        const btn = document.getElementById('editModalHighlightBtn');
        if (!btn) {
            return;
        }
        const isHighlighted = wrap && wrap.classList.contains('story-card-wrap--highlighted');
        const add = btn.querySelector('.edit-modal-highlight-icon-add');
        const remove = btn.querySelector('.edit-modal-highlight-icon-remove');
        if (add) {
            add.style.display = isHighlighted ? 'none' : 'inline-flex';
        }
        if (remove) {
            remove.style.display = isHighlighted ? 'inline-flex' : 'none';
        }
        btn.title = isHighlighted
            ? 'Снять выделение'
            : 'Выделить: отметить карточку, чтобы быстро найти место остановки';
        btn.setAttribute(
            'aria-label',
            isHighlighted ? 'Снять выделение' : 'Выделить карточку для быстрого поиска'
        );
    };

    window.highlightCardFromModal = async function highlightCardFromModal() {
        const url = editForm.dataset.highlightUrl;
        if (!url) {
            return;
        }
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
            });
            if (!res.ok) {
                window.location.reload();
                return;
            }
            const data = await res.json();
            if (editDirty()) {
                if (
                    !window.confirm(
                        'Остались несохранённые изменения. Закрыть без сохранения?'
                    )
                ) {
                    return;
                }
            }
            editModal.close();
            document.querySelectorAll('.story-card-wrap').forEach((w) => {
                w.classList.remove('story-card-wrap--highlighted');
            });
            if (data.highlighted_card_id) {
                const wrap = document.querySelector(
                    `.story-card-wrap[data-card-id="${data.highlighted_card_id}"]`
                );
                if (wrap) {
                    wrap.classList.add('story-card-wrap--highlighted');
                }
            }
        } catch (e) {
            window.location.reload();
        }
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
                        if (titleEl && rawTitle === '') {
                            titleEl.textContent = 'Карточка ' + n;
                        }
                    });
                })
                .catch(() => {
                    window.location.reload();
                });
        },
    });
});
