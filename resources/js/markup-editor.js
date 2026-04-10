import { Prec, RangeSetBuilder } from '@codemirror/state';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import { defaultHighlightStyle, syntaxHighlighting } from '@codemirror/language';
import {
    Decoration,
    EditorView,
    drawSelection,
    highlightSpecialChars,
    keymap,
    lineNumbers,
    ViewPlugin,
} from '@codemirror/view';
import { MODULE_OPTIONS, parseAndRenderHtml } from './noema-markup.js';
import { bindEntityLinkTooltips } from './markup-entity-tooltips.js';

/** @type {HTMLElement|null} */
let activeCtxMenuEl = null;

function hideActiveCtxMenu() {
    if (activeCtxMenuEl) {
        activeCtxMenuEl.remove();
        activeCtxMenuEl = null;
    }
}

if (typeof document !== 'undefined') {
    document.addEventListener('click', hideActiveCtxMenu);
}

/** Редактор, в который вставлять ссылку из общего модального окна (последний открывший ссылку). */
/** @type {import('@codemirror/view').EditorView|null} */
let pendingLinkCmView = null;

/**
 * Общий linkEntityModal: один confirm на страницу, иначе несколько полей перезаписывают обработчики.
 * @param {HTMLDialogElement|null} linkModal
 */
function ensureLinkModalShared(linkModal) {
    if (!linkModal || linkModal.dataset.noemaMarkupLinkBound === '1') {
        return;
    }
    linkModal.dataset.noemaMarkupLinkBound = '1';
    const linkModalConfirm = document.getElementById('linkModalConfirm');
    const linkModalCancel = document.getElementById('linkModalCancel');
    const linkModuleSelect = document.getElementById('linkModuleSelect');
    const linkEntitySelect = document.getElementById('linkEntitySelect');

    linkModalConfirm?.addEventListener('click', () => {
        if (!pendingLinkCmView || !linkModuleSelect || !linkEntitySelect) {
            return;
        }
        const mod = parseInt(linkModuleSelect.value, 10);
        const ent = parseInt(linkEntitySelect.value, 10);
        if (!Number.isFinite(ent) || ent < 1) {
            return;
        }
        wrapSelection(pendingLinkCmView, `[link module=${mod} entity=${ent}]`, '[/link]');
        pendingLinkCmView = null;
        linkModal.close();
    });
    linkModalCancel?.addEventListener('click', () => {
        pendingLinkCmView = null;
        linkModal.close();
    });
    linkModal.querySelectorAll('[data-link-modal-close]').forEach((el) => {
        el.addEventListener('click', () => {
            pendingLinkCmView = null;
            linkModal.close();
        });
    });
    linkModuleSelect?.addEventListener('change', () => {
        const url = linkModal.dataset.markupEntitiesUrl || '';
        if (!url || !linkEntitySelect) {
            return;
        }
        loadEntitiesForModuleShared(linkEntitySelect, parseInt(linkModuleSelect.value, 10), url);
    });
}

/**
 * @param {HTMLSelectElement|null} linkEntitySelect
 * @param {number} mod
 * @param {string} entitiesUrl
 */
async function loadEntitiesForModuleShared(linkEntitySelect, mod, entitiesUrl) {
    if (!linkEntitySelect || !entitiesUrl) {
        return;
    }
    linkEntitySelect.innerHTML = '<option value="">Загрузка…</option>';
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const url = new URL(entitiesUrl, window.location.origin);
    url.searchParams.set('module', String(mod));
    const res = await fetch(url.toString(), {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token || '',
        },
    });
    const data = res.ok ? await res.json() : { items: [] };
    linkEntitySelect.innerHTML = '<option value="">— выберите —</option>';
    for (const it of data.items || []) {
        const opt = document.createElement('option');
        opt.value = String(it.id);
        opt.textContent = it.label;
        linkEntitySelect.appendChild(opt);
    }
}


/**
 * @param {string} text
 * @returns {{ from: number, to: number, class: string }[]}
 */
