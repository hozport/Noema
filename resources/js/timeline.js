/**
 * Таймлайн: перекрестье, контекстное меню (линия / событие / холст), модалки создания и редактирования.
 */
function readAxisConfig() {
    const el = document.getElementById('timeline-axis-config');
    if (!el?.textContent) {
        return null;
    }
    try {
        return JSON.parse(el.textContent);
    } catch {
        return null;
    }
}

function readTimelineLinesConfig() {
    const el = document.getElementById('timeline-lines-config');
    if (!el?.textContent) {
        return [];
    }
    try {
        const raw = JSON.parse(el.textContent);
        return Array.isArray(raw) ? raw : [];
    } catch {
        return [];
    }
}

function readTimelineEventsConfig() {
    const el = document.getElementById('timeline-events-config');
    if (!el?.textContent) {
        return [];
    }
    try {
        const raw = JSON.parse(el.textContent);
        return Array.isArray(raw) ? raw : [];
    } catch {
        return [];
    }
}

function readTimelinePageMeta() {
    const el = document.getElementById('timeline-page-meta');
    if (!el?.textContent) {
        return { worldId: 0, csrf: '', urls: {} };
    }
    try {
        return JSON.parse(el.textContent);
    } catch {
        return { worldId: 0, csrf: '', urls: {} };
    }
}

function hideTimelineContextMenu() {
    const contextMenu = document.getElementById('timeline-context-menu');
    if (!contextMenu) {
        return;
    }
    contextMenu.classList.add('hidden', 'opacity-0', 'invisible');
    contextMenu.innerHTML = '';
    contextMenu.setAttribute('aria-hidden', 'true');
}

/**
 * @param {number} left
 * @param {number} top
 * @param {HTMLElement} el
 */
function clampFixedToViewport(left, top, el) {
    const pad = 10;
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const w = el.offsetWidth || 1;
    const h = el.offsetHeight || 1;
    let l = left;
    let t = top;
    if (l + w > vw - pad) {
        l = vw - w - pad;
    }
    if (t + h > vh - pad) {
        t = vh - h - pad;
    }
    l = Math.max(pad, l);
    t = Math.max(pad, t);
    el.style.left = `${l}px`;
    el.style.top = `${t}px`;
}

/**
 * @param {boolean} isMain
 */
function applyTimelineEventBreaksLineUi(isMain) {
    const label = document.getElementById('timeline-event-breaks-line-label');
    const cb = document.getElementById('timeline-event-breaks-line');
    if (!label || !cb) {
        return;
    }
    if (isMain) {
        label.classList.add('hidden');
        cb.checked = false;
        cb.disabled = true;
    } else {
        label.classList.remove('hidden');
        cb.disabled = false;
    }
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || readTimelinePageMeta().csrf || '';
}

/**
 * @param {string} method
 * @param {HTMLFormElement} form
 */
function setFormHttpMethod(form, method) {
    let spoof = form.querySelector('input[name="_method"][data-timeline-spoof]');
    if (method === 'POST') {
        if (spoof) {
            spoof.remove();
        }
        return;
    }
    if (!spoof) {
        spoof = document.createElement('input');
        spoof.type = 'hidden';
        spoof.name = '_method';
        spoof.setAttribute('data-timeline-spoof', '1');
        form.insertBefore(spoof, form.firstChild?.nextSibling || null);
    }
    spoof.value = method;
}

/**
 * @param {string} url
 */
function submitTimelineDelete(url) {
    const token = getCsrfToken();
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = token;
    const method = document.createElement('input');
    method.type = 'hidden';
    method.name = '_method';
    method.value = 'DELETE';
    form.appendChild(csrf);
    form.appendChild(method);
    document.body.appendChild(form);
    form.submit();
}

