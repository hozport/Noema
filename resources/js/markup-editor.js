import { RangeSetBuilder } from '@codemirror/state';
import { Decoration, EditorView, ViewPlugin } from '@codemirror/view';
import { basicSetup } from 'codemirror';
import { MODULE_OPTIONS, parseAndRenderHtml } from './noema-markup.js';

/** @type {EditorView|null} */
let cmView = null;

/** @type {(() => void)|null} */
let tooltipHideTimer = null;

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

/**
 * @param {object} opts
 * @param {HTMLElement|null} opts.root
 * @param {HTMLElement|null} opts.viewWrap
 * @param {HTMLElement|null} opts.viewEl
 * @param {HTMLElement|null} opts.editWrap
 * @param {HTMLElement|null} opts.cmHost
 * @param {HTMLElement|null} opts.previewEl
 * @param {HTMLInputElement|null} opts.hiddenContent
 * @param {HTMLButtonElement|null} opts.doneBtn
 * @param {HTMLDialogElement|null} opts.linkModal
 * @param {HTMLSelectElement|null} opts.linkModuleSelect
 * @param {HTMLSelectElement|null} opts.linkEntitySelect
 * @param {HTMLButtonElement|null} opts.linkModalConfirm
 * @param {HTMLButtonElement|null} opts.linkModalCancel
 * @param {string} opts.entitiesUrl
 * @param {string} opts.resolveUrl
 */