function scanNoemaHighlights(text) {
    /** @type {{ from: number, to: number, class: string }[]} */
    const ranges = [];
    let i = 0;
    while (i < text.length) {
        if (text[i] === '\\' && i + 1 < text.length) {
            ranges.push({ from: i, to: i + 2, class: 'cm-noema-escape' });
            i += 2;
            continue;
        }
        if (text[i] === '[') {
            const slice = text.slice(i);
            let m = slice.match(/^\[link\s+module\s*=\s*\d+\s+entity\s*=\s*\d+\]/);
            if (!m) {
                m = slice.match(/^\[\/link\]/);
            }
            if (!m) {
                m = slice.match(/^\[[bius]\]/);
            }
            if (!m) {
                m = slice.match(/^\[\/[bius]\]/);
            }
            if (m) {
                ranges.push({ from: i, to: i + m[0].length, class: 'cm-noema-tag' });
                i += m[0].length;
                continue;
            }
        }
        i++;
    }
    return ranges;
}

/**
 * @param {EditorView} view
 */
function buildDecorations(view) {
    try {
        const text = view.state.doc.toString();
        const ranges = scanNoemaHighlights(text);
        if (ranges.length === 0) {
            return Decoration.none;
        }
        const b = new RangeSetBuilder();
        for (const r of ranges) {
            b.add(
                r.from,
                r.to,
                Decoration.mark({ class: r.class })
            );
        }
        return b.finish();
    } catch {
        return Decoration.none;
    }
}

function noemaHighlightPlugin() {
    return ViewPlugin.fromClass(
        class {
            /**
             * @param {EditorView} view
             */
            constructor(view) {
                this.decorations = buildDecorations(view);
            }

            /**
             * @param {import('@codemirror/view').ViewUpdate} u
             */
            update(u) {
                if (u.docChanged) {
                    this.decorations = buildDecorations(u.view);
                }
            }
        },
        { decorations: (v) => v.decorations }
    );
}

/**
 * @param {EditorView} view
 * @param {string} open
 * @param {string} close
 */
function wrapSelection(view, open, close) {
    const sel = view.state.selection.main;
    const { from, to } = sel;
    const text = view.state.sliceDoc(from, to);
    view.dispatch({
        changes: { from, to, insert: `${open}${text}${close}` },
        selection: { anchor: from + open.length + text.length + close.length },
    });
    view.focus();
}

/** Зачёркивание: Mod-Shift-s, чтобы не отбирать Ctrl+S у браузера (сохранение страницы). */
const noemaMarkupFormatKeymap = Prec.highest(
    keymap.of([
        {
            key: 'Mod-b',
            run: (view) => {
                wrapSelection(view, '[b]', '[/b]');
                return true;
            },
        },
        {
            key: 'Mod-i',
            run: (view) => {
                wrapSelection(view, '[i]', '[/i]');
                return true;
            },
        },
        {
            key: 'Mod-u',
            run: (view) => {
                wrapSelection(view, '[u]', '[/u]');
                return true;
            },
        },
        {
            key: 'Mod-Shift-s',
            run: (view) => {
                wrapSelection(view, '[s]', '[/s]');
                return true;
            },
        },
    ])
);

/** Как `minimalSetup` из пакета codemirror, но только из @codemirror/* — один граф модулей в бандле. */
const noemaMarkupEditorExtensions = [
    lineNumbers(),
    highlightSpecialChars(),
    history(),
    drawSelection(),
    syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
    noemaMarkupFormatKeymap,
    keymap.of([...defaultKeymap, ...historyKeymap]),
];

/**
 * @param {object} opts
 * @param {HTMLElement|null} [opts.formEl]
 * @param {HTMLElement|null} opts.viewWrap
 * @param {HTMLElement|null} opts.viewEl
 * @param {HTMLElement|null} opts.editWrap
 * @param {HTMLElement|null} opts.cmHost
 * @param {HTMLDialogElement|null} [opts.previewDialog] — опционально (карточки истории без отдельного «Просмотра»)
 * @param {HTMLElement|null} [opts.previewDialogBody]
 * @param {HTMLButtonElement|null} [opts.previewToggleBtn]
 * @param {HTMLInputElement|null} opts.hiddenContent
 * @param {HTMLDialogElement|null} opts.linkModal
 * @param {HTMLSelectElement|null} opts.linkModuleSelect
 * @param {HTMLSelectElement|null} opts.linkEntitySelect
 * @param {HTMLButtonElement|null} opts.linkModalConfirm
 * @param {HTMLButtonElement|null} opts.linkModalCancel
 * @param {string} [opts.entitiesUrl]
 * @param {string} [opts.resolveUrl]
 * @param {string} [opts.globalApiKey] — например `noemaCardMarkup` для модалки карточки
 * @param {string} [opts.cmRootMaxHeight] — max-height корня CodeMirror (по умолчанию 22rem)
 * @param {HTMLElement} [opts.markupEditBoundary] — если задан, клик вне этой области сворачивает редактор в просмотр
 */