function initTimelineModals() {
    const lineDialog = document.getElementById('timeline-line-dialog');
    const eventDialog = document.getElementById('timeline-event-dialog');
    const lineForm = document.getElementById('timeline-line-form');
    const eventForm = document.getElementById('timeline-event-form');
    const meta = readTimelinePageMeta();
    const linesConfig = readTimelineLinesConfig();
    const eventsConfig = readTimelineEventsConfig();

    function closeDialogs() {
        lineDialog?.close();
        eventDialog?.close();
    }

    document.querySelectorAll('[data-timeline-dialog-close]').forEach((el) => {
        el.addEventListener('click', () => closeDialogs());
    });

    function findLine(lineId) {
        return linesConfig.find((l) => Number(l.id) === Number(lineId));
    }

    function findEvent(eventId) {
        return eventsConfig.find((e) => Number(e.id) === Number(eventId));
    }

    function resetLineFormToCreate() {
        if (!lineForm) {
            return;
        }
        lineForm.action = lineForm.dataset.storeUrl || lineForm.getAttribute('action') || '';
        setFormHttpMethod(lineForm, 'POST');
        const ctx = document.getElementById('timeline-line-form-context');
        if (ctx) {
            ctx.value = 'line_create';
        }
        const eid = document.getElementById('timeline-line-edit-id');
        if (eid) {
            eid.value = '';
            eid.disabled = true;
        }
        const title = document.getElementById('timeline-line-dialog-title');
        if (title) {
            title.textContent = 'Новая линия';
        }
        const sub = document.getElementById('timeline-line-submit');
        if (sub) {
            sub.textContent = 'Создать линию';
        }
    }

    /**
     * @param {number|string} lineId
     */
    function openLineEditDialog(lineId) {
        if (!lineDialog || !lineForm) {
            return;
        }
        const line = findLine(lineId);
        if (!line) {
            return;
        }
        resetLineFormToCreate();
        lineForm.action = (meta.urls?.lineUpdate || '').replace('__ID__', String(lineId));
        setFormHttpMethod(lineForm, 'PUT');
        const ctx = document.getElementById('timeline-line-form-context');
        if (ctx) {
            ctx.value = 'line_edit';
        }
        const eid = document.getElementById('timeline-line-edit-id');
        if (eid) {
            eid.value = String(lineId);
            eid.disabled = false;
        }
        const nameInput = lineForm.querySelector('input[name="name"]');
        if (nameInput) {
            nameInput.value = line.name || '';
        }
        const sy = document.getElementById('timeline-line-start-year');
        const ey = document.getElementById('timeline-line-end-year');
        const col = document.getElementById('timeline-line-color');
        if (sy) {
            sy.value = String(line.start_year ?? 0);
        }
        if (ey) {
            ey.value = line.end_year != null ? String(line.end_year) : '';
        }
        if (col) {
            col.value = line.color || '#457B9D';
        }
        const title = document.getElementById('timeline-line-dialog-title');
        if (title) {
            title.textContent = 'Редактировать линию';
        }
        const sub = document.getElementById('timeline-line-submit');
        if (sub) {
            sub.textContent = 'Сохранить';
        }
        lineDialog.showModal();
    }

    /**
     * @param {{ startYear?: number, clearEnd?: boolean }} prefill
     */
    function openLineDialog(prefill = {}) {
        if (!lineDialog || !lineForm) {
            return;
        }
        lineForm.reset();
        resetLineFormToCreate();
        const colorInput = lineForm.querySelector('input[name="color"]');
        if (colorInput) {
            colorInput.value = '#457B9D';
        }
        const sy = document.getElementById('timeline-line-start-year');
        const ey = document.getElementById('timeline-line-end-year');
        if (sy) {
            sy.value = typeof prefill.startYear === 'number' ? String(prefill.startYear) : '0';
        }
        if (ey) {
            ey.value = '';
        }
        lineDialog.showModal();
    }

    function syncBreaksLineFromLineSelect() {
        const sel = document.getElementById('timeline-event-line-id');
        if (!sel || sel.disabled) {
            return;
        }
        const opt = sel.options[sel.selectedIndex];
        const fromAttr = opt?.getAttribute('data-is-main');
        let isMain = fromAttr === '1';
        if (fromAttr === null || fromAttr === '') {
            const lines = readTimelineLinesConfig();
            const id = Number.parseInt(String(sel.value), 10);
            const found = lines.find((l) => Number(l.id) === id);
            isMain = !!found?.is_main;
        }
        applyTimelineEventBreaksLineUi(isMain);
    }

    function setEventLineFieldEditMode(isEdit, lineId) {
        const sel = document.getElementById('timeline-event-line-id');
        const wrap = document.getElementById('timeline-event-line-field-wrap');
        if (!sel || !wrap) {
            return;
        }
        let hidden = document.getElementById('timeline-event-line-id-hidden');
        if (isEdit) {
            sel.removeAttribute('name');
            sel.disabled = true;
            sel.removeAttribute('required');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.id = 'timeline-event-line-id-hidden';
                hidden.name = 'timeline_line_id';
                wrap.insertBefore(hidden, wrap.firstChild);
            }
            hidden.value = String(lineId);
            wrap.classList.add('opacity-60');
        } else {
            sel.setAttribute('name', 'timeline_line_id');
            sel.disabled = false;
            sel.setAttribute('required', 'required');
            hidden?.remove();
            wrap.classList.remove('opacity-60');
        }
    }

    function resetEventFormToCreate() {
        if (!eventForm) {
            return;
        }
        eventForm.action = eventForm.dataset.storeUrl || eventForm.getAttribute('action') || '';
        setFormHttpMethod(eventForm, 'POST');
        const ctx = document.getElementById('timeline-event-form-context');
        if (ctx) {
            ctx.value = 'event_create';
        }
        const eid = document.getElementById('timeline-event-edit-id');
        if (eid) {
            eid.value = '';
            eid.disabled = true;
        }
        setEventLineFieldEditMode(false, 0);
        const title = document.getElementById('timeline-event-dialog-title');
        if (title) {
            title.textContent = 'Новое событие';
        }
        const sub = document.getElementById('timeline-event-submit');
        if (sub) {
            sub.textContent = 'Создать событие';
        }
    }

    /**
     * @param {number|string} lineId
     */
    function resumeLineEditForm(lineId) {
        if (!lineDialog || !lineForm) {
            return;
        }
        lineForm.action = (meta.urls?.lineUpdate || '').replace('__ID__', String(lineId));
        setFormHttpMethod(lineForm, 'PUT');
        const ctx = document.getElementById('timeline-line-form-context');
        if (ctx) {
            ctx.value = 'line_edit';
        }
        const eid = document.getElementById('timeline-line-edit-id');
        if (eid) {
            eid.value = String(lineId);
            eid.disabled = false;
        }
        const title = document.getElementById('timeline-line-dialog-title');
        if (title) {
            title.textContent = 'Редактировать линию';
        }
        const sub = document.getElementById('timeline-line-submit');
        if (sub) {
            sub.textContent = 'Сохранить';
        }
        lineDialog.showModal();
    }

    /**
     * @param {number|string} eventId
     */
    function resumeEventEditForm(eventId) {
        if (!eventDialog || !eventForm) {
            return;
        }
        const ev = findEvent(eventId);
        if (!ev) {
            return;
        }
        eventForm.action = (meta.urls?.eventUpdate || '').replace('__ID__', String(eventId));
        setFormHttpMethod(eventForm, 'PUT');
        const ctx = document.getElementById('timeline-event-form-context');
        if (ctx) {
            ctx.value = 'event_edit';
        }
        const eid = document.getElementById('timeline-event-edit-id');
        if (eid) {
            eid.value = String(eventId);
            eid.disabled = false;
        }
        setEventLineFieldEditMode(true, ev.timeline_line_id);
        const ln = findLine(ev.timeline_line_id);
        applyTimelineEventBreaksLineUi(!!ln?.is_main);
        const title = document.getElementById('timeline-event-dialog-title');
        if (title) {
            title.textContent = 'Редактировать событие';
        }
        const sub = document.getElementById('timeline-event-submit');
        if (sub) {
            sub.textContent = 'Сохранить';
        }
        eventDialog.showModal();
    }

    /**
     * @param {number|string} eventId
     */
    function openEventEditDialog(eventId) {
        if (!eventDialog || !eventForm) {
            return;
        }
        const ev = findEvent(eventId);
        if (!ev) {
            return;
        }
        eventForm.reset();
        resetEventFormToCreate();
        eventForm.action = (meta.urls?.eventUpdate || '').replace('__ID__', String(eventId));
        setFormHttpMethod(eventForm, 'PUT');
        const ctx = document.getElementById('timeline-event-form-context');
        if (ctx) {
            ctx.value = 'event_edit';
        }
        const eid = document.getElementById('timeline-event-edit-id');
        if (eid) {
            eid.value = String(eventId);
            eid.disabled = false;
        }
        const sel = document.getElementById('timeline-event-line-id');
        if (sel) {
            sel.value = String(ev.timeline_line_id);
        }
        setEventLineFieldEditMode(true, ev.timeline_line_id);
        const tit = document.getElementById('timeline-event-title');
        if (tit) {
            tit.value = ev.title || '';
        }
        const ey = document.getElementById('timeline-event-epoch-year');
        const mo = document.getElementById('timeline-event-month');
        const day = document.getElementById('timeline-event-day');
        const br = document.getElementById('timeline-event-breaks-line');
        if (ey) {
            ey.value = String(ev.epoch_year ?? 0);
        }
        if (mo) {
            mo.value = String(ev.month ?? 1);
        }
        if (day) {
            day.value = String(ev.day ?? 1);
        }
        if (br) {
            br.checked = !!ev.breaks_line;
        }
        syncBreaksLineFromLineSelect();
        const title = document.getElementById('timeline-event-dialog-title');
        if (title) {
            title.textContent = 'Редактировать событие';
        }
        const sub = document.getElementById('timeline-event-submit');
        if (sub) {
            sub.textContent = 'Сохранить';
        }
        eventDialog.showModal();
    }

    /**
     * @param {{ lineId?: string, epochYear?: number }} prefill
     */
    function openEventDialog(prefill = {}) {
        if (!eventDialog || !eventForm) {
            return;
        }
        eventForm.reset();
        resetEventFormToCreate();
        const sel = document.getElementById('timeline-event-line-id');
        const ey = document.getElementById('timeline-event-epoch-year');
        const mo = document.getElementById('timeline-event-month');
        const day = document.getElementById('timeline-event-day');
        const br = document.getElementById('timeline-event-breaks-line');
        if (sel && prefill.lineId) {
            sel.value = String(prefill.lineId);
        }
        if (ey) {
            ey.value = typeof prefill.epochYear === 'number' ? String(prefill.epochYear) : '0';
        }
        if (mo) {
            mo.value = '1';
        }
        if (day) {
            day.value = '1';
        }
        if (br) {
            br.checked = false;
        }
        syncBreaksLineFromLineSelect();
        eventDialog.showModal();
    }

    document.getElementById('timeline-event-line-id')?.addEventListener('change', () => {
        syncBreaksLineFromLineSelect();
    });

    document.getElementById('timeline-open-line-dialog')?.addEventListener('click', () => {
        openLineDialog({ startYear: 0, clearEnd: true });
    });

    document.getElementById('timeline-open-event-dialog')?.addEventListener('click', () => {
        openEventDialog({});
    });

    const contextMenu = document.getElementById('timeline-context-menu');
    contextMenu?.addEventListener('click', (e) => {
        const createLine = e.target.closest('.timeline-ctx-create-line');
        const createEv = e.target.closest('.timeline-ctx-create-event');
        const editLine = e.target.closest('.timeline-ctx-edit-line');
        const delLine = e.target.closest('.timeline-ctx-delete-line');
        const editEv = e.target.closest('.timeline-ctx-edit-event');
        const delEv = e.target.closest('.timeline-ctx-delete-event');
        if (createLine) {
            const y = parseInt(createLine.getAttribute('data-year') || '0', 10);
            hideTimelineContextMenu();
            openLineDialog({ startYear: y, clearEnd: true });
        }
        if (createEv) {
            const y = parseInt(createEv.getAttribute('data-year') || '0', 10);
            const lid = createEv.getAttribute('data-line-id') || '';
            hideTimelineContextMenu();
            openEventDialog({ lineId: lid, epochYear: y });
        }
        if (editLine) {
            const lid = editLine.getAttribute('data-line-id') || '';
            hideTimelineContextMenu();
            if (lid) {
                openLineEditDialog(lid);
            }
        }
        if (delLine) {
            const lid = delLine.getAttribute('data-line-id') || '';
            const bio = delLine.getAttribute('data-biography-line') === '1';
            hideTimelineContextMenu();
            if (!lid) {
                return;
            }
            const msg = bio
                ? 'Удалить эту линию? Все события на ней исчезнут с таймлайна. В биографии персонажа записи событий останутся — их можно снова отправить на линию.'
                : 'Удалить линию и все события на ней?';
            if (!confirm(msg)) {
                return;
            }
            const url = (meta.urls?.lineDestroy || '').replace('__ID__', lid);
            if (url) {
                submitTimelineDelete(url);
            }
        }
        if (editEv) {
            const eid = editEv.getAttribute('data-event-id') || '';
            hideTimelineContextMenu();
            if (eid) {
                openEventEditDialog(eid);
            }
        }
        if (delEv) {
            const eid = delEv.getAttribute('data-event-id') || '';
            hideTimelineContextMenu();
            if (!eid) {
                return;
            }
            if (
                !confirm(
                    'Удалить это событие? Если оно связано с биографией, связь будет снята; запись в биографии останется, её можно снова отправить на таймлайн.',
                )
            ) {
                return;
            }
            const url = (meta.urls?.eventDestroy || '').replace('__ID__', eid);
            if (url) {
                submitTimelineDelete(url);
            }
        }
    });

    window.timelineOpenLineEdit = openLineEditDialog;
    window.timelineOpenEventEdit = openEventEditDialog;
    window.timelineResumeLineEdit = resumeLineEditForm;
    window.timelineResumeEventEdit = resumeEventEditForm;
}