export function bindCardMarkupEditor(opts) {
    const {
        viewWrap,
        viewEl,
        editWrap,
        cmHost,
        previewEl,
        hiddenContent,
        doneBtn,
        linkModal,
        linkModuleSelect,
        linkEntitySelect,
        linkModalConfirm,
        linkModalCancel,
        entitiesUrl,
        resolveUrl,
    } = opts;

    if (!viewEl || !hiddenContent || !cmHost || !editWrap || !viewWrap) {
        return;
    }

    /** @type {Map<string, { title: string, description: string, image_url: string|null }>} */
    const previewCache = new Map();

    /** @type {HTMLElement|null} */
    let tooltipEl = null;

    function ensureTooltip() {
        if (tooltipEl) {
            return tooltipEl;
        }
        tooltipEl = document.createElement('div');
        tooltipEl.className =
            'noema-entity-tooltip pointer-events-none fixed z-[100] max-w-xs rounded border border-base-300 bg-base-200 p-2 text-sm shadow-lg opacity-0 transition-opacity';
        tooltipEl.setAttribute('role', 'tooltip');
        tooltipEl.style.display = 'none';
        document.body.appendChild(tooltipEl);
        return tooltipEl;
    }

    /**
     * @param {string} key
     */
    async function getPreview(key) {
        if (previewCache.has(key)) {
            return previewCache.get(key);
        }
        const [mod, ent] = key.split(':').map((x) => parseInt(x, 10));
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(resolveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': token || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ refs: [{ module: mod, entity: ent }] }),
        });
        if (!res.ok) {
            return null;
        }
        const data = await res.json();
        const p = data.previews?.[key] ?? null;
        if (p) {
            previewCache.set(key, p);
        }
        return p;
    }

    /**
     * @param {HTMLElement} container
     */
    function bindTooltipHosts(container) {
        container.querySelectorAll('a.noema-entity-link').forEach((a) => {
            if (a.dataset.noemaTooltipBound) {
                return;
            }
            a.dataset.noemaTooltipBound = '1';
            a.addEventListener('click', (e) => e.preventDefault());
            a.addEventListener('mouseenter', async (e) => {
                const el = /** @type {HTMLElement} */ (e.currentTarget);
                const mod = el.getAttribute('data-noema-module');
                const ent = el.getAttribute('data-noema-entity');
                if (!mod || !ent) {
                    return;
                }
                const key = `${mod}:${ent}`;
                const tip = ensureTooltip();
                const p = await getPreview(key);
                if (!p) {
                    tip.innerHTML =
                        '<p class="text-base-content/70">Нет данных</p>';
                } else {
                    const img = p.image_url
                        ? `<img src="${escapeAttr(p.image_url)}" alt="" class="mb-2 max-h-24 w-full object-contain" loading="lazy" />`
                        : '';
                    tip.innerHTML = `${img}<div class="font-medium">${escapeHtml(
                        p.title || ''
                    )}</div><p class="mt-1 text-xs text-base-content/80">${escapeHtml(
                        p.description || ''
                    )}</p>`;
                }
                tip.style.display = 'block';
                tip.style.opacity = '0';
                const r = el.getBoundingClientRect();
                tip.style.left = `${Math.min(r.left, window.innerWidth - 320)}px`;
                tip.style.top = `${r.bottom + 8}px`;
                requestAnimationFrame(() => {
                    tip.style.opacity = '1';
                });
            });
            a.addEventListener('mouseleave', () => {
                if (tooltipHideTimer) {
                    clearTimeout(tooltipHideTimer);
                }
                tooltipHideTimer = setTimeout(() => {
                    const tip = ensureTooltip();
                    tip.style.opacity = '0';
                    tip.style.display = 'none';
                }, 120);
            });
        });
    }

    function escapeHtml(s) {
        return s
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function escapeAttr(s) {
        return String(s).replace(/"/g, '&quot;');
    }

    function renderViewFromSource(source) {
        const { html } = parseAndRenderHtml(source);
        viewEl.innerHTML = html || '<span class="text-base-content/40">(пусто)</span>';
        bindTooltipHosts(viewEl);
    }

    function renderPreviewFromSource(source) {
        if (!previewEl) {
            return;
        }
        const { html } = parseAndRenderHtml(source);
        previewEl.innerHTML =
            html ||
            '<span class="text-base-content/40">Превью появится после ввода разметки</span>';
        bindTooltipHosts(previewEl);
    }

    function emitContentInput() {
        if (hiddenContent) {
            hiddenContent.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    function destroyCm() {
        if (cmView) {
            cmView.destroy();
            cmView = null;
        }
    }

    function initCm(initialValue) {
        destroyCm();
        cmHost.innerHTML = '';
        cmView = new EditorView({
            doc: initialValue,
            extensions: [
                basicSetup,
                noemaHighlightPlugin(),
                EditorView.theme({
                    '&': { maxHeight: '22rem' },
                    '.cm-scroller': { fontFamily: 'ui-monospace, monospace', fontSize: '13px' },
                    '.cm-noema-tag': { color: 'var(--color-accent, #7c3aed)' },
                    '.cm-noema-escape': { color: 'var(--color-base-content, #ccc)', opacity: 0.55 },
                }),
                EditorView.updateListener.of((u) => {
                    if (u.docChanged && hiddenContent) {
                        hiddenContent.value = u.state.doc.toString();
                        emitContentInput();
                        renderPreviewFromSource(hiddenContent.value);
                    }
                }),
            ],
            parent: cmHost,
        });
    }

    function showViewMode() {
        if (hiddenContent) {
            renderViewFromSource(hiddenContent.value);
        }
        viewWrap.classList.remove('hidden');
        editWrap.classList.add('hidden');
    }

    function showEditMode() {
        if (!cmView) {
            initCm(hiddenContent?.value ?? '');
        } else {
            cmView.dispatch({
                changes: { from: 0, to: cmView.state.doc.length, insert: hiddenContent?.value ?? '' },
            });
        }
        viewWrap.classList.add('hidden');
        editWrap.classList.remove('hidden');
        renderPreviewFromSource(hiddenContent?.value ?? '');
        requestAnimationFrame(() => cmView?.focus());
    }

    window.noemaCardMarkup = {
        /** @param {string} content */
        syncFromServer(content) {
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
            if (editWrap && !editWrap.classList.contains('hidden') && cmView) {
                hiddenContent.value = cmView.state.doc.toString();
            }
        },
        destroyCm,
    };

    viewEl.addEventListener('dblclick', (e) => {
        e.preventDefault();
        showEditMode();
    });

    doneBtn?.addEventListener('click', () => {
        if (cmView) {
            hiddenContent.value = cmView.state.doc.toString();
        }
        emitContentInput();
        showViewMode();
    });

    /** @type {HTMLElement|null} */
    let ctxMenu = null;

    function hideCtxMenu() {
        if (ctxMenu) {
            ctxMenu.remove();
            ctxMenu = null;
        }
    }

    function showCtxMenu(x, y) {
        hideCtxMenu();
        if (!cmView) {
            return;
        }
        ctxMenu = document.createElement('div');
        ctxMenu.className =
            'fixed z-[9999] flex gap-0.5 rounded border border-base-300 bg-base-200 p-1 shadow-lg';
        ctxMenu.style.left = `${x}px`;
        ctxMenu.style.top = `${y}px`;
        const mkBtn = (label, title, svgPath, fn) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.title = title;
            b.className = 'btn btn-ghost btn-xs btn-square rounded-none';
            b.innerHTML = svgPath;
            b.addEventListener('click', () => {
                fn();
                hideCtxMenu();
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
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="16" y1="4" x2="8" y2="4"/><line x1="12" y1="12" x2="8" y2="12"/><line x1="20" y1="12" x2="8" y2="12"/><line x1="20" y1="20" x2="4" y2="20"/></svg>',
            link: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
        };
        ctxMenu.append(
            mkBtn('B', 'Жирный', svg.bold, () => wrapSelection(cmView, '[b]', '[/b]')),
            mkBtn('I', 'Курсив', svg.italic, () => wrapSelection(cmView, '[i]', '[/i]')),
            mkBtn('U', 'Подчёркнутый', svg.underline, () => wrapSelection(cmView, '[u]', '[/u]')),
            mkBtn('S', 'Зачёркнутый', svg.strike, () => wrapSelection(cmView, '[s]', '[/s]')),
            mkBtn('↗', 'Ссылка на сущность', svg.link, () => openLinkModal())
        );
        document.body.appendChild(ctxMenu);
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

    document.addEventListener('click', hideCtxMenu);

    cmHost?.addEventListener('contextmenu', (e) => {
        if (editWrap.classList.contains('hidden')) {
            return;
        }
        e.preventDefault();
        showCtxMenu(e.clientX, e.clientY);
    });

    function openLinkModal() {
        hideCtxMenu();
        if (!linkModal || !linkModuleSelect || !linkEntitySelect) {
            return;
        }
        linkModuleSelect.innerHTML = '';
        for (const o of MODULE_OPTIONS) {
            const opt = document.createElement('option');
            opt.value = String(o.value);
            opt.textContent = o.label;
            linkModuleSelect.appendChild(opt);
        }
        linkEntitySelect.innerHTML = '<option value="">—</option>';
        linkModal.showModal();
        loadEntitiesForModule(parseInt(linkModuleSelect.value, 10));
    }

    async function loadEntitiesForModule(mod) {
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

    linkModuleSelect?.addEventListener('change', () => {
        loadEntitiesForModule(parseInt(linkModuleSelect.value, 10));
    });

    linkModalConfirm?.addEventListener('click', () => {
        if (!cmView || !linkModuleSelect || !linkEntitySelect) {
            return;
        }
        const mod = parseInt(linkModuleSelect.value, 10);
        const ent = parseInt(linkEntitySelect.value, 10);
        if (!Number.isFinite(ent) || ent < 1) {
            return;
        }
        const open = `[link module=${mod} entity=${ent}]`;
        const close = '[/link]';
        wrapSelection(cmView, open, close);
        linkModal?.close();
    });

    linkModalCancel?.addEventListener('click', () => {
        linkModal?.close();
    });

    linkModal?.querySelectorAll('[data-link-modal-close]').forEach((el) => {
        el.addEventListener('click', () => linkModal?.close());
    });

    document.getElementById('editForm')?.addEventListener('submit', () => {
        window.noemaCardMarkup?.syncBeforeSubmit();
    });
}