export function bindNoemaMarkupField(opts) {
    /** @type {import('@codemirror/view').EditorView|null} */
    let cmView = null;
    /** @type {(() => void)|null} */
    let cmContextMenuCleanup = null;

    const {
        formEl: formElOpt,
        viewWrap,
        viewEl,
        editWrap,
        cmHost,
        previewDialog,
        previewDialogBody,
        previewToggleBtn,
        hiddenContent,
        linkModal,
        linkModuleSelect,
        linkEntitySelect,
        linkModalConfirm: _linkModalConfirm,
        linkModalCancel: _linkModalCancel,
        entitiesUrl,
        resolveUrl,
        globalApiKey,
        cmRootMaxHeight: cmRootMaxHeightOpt,
    } = opts;

    const cmRootMaxHeight = cmRootMaxHeightOpt ?? '22rem';

    const formEl = formElOpt ?? document.getElementById('editForm');
    const cmHostEl = cmHost ?? document.getElementById('editModalCmHost');
    if (!viewEl || !hiddenContent || !editWrap || !viewWrap || !cmHostEl) {
        return;
    }

    if (linkModal && entitiesUrl) {
        linkModal.dataset.markupEntitiesUrl = entitiesUrl;
    }
    ensureLinkModalShared(linkModal);

    function tooltipMountEl() {
        return cmHostEl.closest('dialog') ?? document.body;
    }

    function renderViewFromSource(source) {
        const { html } = parseAndRenderHtml(source);
        viewEl.innerHTML = html || '<span class="text-base-content/40">(пусто)</span>';
        bindEntityLinkTooltips(viewEl, resolveUrl, tooltipMountEl());
    }

    function renderPreviewDialogContent(source) {
        if (!previewDialogBody) {
            return;
        }
        const { html } = parseAndRenderHtml(source);
        previewDialogBody.innerHTML =
            html ||
            '<span class="text-base-content/40">(пусто)</span>';
        const previewMount =
            previewDialog instanceof HTMLElement ? previewDialog : tooltipMountEl();
        bindEntityLinkTooltips(previewDialogBody, resolveUrl, previewMount);
    }

    function emitContentInput() {
        if (hiddenContent) {
            hiddenContent.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    function destroyCm() {
        if (cmView) {
            if (cmContextMenuCleanup) {
                cmContextMenuCleanup();
                cmContextMenuCleanup = null;
            }
            cmView.destroy();
            cmView = null;
        }
        if (cmHostEl) {
            cmHostEl.innerHTML = '';
        }
    }

    function initCm(initialValue) {
        destroyCm();
        try {
            const view = new EditorView({
                doc: initialValue,
                extensions: [
                    ...noemaMarkupEditorExtensions,
                    EditorView.lineWrapping,
                    noemaHighlightPlugin(),
                    EditorView.theme({
                        '&': { maxHeight: cmRootMaxHeight },
                        '.cm-scroller': {
                            fontFamily: 'ui-monospace, monospace',
                            fontSize: '13px',
                            overflowX: 'hidden',
                        },
                        '.cm-content': {
                            caretColor: 'var(--color-base-content, #f4f4f5)',
                        },
                        '.cm-noema-tag': { color: 'var(--color-accent, #7c3aed)' },
                        '.cm-noema-escape': { color: 'var(--color-base-content, #ccc)', opacity: 0.55 },
                        '.cm-activeLine': { backgroundColor: 'transparent' },
                        '.cm-activeLineGutter': { backgroundColor: 'transparent' },
                        '.cm-cursor, .cm-dropCursor': {
                            borderLeft: '2px solid var(--color-base-content, #f4f4f5)',
                            marginLeft: '-1px',
                        },
                        '&.cm-focused > .cm-scroller > .cm-cursorLayer': {
                            animation: 'none',
                        },
                        '&.cm-focused > .cm-scroller > .cm-cursorLayer .cm-cursor': {
                            display: 'block',
                            opacity: '1',
                        },
                        '&.cm-focused > .cm-scroller > .cm-selectionLayer .cm-selectionBackground': {
                            background: 'color-mix(in oklab, var(--color-primary, #8b5cf6) 30%, transparent)',
                        },
                    }),
                    EditorView.updateListener.of((u) => {
                        if (u.docChanged && hiddenContent) {
                            hiddenContent.value = u.state.doc.toString();
                            emitContentInput();
                            if (previewDialogBody && previewDialog?.open) {
                                renderPreviewDialogContent(hiddenContent.value);
                            }
                        }
                    }),
                ],
                parent: cmHostEl,
            });
            cmView = view;

            const openCtx = (e) => {
                e.preventDefault();
                e.stopPropagation();
                showCtxMenu(e.clientX, e.clientY);
            };
            const onPointerDown = (e) => {
                if (e.button !== 2) {
                    return;
                }
                openCtx(e);
            };
            /** Панель форматирования сразу после выделения (мышь/тач); правый клик по-прежнему открывает то же меню в точке клика. */
            const onEditorPointerUp = (e) => {
                if (e.pointerType === 'mouse' && e.button !== 0) {
                    return;
                }
                if (activeCtxMenuEl?.contains(/** @type {Node} */ (e.target))) {
                    return;
                }
                requestAnimationFrame(() => {
                    if (!cmView) {
                        return;
                    }
                    if (e.target != null && !cmView.dom.contains(/** @type {Node} */ (e.target))) {
                        return;
                    }
                    const sel = cmView.state.selection.main;
                    if (sel.from === sel.to) {
                        hideActiveCtxMenu();
                        return;
                    }
                    const coords = cmView.coordsAtPos(sel.head);
                    if (!coords) {
                        return;
                    }
                    showCtxMenu((coords.left + coords.right) / 2, coords.bottom + 8);
                });
            };
            const onScrollerScroll = () => {
                hideActiveCtxMenu();
            };
            const dom = view.dom;
            dom.addEventListener('contextmenu', openCtx, { capture: true });
            dom.addEventListener('pointerdown', onPointerDown, { capture: true });
            dom.addEventListener('pointerup', onEditorPointerUp);
            view.scrollDOM.addEventListener('scroll', onScrollerScroll, { passive: true });
            cmContextMenuCleanup = () => {
                dom.removeEventListener('contextmenu', openCtx, { capture: true });
                dom.removeEventListener('pointerdown', onPointerDown, { capture: true });
                dom.removeEventListener('pointerup', onEditorPointerUp);
                view.scrollDOM.removeEventListener('scroll', onScrollerScroll);
            };
            requestAnimationFrame(() => {
                view.requestMeasure();
            });
        } catch (err) {
            try {
                window.__noemaCmInitError = err instanceof Error ? err : new Error(String(err));
            } catch {
                /* ignore */
            }
            console.error('Noema markup editor init failed', err);
            const ta = document.createElement('textarea');
            ta.setAttribute('data-noema-markup-fallback', '1');
            ta.className =
                'textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 font-mono text-sm min-h-[12rem] whitespace-pre-wrap break-words';
            ta.value = initialValue;
            ta.addEventListener('input', () => {
                if (hiddenContent) {
                    hiddenContent.value = ta.value;
                    emitContentInput();
                }
                if (previewDialogBody && previewDialog?.open) {
                    renderPreviewDialogContent(hiddenContent.value);
                }
            });
            cmHostEl.appendChild(ta);
        }
    }

    function showViewMode() {
        if (previewDialog) {
            previewDialog.close();
        }
        if (hiddenContent) {
            renderViewFromSource(hiddenContent.value);
        }
        viewWrap.classList.remove('hidden');
        editWrap.classList.add('hidden');
    }

    function showEditMode() {
        viewWrap.classList.add('hidden');
        editWrap.classList.remove('hidden');
        const fallbackTa = cmHostEl?.querySelector('textarea[data-noema-markup-fallback]');
        if (fallbackTa) {
            requestAnimationFrame(() => fallbackTa.focus());
            return;
        }
        if (!cmView) {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    initCm(hiddenContent?.value ?? '');
                    requestAnimationFrame(() => {
                        cmView?.requestMeasure?.();
                        cmView?.focus();
                        const ta = cmHostEl?.querySelector('textarea[data-noema-markup-fallback]');
                        ta?.focus();
                    });
                });
            });
        } else {
            cmView.dispatch({
                changes: { from: 0, to: cmView.state.doc.length, insert: hiddenContent?.value ?? '' },
            });
            requestAnimationFrame(() => {
                cmView?.requestMeasure?.();
                cmView?.focus();
            });
        }
    }

    const api = {
        /** @param {string} content */
        syncFromServer(content) {
            if (previewDialog) {
                previewDialog.close();
            }
            if (hiddenContent) {
                hiddenContent.value = content;
                emitContentInput();
            }
            renderViewFromSource(content);
            destroyCm();
            viewWrap.classList.remove('hidden');
            editWrap.classList.add('hidden');
        },
        showViewMode,
        showEditMode,
        getContent() {
            return hiddenContent?.value ?? '';
        },
        syncBeforeSubmit() {
            if (editWrap && !editWrap.classList.contains('hidden')) {
                if (cmView) {
                    hiddenContent.value = cmView.state.doc.toString();
                } else {
                    const ta = cmHostEl?.querySelector('textarea[data-noema-markup-fallback]');
                    if (ta) {
                        hiddenContent.value = ta.value;
                    }
                }
            }
        },
        destroyCm,
    };

    const markupEditBoundary = opts.markupEditBoundary;
    if (markupEditBoundary != null && typeof markupEditBoundary.contains === 'function') {
        function eventTargetInsideBoundary(e) {
            if (typeof e.composedPath === 'function') {
                for (const node of e.composedPath()) {
                    if (node === markupEditBoundary) {
                        return true;
                    }
                    if (node instanceof Node && markupEditBoundary.contains(node)) {
                        return true;
                    }
                }
                return false;
            }
            let t = e.target;
            if (t && t.nodeType === Node.TEXT_NODE) {
                t = t.parentElement;
            }
            return t instanceof Node && markupEditBoundary.contains(t);
        }

        function onMaybeCollapseToViewMode(e) {
            if (e.button !== 0) {
                return;
            }
            if (!editWrap || editWrap.classList.contains('hidden')) {
                return;
            }
            if (eventTargetInsideBoundary(e)) {
                return;
            }
            const rawT = e.target;
            const t =
                rawT && rawT.nodeType === Node.TEXT_NODE ? rawT.parentElement : rawT;
            if (t instanceof Node && activeCtxMenuEl?.contains(t)) {
                return;
            }
            if (
                linkModal instanceof HTMLDialogElement &&
                linkModal.open &&
                t instanceof Node &&
                linkModal.contains(t)
            ) {
                return;
            }
            if (t instanceof Element && t.closest?.('.dropdown-content')) {
                return;
            }
            api.syncBeforeSubmit();
            showViewMode();
        }

        /** Как клик вне области: Esc сворачивает редактор в просмотр (не закрывает родительский диалог модалки карточки). */
        function onEscapeCollapseToViewMode(e) {
            if (e.key !== 'Escape') {
                return;
            }
            if (!editWrap || editWrap.classList.contains('hidden')) {
                return;
            }
            if (linkModal instanceof HTMLDialogElement && linkModal.open) {
                return;
            }
            if (previewDialog instanceof HTMLDialogElement && previewDialog.open) {
                return;
            }
            const ae = document.activeElement;
            if (ae && linkModal instanceof HTMLDialogElement && linkModal.contains(ae)) {
                return;
            }
            if (ae instanceof Element && ae.closest?.('.dropdown-content')) {
                return;
            }
            hideActiveCtxMenu();
            e.preventDefault();
            e.stopPropagation();
            api.syncBeforeSubmit();
            showViewMode();
        }

        document.addEventListener('mousedown', onMaybeCollapseToViewMode, true);
        document.addEventListener('pointerdown', onMaybeCollapseToViewMode, true);
        document.addEventListener('keydown', onEscapeCollapseToViewMode, true);
        formEl?.addEventListener('mousedown', onMaybeCollapseToViewMode, true);
        formEl?.addEventListener('pointerdown', onMaybeCollapseToViewMode, true);
    }

    if (globalApiKey) {
        window[globalApiKey] = api;
    }

    function showCtxMenu(x, y) {
        hideActiveCtxMenu();
        if (!cmView) {
            return;
        }
        const ctxMenu = document.createElement('div');
        activeCtxMenuEl = ctxMenu;
        ctxMenu.className =
            'fixed z-[100000] flex gap-0.5 rounded border border-base-300 bg-base-200 p-1 shadow-lg';
        ctxMenu.style.left = `${x}px`;
        ctxMenu.style.top = `${y}px`;
        const mkBtn = (title, svgPath, fn) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.title = title;
            b.className = 'btn btn-ghost btn-xs btn-square rounded-none';
            b.innerHTML = svgPath;
            b.addEventListener('click', () => {
                fn();
                hideActiveCtxMenu();
            });
            return b;
        };
        const svg = {
            bold: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>',
            italic:
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>',
            underline:
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 4v6a6 6 0 0 0 12 0V4"/><line x1="4" y1="20" x2="20" y2="20"/></svg>',
            strike:
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 4H9a3 3 0 0 0-2.83 4"/><path d="M14 12a4 4 0 0 1 0 8H6"/><line x1="4" y1="12" x2="20" y2="12"/></svg>',
            link: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
        };
        ctxMenu.append(
            mkBtn('Жирный', svg.bold, () => wrapSelection(cmView, '[b]', '[/b]')),
            mkBtn('Курсив', svg.italic, () => wrapSelection(cmView, '[i]', '[/i]')),
            mkBtn('Подчёркнутый', svg.underline, () => wrapSelection(cmView, '[u]', '[/u]')),
            mkBtn('Зачёркнутый', svg.strike, () => wrapSelection(cmView, '[s]', '[/s]')),
            mkBtn('Ссылка на сущность', svg.link, () => openLinkModal())
        );
        const menuRoot = cmHostEl.closest('dialog') ?? document.body;
        menuRoot.appendChild(ctxMenu);
        ctxMenu.addEventListener('click', (e) => e.stopPropagation());
        const clamp = () => {
            const r = ctxMenu.getBoundingClientRect();
            if (r.right > window.innerWidth) {
                ctxMenu.style.left = `${window.innerWidth - r.width - 8}px`;
            }
            if (r.bottom > window.innerHeight) {
                ctxMenu.style.top = `${y - r.height - 8}px`;
            }
        };
        requestAnimationFrame(clamp);
    }

    function openLinkModal() {
        hideActiveCtxMenu();
        if (!entitiesUrl || !resolveUrl) {
            return;
        }
        if (!linkModal || !linkModuleSelect || !linkEntitySelect) {
            return;
        }
        pendingLinkCmView = cmView;
        linkModuleSelect.innerHTML = '';
        for (const o of MODULE_OPTIONS) {
            const opt = document.createElement('option');
            opt.value = String(o.value);
            opt.textContent = o.label;
            linkModuleSelect.appendChild(opt);
        }
        linkEntitySelect.innerHTML = '<option value="">—</option>';
        linkModal.showModal();
        loadEntitiesForModuleShared(
            linkEntitySelect,
            parseInt(linkModuleSelect.value, 10),
            entitiesUrl
        );
    }

    const openFromPreview = (e) => {
        if (e.button !== 0) {
            return;
        }
        const t = e.target;
        if (!(t instanceof Node) || !viewEl.contains(t)) {
            return;
        }
        showEditMode();
    };
    formEl?.addEventListener('pointerdown', openFromPreview, true);

    if (previewToggleBtn && previewDialog && previewDialogBody) {
        previewToggleBtn.addEventListener('click', () => {
            let text = '';
            if (cmView) {
                text = cmView.state.doc.toString();
            } else {
                const ta = cmHostEl?.querySelector('textarea[data-noema-markup-fallback]');
                if (!ta) {
                    return;
                }
                text = ta.value;
            }
            hiddenContent.value = text;
            emitContentInput();
            renderPreviewDialogContent(text);
            previewDialog.showModal();
        });

        previewDialog.querySelectorAll('[data-markup-preview-close]').forEach((el) => {
            el.addEventListener('click', () => previewDialog.close());
        });
    }

    formEl?.addEventListener('submit', () => {
        api.syncBeforeSubmit();
    });

    renderViewFromSource(hiddenContent?.value ?? '');
}

/**
 * Редактор в модалке карточки истории (глобальный API window.noemaCardMarkup).
 * @param {object} opts
 */
export function bindCardMarkupEditor(opts) {
    return bindNoemaMarkupField({
        ...opts,
        formEl: opts.formEl ?? document.getElementById('editForm'),
        globalApiKey: 'noemaCardMarkup',
    });
}