function initTimelineCanvas() {
    const scroll = document.querySelector('.timeline-canvas-scroll');
    const inner = document.querySelector('.timeline-canvas-inner');
    const crosshair = document.getElementById('timeline-crosshair');
    const crosshairLine = document.getElementById('timeline-crosshair-line');
    const yearAtCursor = document.getElementById('timeline-year-at-cursor');
    const contextMenu = document.getElementById('timeline-context-menu');
    const tooltip = document.getElementById('timeline-point-tooltip');
    const axis = readAxisConfig();
    const linesConfig = readTimelineLinesConfig();
    const eventsConfigForMenu = readTimelineEventsConfig();

    if (!scroll || !inner || !crosshair || !crosshairLine || !axis || axis.tMax <= axis.tMin) {
        return;
    }

    const { tMin, tMax, canvasWidth } = axis;
    const range = tMax - tMin;

    function xToYear(x) {
        return Math.round(tMin + (x / canvasWidth) * range);
    }

    function showCrosshair(xPx, clientX, clientY) {
        crosshair.classList.remove('hidden');
        crosshairLine.style.left = `${xPx}px`;
        if (yearAtCursor) {
            const y = xToYear(xPx);
            yearAtCursor.textContent = `${y} г.`;
            yearAtCursor.classList.remove('hidden', 'opacity-0', 'invisible');
            const pad = 12;
            let left = clientX + pad;
            let top = clientY + pad;
            yearAtCursor.style.left = `${left}px`;
            yearAtCursor.style.top = `${top}px`;
            requestAnimationFrame(() => {
                if (!yearAtCursor) {
                    return;
                }
                clampFixedToViewport(left, top, yearAtCursor);
            });
        }
    }

    function hideCrosshair() {
        crosshair.classList.add('hidden');
        if (yearAtCursor) {
            yearAtCursor.classList.add('hidden', 'opacity-0', 'invisible');
        }
    }

    /** @type {MouseEvent | null} */
    let lastPointer = null;

    function handlePointer(e) {
        const rect = scroll.getBoundingClientRect();
        const x = scroll.scrollLeft + (e.clientX - rect.left);
        if (x < 0 || x > canvasWidth) {
            hideCrosshair();
            return;
        }
        showCrosshair(x, e.clientX, e.clientY);
    }

    scroll.addEventListener('mousemove', (e) => {
        lastPointer = e;
        handlePointer(e);
    });

    scroll.addEventListener('mouseleave', () => {
        lastPointer = null;
        hideCrosshair();
    });

    scroll.addEventListener('scroll', () => {
        if (lastPointer) {
            handlePointer(lastPointer);
        }
        hideTimelineContextMenu();
    });

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;

        return d.innerHTML;
    }

    function lineMeta(lineId) {
        const id = Number.parseInt(String(lineId), 10);
        const line = linesConfig.find((l) => Number(l.id) === id);
        return {
            isMain: !!line?.is_main,
            biographyLine: line?.source_biography_id != null && line.source_biography_id > 0,
        };
    }

    /**
     * @param {number} clientX
     * @param {number} clientY
     * @param {'canvas' | 'lineTrack' | 'point'} mode
     * @param {{ year: number, trackLabel?: string, lineId?: string, eventIds?: number[] }} payload
     */
    function showContextMenu(clientX, clientY, mode, payload) {
        if (!contextMenu) {
            return;
        }
        hideTooltip();
        const year = payload.year;
        const trackLabel = payload.trackLabel || '';
        const lineId = payload.lineId || '';
        const eventIds = Array.isArray(payload.eventIds) ? payload.eventIds : [];
        const metaLine = `<div class="px-2 py-1.5 text-[10px] text-base-content/72 border-t border-base-300/60">Год: <strong class="text-base-content">${year}</strong> г.</div>`;
        const metaEvent = `<div class="px-2 py-1.5 text-[10px] text-base-content/72 border-t border-base-300/60">${escapeHtml(trackLabel)} · <strong class="text-base-content">${year}</strong> г.</div>`;

        if (mode === 'canvas') {
            contextMenu.innerHTML = `<button type="button" role="menuitem" class="timeline-ctx-create-line w-full text-left px-2 py-2 text-sm font-medium hover:bg-base-200">Создать линию</button>${metaLine}`;
            const btn = contextMenu.querySelector('.timeline-ctx-create-line');
            if (btn) {
                btn.setAttribute('data-year', String(year));
            }
        } else if (mode === 'lineTrack') {
            const lm = lineMeta(lineId);
            const parts = [];
            parts.push(
                `<button type="button" role="menuitem" class="timeline-ctx-create-event w-full text-left px-2 py-2 text-sm font-medium hover:bg-base-200">Создать событие</button>`,
            );
            parts.push(
                `<button type="button" role="menuitem" class="timeline-ctx-edit-line w-full text-left px-2 py-2 text-sm font-medium hover:bg-base-200">Редактировать линию</button>`,
            );
            if (lm.isMain) {
                parts.push(
                    `<button type="button" role="menuitem" disabled class="w-full text-left px-2 py-2 text-sm font-medium opacity-50 cursor-not-allowed">Удалить линию (основная)</button>`,
                );
            } else {
                parts.push(
                    `<button type="button" role="menuitem" class="timeline-ctx-delete-line w-full text-left px-2 py-2 text-sm font-medium hover:bg-base-200 text-error">Удалить линию</button>`,
                );
            }
            parts.push(metaLine);
            contextMenu.innerHTML = parts.join('');
            const evBtn = contextMenu.querySelector('.timeline-ctx-create-event');
            if (evBtn) {
                evBtn.setAttribute('data-year', String(year));
                evBtn.setAttribute('data-line-id', lineId);
            }
            const edLine = contextMenu.querySelector('.timeline-ctx-edit-line');
            if (edLine) {
                edLine.setAttribute('data-line-id', lineId);
            }
            const delLine = contextMenu.querySelector('.timeline-ctx-delete-line');
            if (delLine) {
                delLine.setAttribute('data-line-id', lineId);
                if (lm.biographyLine) {
                    delLine.setAttribute('data-biography-line', '1');
                }
            }
        } else {
            const parts = [];
            eventIds.forEach((eid) => {
                const idStr = String(eid);
                const evRow = eventsConfigForMenu.find((e) => Number(e.id) === Number(eid));
                const titleShort = evRow?.title
                    ? escapeHtml(String(evRow.title).slice(0, 56))
                    : `Событие #${idStr}`;
                parts.push(`<div class="px-2 pt-2 text-[10px] text-base-content/70 max-w-[14rem] truncate">${titleShort}</div>`);
                parts.push(
                    `<button type="button" role="menuitem" class="timeline-ctx-edit-event w-full text-left px-2 py-1.5 text-sm font-medium hover:bg-base-200" data-event-id="${idStr}">Редактировать</button>`,
                );
                parts.push(
                    `<button type="button" role="menuitem" class="timeline-ctx-delete-event w-full text-left px-2 py-1.5 text-sm font-medium hover:bg-base-200 text-error" data-event-id="${idStr}">Удалить</button>`,
                );
            });
            parts.push(metaEvent);
            contextMenu.innerHTML = parts.join('');
        }

        contextMenu.classList.remove('hidden', 'opacity-0', 'invisible');
        contextMenu.setAttribute('aria-hidden', 'false');
        contextMenu.style.left = '0';
        contextMenu.style.top = '0';
        requestAnimationFrame(() => {
            if (!contextMenu) {
                return;
            }
            const pad = 6;
            let left = clientX + pad;
            let top = clientY + pad;
            contextMenu.style.left = `${left}px`;
            contextMenu.style.top = `${top}px`;
            clampFixedToViewport(left, top, contextMenu);
        });
    }

    scroll.addEventListener('contextmenu', (e) => {
        const rect = scroll.getBoundingClientRect();
        const x = scroll.scrollLeft + (e.clientX - rect.left);
        if (x < 0 || x > canvasWidth) {
            return;
        }
        e.preventDefault();
        const year = xToYear(x);
        const target = /** @type {Element} */ (e.target);
        const pointHit = target.closest?.('.timeline-point-hit');
        const lineHit = target.closest?.('.timeline-line-hit');

        if (pointHit) {
            const trackLabel = pointHit.getAttribute('data-track-label') || '';
            const lineId = pointHit.getAttribute('data-line-id') || '';
            let eventIds = [];
            const raw = pointHit.getAttribute('data-tooltip');
            if (raw) {
                try {
                    const tip = JSON.parse(raw);
                    if (Array.isArray(tip.eventIds)) {
                        eventIds = tip.eventIds.map((n) => Number.parseInt(String(n), 10)).filter((n) => !Number.isNaN(n));
                    }
                } catch {
                    /* ignore */
                }
            }
            if (eventIds.length === 0) {
                return;
            }
            showContextMenu(e.clientX, e.clientY, 'point', { year, trackLabel, lineId, eventIds });
        } else if (lineHit) {
            const lineId = lineHit.getAttribute('data-line-id') || '';
            showContextMenu(e.clientX, e.clientY, 'lineTrack', { year, lineId });
        } else {
            showContextMenu(e.clientX, e.clientY, 'canvas', { year });
        }
    });

    document.addEventListener('mousedown', (e) => {
        if (e.button !== 0) {
            return;
        }
        if (contextMenu && !contextMenu.classList.contains('invisible') && !contextMenu.contains(/** @type {Node} */ (e.target))) {
            hideTimelineContextMenu();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            hideTimelineContextMenu();
            document.getElementById('timeline-line-dialog')?.close();
            document.getElementById('timeline-event-dialog')?.close();
        }
    });

    /** @type {HTMLElement | null} */
    let tooltipTarget = null;

    function hideTooltip() {
        if (tooltip) {
            tooltip.classList.add('opacity-0', 'pointer-events-none', 'invisible');
            tooltip.innerHTML = '';
        }
        tooltipTarget = null;
    }

    function showTooltip(payload, anchorRect) {
        if (!tooltip || !payload) {
            return;
        }
        const lines = Array.isArray(payload.titles) ? payload.titles : [];
        const dates = Array.isArray(payload.exactDates) ? payload.exactDates : [];
        const parts = [];
        parts.push(`<div class="timeline-tooltip__line text-[10px] uppercase tracking-wide text-base-content/75 mb-1">${escapeHtml(payload.lineLabel || '')}</div>`);
        parts.push(`<div class="timeline-tooltip__year font-semibold text-base-content mb-1">${escapeHtml(String(payload.year))} г.</div>`);
        lines.forEach((t, i) => {
            const d = dates[i];
            parts.push(`<div class="text-sm text-base-content/95">• ${escapeHtml(t)}${d ? ` <span class="text-base-content/70 text-xs">(${escapeHtml(d)})</span>` : ''}</div>`);
        });
        if (payload.count > 1) {
            parts.push(`<div class="text-xs text-base-content/70 mt-1">Событий в году: ${payload.count}</div>`);
        }
        tooltip.innerHTML = parts.join('');
        tooltip.classList.remove('opacity-0', 'pointer-events-none', 'invisible');

        const pad = 12;
        requestAnimationFrame(() => {
            if (!tooltip) {
                return;
            }
            let left = anchorRect.left + anchorRect.width / 2;
            let top = anchorRect.top - pad;

            tooltip.style.left = '0';
            tooltip.style.top = '0';
            const tw = tooltip.offsetWidth;
            const th = tooltip.offsetHeight;
            left -= tw / 2;
            top -= th;

            const vw = window.innerWidth;
            const vh = window.innerHeight;
            left = Math.max(pad, Math.min(left, vw - tw - pad));
            top = Math.max(pad, Math.min(top, vh - th - pad));

            tooltip.style.left = `${left}px`;
            tooltip.style.top = `${top}px`;
        });
    }

    document.querySelectorAll('.timeline-point-hit[data-tooltip]').forEach((hit) => {
        hit.addEventListener('mouseenter', (e) => {
            const raw = hit.getAttribute('data-tooltip');
            if (!raw) {
                return;
            }
            try {
                const payload = JSON.parse(raw);
                tooltipTarget = hit;
                const rect = hit.getBoundingClientRect();
                showTooltip(payload, rect);
            } catch {
                /* ignore */
            }
        });
        hit.addEventListener('mouseleave', () => {
            if (tooltipTarget === hit) {
                hideTooltip();
            }
        });
    });

    document.addEventListener('scroll', () => {
        if (tooltipTarget) {
            hideTooltip();
        }
    }, true);
}

document.addEventListener('DOMContentLoaded', () => {
    initTimelineCanvas();
    initTimelineModals();
});
