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

function getTimelineAxis() {
    const axis = readAxisConfig();
    if (!axis || axis.tMax <= axis.tMin) {
        return null;
    }
    return axis;
}

/**
 * Текущий масштаб холста (1 = 100%). Учитывается в координатах года и границах перекрестья/меню.
 *
 * @returns {number}
 */
function getTimelineZoom() {
    const z = window.__timelineZoom;
    if (typeof z !== 'number' || z <= 0 || Number.isNaN(z)) {
        return 1;
    }
    return z;
}

/**
 * @param {number} z
 * @returns {number}
 */
function clampTimelineZoom(z) {
    return Math.min(4, Math.max(0.25, z));
}

/**
 * @returns {string}
 */
function timelineZoomStorageKey() {
    const meta = readTimelinePageMeta();
    const id = meta.worldId || document.body?.dataset?.worldId || '0';
    return `nqTimelineZoom:${id}`;
}

/**
 * Применяет масштаб к содержимому холста внутри `.timeline-canvas-scroll`.
 * Вьюпорт (область со скроллом) по размеру задаётся flex’ом страницы и не растёт от z.
 * При смене уровня зума якорь — центр вьюпорта или точка курсора (колёсико); без ухода в угол.
 *
 * @param {number} z
 * @param {{
 *   skipScrollPreserve?: boolean,
 *   focalOffsetX?: number,
 *   focalOffsetY?: number,
 * }} [options]
 */
function applyTimelineZoom(z, options = {}) {
    const { skipScrollPreserve = false, focalOffsetX, focalOffsetY } = options;
    z = clampTimelineZoom(z);
    const zPrev = getTimelineZoom();

    const scroll = document.querySelector('.timeline-canvas-scroll');
    let sL = 0;
    let sT = 0;
    let vw0 = 0;
    let vh0 = 0;
    let oldMaxX = 0;
    let oldMaxY = 0;
    if (scroll && !skipScrollPreserve) {
        sL = scroll.scrollLeft;
        sT = scroll.scrollTop;
        vw0 = scroll.clientWidth;
        vh0 = scroll.clientHeight;
        const oldSw = scroll.scrollWidth;
        const oldSh = scroll.scrollHeight;
        oldMaxX = Math.max(0, oldSw - vw0);
        oldMaxY = Math.max(0, oldSh - vh0);
    }

    window.__timelineZoom = z;
    try {
        sessionStorage.setItem(timelineZoomStorageKey(), String(z));
    } catch {
        /* ignore */
    }

    const root = document.getElementById('timeline-jpg-export-root');
    const shell = document.getElementById('timeline-zoom-shell');
    const axis = readAxisConfig();
    if (!root || !shell || !axis || axis.canvasWidth == null) {
        return;
    }

    const cw = axis.canvasWidth;
    if (z === 1) {
        root.style.transform = '';
        root.classList.remove('timeline-zoom-root--scaled');
    } else {
        root.style.transform = `scale(${z})`;
        root.classList.add('timeline-zoom-root--scaled');
    }

    const layoutH = root.offsetHeight;
    shell.style.width = `${cw * z}px`;
    shell.style.height = `${layoutH * z}px`;

    const label = document.getElementById('timeline-zoom-label');
    if (label) {
        label.textContent = `${Math.round(z * 100)}%`;
    }

    if (!scroll || skipScrollPreserve) {
        return;
    }

    void shell.offsetHeight;
    void scroll.offsetHeight;
    const vw = scroll.clientWidth;
    const vh = scroll.clientHeight;
    const sw = scroll.scrollWidth;
    const sh = scroll.scrollHeight;
    const maxX = Math.max(0, sw - vw);
    const maxY = Math.max(0, sh - vh);

    const zoomChanged = Math.abs(z - zPrev) > 1e-6;

    if (zoomChanged) {
        let fx = vw0 / 2;
        let fy = vh0 / 2;
        if (
            typeof focalOffsetX === 'number' &&
            typeof focalOffsetY === 'number' &&
            !Number.isNaN(focalOffsetX) &&
            !Number.isNaN(focalOffsetY)
        ) {
            fx = Math.max(0, Math.min(vw0, focalOffsetX));
            fy = Math.max(0, Math.min(vh0, focalOffsetY));
        }
        scroll.scrollLeft = Math.max(0, Math.min(maxX, (sL + fx) * (z / zPrev) - fx));
        scroll.scrollTop = Math.max(0, Math.min(maxY, (sT + fy) * (z / zPrev) - fy));
    } else {
        const mx = oldMaxX > 0 ? sL / oldMaxX : 0;
        const my = oldMaxY > 0 ? sT / oldMaxY : 0;
        scroll.scrollLeft = mx * maxX;
        scroll.scrollTop = my * maxY;
    }
}

/**
 * Кнопки масштаба, Ctrl/⌘ + колёсико, восстановление из sessionStorage.
 */
function initTimelineZoom() {
    const scroll = document.querySelector('.timeline-canvas-scroll');
    const axis = readAxisConfig();
    if (!axis || axis.canvasWidth == null) {
        return;
    }

    let initial = 1;
    try {
        const raw = sessionStorage.getItem(timelineZoomStorageKey());
        if (raw !== null && raw !== '') {
            const parsed = Number.parseFloat(raw);
            if (!Number.isNaN(parsed)) {
                initial = clampTimelineZoom(parsed);
            }
        }
    } catch {
        /* ignore */
    }

    applyTimelineZoom(initial, { skipScrollPreserve: true });
    requestAnimationFrame(() => {
        applyTimelineZoom(initial, { skipScrollPreserve: true });
    });

    const step = () => getTimelineZoom() * 0.15;
    document.getElementById('timeline-zoom-in')?.addEventListener('click', () => {
        applyTimelineZoom(getTimelineZoom() + step() + 0.01);
    });
    document.getElementById('timeline-zoom-out')?.addEventListener('click', () => {
        applyTimelineZoom(getTimelineZoom() - step() - 0.01);
    });
    document.getElementById('timeline-zoom-reset')?.addEventListener('click', () => {
        applyTimelineZoom(1);
    });

    if (scroll) {
        scroll.addEventListener(
            'wheel',
            (e) => {
                if (!e.ctrlKey && !e.metaKey) {
                    return;
                }
                e.preventDefault();
                const delta = e.deltaY > 0 ? -0.12 : 0.12;
                const rect = scroll.getBoundingClientRect();
                const fx = e.clientX - rect.left;
                const fy = e.clientY - rect.top;
                applyTimelineZoom(getTimelineZoom() + delta, { focalOffsetX: fx, focalOffsetY: fy });
            },
            { passive: false },
        );
    }

    let resizeTimer = 0;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            applyTimelineZoom(getTimelineZoom());
        }, 120);
    });
}

/**
 * @param {number} x
 * @param {{ tMin: number, tMax: number, canvasWidth: number }} axis
 */
function xToYearFromAxis(x, axis) {
    const range = axis.tMax - axis.tMin;
    return Math.round(axis.tMin + (x / axis.canvasWidth) * range);
}

/** @type {SVGElement | HTMLElement | null} */
let timelinePointTooltipTarget = null;

function timelineEscapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function timelineHidePointTooltip() {
    const tooltip = document.getElementById('timeline-point-tooltip');
    if (tooltip) {
        tooltip.classList.add('opacity-0', 'pointer-events-none', 'invisible');
        tooltip.innerHTML = '';
    }
    timelinePointTooltipTarget = null;
}

/**
 * @param {Record<string, unknown>} payload
 * @param {DOMRect} anchorRect
 */
function timelineShowPointTooltip(payload, anchorRect) {
    const tooltip = document.getElementById('timeline-point-tooltip');
    if (!tooltip || !payload) {
        return;
    }
    const lines = Array.isArray(payload.titles) ? payload.titles : [];
    const dates = Array.isArray(payload.exactDates) ? payload.exactDates : [];
    const parts = [];
    parts.push(
        `<div class="timeline-tooltip__line text-[10px] uppercase tracking-wide text-base-content/75 mb-1">${timelineEscapeHtml(String(payload.lineLabel || ''))}</div>`,
    );
    parts.push(
        `<div class="timeline-tooltip__year font-semibold text-base-content mb-1">${timelineEscapeHtml(String(payload.year))} г.</div>`,
    );
    lines.forEach((t, i) => {
        const d = dates[i];
        parts.push(
            `<div class="text-sm text-base-content/95">• ${timelineEscapeHtml(String(t))}${d ? ` <span class="text-base-content/70 text-xs">(${timelineEscapeHtml(String(d))})</span>` : ''}</div>`,
        );
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

function bindTimelinePointTooltips() {
    document.querySelectorAll('.timeline-point-hit[data-tooltip]').forEach((hit) => {
        hit.addEventListener('mouseenter', () => {
            const raw = hit.getAttribute('data-tooltip');
            if (!raw) {
                return;
            }
            try {
                const payload = JSON.parse(raw);
                timelinePointTooltipTarget = hit;
                const rect = hit.getBoundingClientRect();
                timelineShowPointTooltip(payload, rect);
            } catch {
                /* ignore */
            }
        });
        hit.addEventListener('mouseleave', () => {
            if (timelinePointTooltipTarget === hit) {
                timelineHidePointTooltip();
            }
        });
    });
}

/**
 * Первая отрисовка: прокрутить холст так, чтобы были видны годы с событиями (а не только 0 у левого края).
 */
function initTimelineInitialScroll() {
    const scroll = document.querySelector('.timeline-canvas-scroll');
    const axis = readAxisConfig();
    if (!scroll || !axis || axis.canvasWidth == null) {
        return;
    }
    if (axis.eventYearMin == null || axis.eventYearMax == null) {
        return;
    }

    const { tMin, tMax, canvasWidth: cw } = axis;
    const minY = axis.eventYearMin;
    const maxY = axis.eventYearMax;

    const apply = () => {
        const vw = scroll.clientWidth;
        if (vw < 1) {
            return;
        }
        const z = getTimelineZoom();
        const yearToX = (y) => {
            if (tMax <= tMin) {
                return 0;
            }

            return ((y - tMin) / (tMax - tMin)) * cw * z;
        };
        const xMin = yearToX(minY);
        const xMax = yearToX(maxY);
        const spanPx = Math.abs(xMax - xMin);
        const margin = 48;
        let left;
        if (spanPx <= vw - margin) {
            left = (xMin + xMax) / 2 - vw / 2;
        } else {
            left = xMax - vw + margin;
        }
        const maxScroll = Math.max(0, scroll.scrollWidth - vw);
        scroll.scrollLeft = Math.max(0, Math.min(left, maxScroll));
    };

    requestAnimationFrame(() => {
        requestAnimationFrame(apply);
    });
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

/**
 * Данные для цепочки: источник → персонаж/фракция → событие.
 *
 * @returns {Record<string, unknown>}
 */
function readTimelineEventSourceOptions() {
    const el = document.getElementById('timeline-event-source-options');
    if (!el?.textContent) {
        return emptyTimelineEventSourceOptions();
    }
    try {
        const raw = JSON.parse(el.textContent);
        return {
            biographies: Array.isArray(raw.biographies) ? raw.biographies : [],
            factions: Array.isArray(raw.factions) ? raw.factions : [],
            biography_events_by_biography:
                raw.biography_events_by_biography && typeof raw.biography_events_by_biography === 'object'
                    ? raw.biography_events_by_biography
                    : {},
            faction_events_by_faction:
                raw.faction_events_by_faction && typeof raw.faction_events_by_faction === 'object'
                    ? raw.faction_events_by_faction
                    : {},
            biography_event_lookup:
                raw.biography_event_lookup && typeof raw.biography_event_lookup === 'object'
                    ? raw.biography_event_lookup
                    : {},
            faction_event_lookup:
                raw.faction_event_lookup && typeof raw.faction_event_lookup === 'object' ? raw.faction_event_lookup : {},
        };
    } catch {
        return emptyTimelineEventSourceOptions();
    }
}

function emptyTimelineEventSourceOptions() {
    return {
        biographies: [],
        factions: [],
        biography_events_by_biography: {},
        faction_events_by_faction: {},
        biography_event_lookup: {},
        faction_event_lookup: {},
    };
}

function hideTimelineContextMenu() {
    const contextMenu = document.getElementById('timeline-context-menu');
    if (!contextMenu) {
        return;
    }
    contextMenu.classList.add('hidden');
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

    /**
     * Скрытые поля источника и второй селект — без сброса «Из модуля» (иначе change сразу обнуляет выбор пользователя).
     */
    function resetTimelineEventSourceEntityAndEvent() {
        const ent = document.getElementById('timeline-event-source-entity');
        const evSel = document.getElementById('timeline-event-source-event');
        const hb = document.getElementById('timeline-event-biography-event-id');
        const hf = document.getElementById('timeline-event-faction-event-id');
        if (ent) {
            ent.innerHTML = '<option value="">— Сначала выберите источник —</option>';
            ent.disabled = true;
        }
        if (evSel) {
            evSel.innerHTML = '<option value="">— Сначала выберите персонажа или фракцию —</option>';
            evSel.disabled = true;
        }
        if (hb) {
            hb.value = '';
            hb.disabled = true;
        }
        if (hf) {
            hf.value = '';
            hf.disabled = true;
        }
    }

    function resetTimelineEventObjectAndHiddenOnly() {
        resetTimelineEventSourceEntityAndEvent();
    }

    function clearTimelineEventSourceFields() {
        const mod = document.getElementById('timeline-event-source-module');
        if (mod) {
            mod.value = '';
        }
        resetTimelineEventObjectAndHiddenOnly();
    }

    /**
     * @param {boolean} visible
     */
    function setEventSourcePanelVisible(visible) {
        const wrap = document.getElementById('timeline-event-source-wrap');
        if (!wrap) {
            return;
        }
        wrap.classList.toggle('hidden', !visible);
        if (!visible) {
            clearTimelineEventSourceFields();
        }
    }

    /**
     * @param {string} moduleKey biographies|factions
     * @param {string} eventId
     * @returns {Record<string, unknown>|null}
     */
    function findTimelineEventSourceRow(moduleKey, eventId) {
        const opts = readTimelineEventSourceOptions();
        if (moduleKey === 'biographies') {
            const row = opts.biography_event_lookup?.[String(eventId)];
            return row && typeof row === 'object' ? row : null;
        }
        if (moduleKey === 'factions') {
            const row = opts.faction_event_lookup?.[String(eventId)];
            return row && typeof row === 'object' ? row : null;
        }

        return null;
    }

    /**
     * @param {string} moduleKey biographies|factions
     */
    function fillTimelineEventEntitySelect(moduleKey) {
        const ent = document.getElementById('timeline-event-source-entity');
        const opts = readTimelineEventSourceOptions();
        if (!ent) {
            return;
        }
        const list = moduleKey === 'biographies' ? opts.biographies || [] : moduleKey === 'factions' ? opts.factions || [] : [];
        ent.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = list.length
            ? moduleKey === 'biographies'
                ? '— Выберите персонажа —'
                : '— Выберите фракцию —'
            : 'Нет записей в этом модуле';
        ent.appendChild(placeholder);
        list.forEach((row) => {
            const o = document.createElement('option');
            o.value = String(row.id);
            o.textContent = row.name || String(row.id);
            ent.appendChild(o);
        });
        ent.disabled = list.length === 0;
    }

    /**
     * @param {string} moduleKey biographies|factions
     * @param {string} entityId
     */
    function fillTimelineEventEventSelect(moduleKey, entityId) {
        const evSel = document.getElementById('timeline-event-source-event');
        const opts = readTimelineEventSourceOptions();
        if (!evSel) {
            return;
        }
        let list = [];
        if (moduleKey === 'biographies' && entityId) {
            list = opts.biography_events_by_biography?.[String(entityId)] || [];
        } else if (moduleKey === 'factions' && entityId) {
            list = opts.faction_events_by_faction?.[String(entityId)] || [];
        }
        evSel.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = list.length ? '— Выберите событие —' : 'Нет событий для выбора';
        evSel.appendChild(placeholder);
        list.forEach((row) => {
            const o = document.createElement('option');
            o.value = String(row.id);
            o.textContent = row.label || String(row.id);
            evSel.appendChild(o);
        });
        evSel.disabled = list.length === 0;
    }

    /**
     * @param {Record<string, unknown>} row
     */
    function applyTimelineEventSourcePrefill(row) {
        const tit = document.getElementById('timeline-event-title');
        const ey = document.getElementById('timeline-event-epoch-year');
        const mo = document.getElementById('timeline-event-month');
        const day = document.getElementById('timeline-event-day');
        const br = document.getElementById('timeline-event-breaks-line');
        if (tit && typeof row.title === 'string') {
            tit.value = row.title;
        }
        if (ey) {
            ey.value = typeof row.epoch_year === 'number' ? String(row.epoch_year) : '0';
        }
        if (mo) {
            mo.value = typeof row.month === 'number' ? String(row.month) : '1';
        }
        if (day) {
            day.value = typeof row.day === 'number' ? String(row.day) : '1';
        }
        if (br && typeof row.breaks_line === 'boolean') {
            br.checked = row.breaks_line;
        }
        syncBreaksLineFromLineSelect();
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
        setEventSourcePanelVisible(false);
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
     * @param {{ showSourcePanel?: boolean }} opts
     */
    function openEventDialog(prefill = {}, opts = {}) {
        if (!eventDialog || !eventForm) {
            return;
        }
        eventForm.reset();
        resetEventFormToCreate();
        if (opts.showSourcePanel) {
            setEventSourcePanelVisible(true);
            clearTimelineEventSourceFields();
        }
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

    document.getElementById('timeline-event-source-module')?.addEventListener('change', () => {
        const m = document.getElementById('timeline-event-source-module')?.value || '';
        resetTimelineEventSourceEntityAndEvent();
        if (!m) {
            return;
        }
        if (m === 'biographies' || m === 'factions') {
            fillTimelineEventEntitySelect(m);
        }
    });

    document.getElementById('timeline-event-source-entity')?.addEventListener('change', () => {
        const mod = document.getElementById('timeline-event-source-module')?.value || '';
        const entityId = document.getElementById('timeline-event-source-entity')?.value || '';
        const hb = document.getElementById('timeline-event-biography-event-id');
        const hf = document.getElementById('timeline-event-faction-event-id');
        if (hb) {
            hb.value = '';
            hb.disabled = true;
        }
        if (hf) {
            hf.value = '';
            hf.disabled = true;
        }
        const evSel = document.getElementById('timeline-event-source-event');
        if (evSel) {
            evSel.innerHTML = '<option value="">— Выберите событие —</option>';
            evSel.disabled = true;
        }
        if (!entityId || (mod !== 'biographies' && mod !== 'factions')) {
            return;
        }
        fillTimelineEventEventSelect(mod, entityId);
    });

    document.getElementById('timeline-event-source-event')?.addEventListener('change', () => {
        const mod = document.getElementById('timeline-event-source-module')?.value || '';
        const eventId = document.getElementById('timeline-event-source-event')?.value || '';
        const hb = document.getElementById('timeline-event-biography-event-id');
        const hf = document.getElementById('timeline-event-faction-event-id');
        if (hb) {
            hb.value = '';
            hb.disabled = true;
        }
        if (hf) {
            hf.value = '';
            hf.disabled = true;
        }
        if (!eventId || (mod !== 'biographies' && mod !== 'factions')) {
            return;
        }
        const row = findTimelineEventSourceRow(mod, eventId);
        if (mod === 'biographies' && hb) {
            hb.disabled = false;
            hb.value = eventId;
        } else if (mod === 'factions' && hf) {
            hf.disabled = false;
            hf.value = eventId;
        }
        if (row) {
            applyTimelineEventSourcePrefill(row);
        }
    });

    document.getElementById('timeline-open-line-dialog')?.addEventListener('click', () => {
        openLineDialog({ startYear: 0, clearEnd: true });
    });

    document.getElementById('timeline-open-event-dialog')?.addEventListener('click', () => {
        openEventDialog({}, {});
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
            openEventDialog({ lineId: lid, epochYear: y }, {});
        }
        const createEvFrom = e.target.closest('.timeline-ctx-create-event-from');
        if (createEvFrom) {
            const y = parseInt(createEvFrom.getAttribute('data-year') || '0', 10);
            const lid = createEvFrom.getAttribute('data-line-id') || '';
            hideTimelineContextMenu();
            openEventDialog({ lineId: lid, epochYear: y }, { showSourcePanel: true });
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
    const axisProbe = readAxisConfig();
    const linesConfig = readTimelineLinesConfig();

    if (!scroll || !inner || !crosshair || !crosshairLine || !axisProbe || axisProbe.tMax <= axisProbe.tMin) {
        return;
    }

    /** Перетаскивание левой кнопкой: сдвиг области просмотра (scrollLeft / scrollTop). */
    let isPanning = false;
    let panStartX = 0;
    let panStartY = 0;
    let panStartScrollLeft = 0;
    let panStartScrollTop = 0;

    function onPanMove(e) {
        if (!isPanning) {
            return;
        }
        const dx = e.clientX - panStartX;
        const dy = e.clientY - panStartY;
        scroll.scrollLeft = panStartScrollLeft - dx;
        scroll.scrollTop = panStartScrollTop - dy;
    }

    function onPanEnd() {
        if (!isPanning) {
            return;
        }
        isPanning = false;
        scroll.classList.remove('is-panning');
        document.removeEventListener('mousemove', onPanMove);
        document.removeEventListener('mouseup', onPanEnd);
    }

    /**
     * Панорама холста: не начинать при клике по зоне перестановки (полоса линии или подпись)
     * и по точке события — иначе мешаем вертикальному жесту и подсказкам.
     */
    scroll.addEventListener(
        'mousedown',
        (e) => {
            if (e.button !== 0) {
                return;
            }
            if (e.target.closest?.('.timeline-point-hit')) {
                return;
            }
            const reorderRow = e.target.closest?.('.timeline-track-row--reorderable');
            if (
                reorderRow &&
                (e.target.closest?.('.timeline-line-hit') ||
                    e.target.closest?.('.timeline-line-label-reorder-hit'))
            ) {
                return;
            }
            isPanning = true;
            panStartX = e.clientX;
            panStartY = e.clientY;
            panStartScrollLeft = scroll.scrollLeft;
            panStartScrollTop = scroll.scrollTop;
            scroll.classList.add('is-panning');
            document.addEventListener('mousemove', onPanMove);
            document.addEventListener('mouseup', onPanEnd);
            e.preventDefault();
        },
        true,
    );

    function showCrosshair(xPx, clientX, clientY) {
        const axisNow = getTimelineAxis();
        if (!axisNow) {
            return;
        }
        crosshair.classList.remove('hidden');
        crosshairLine.style.left = `${xPx}px`;
        if (yearAtCursor) {
            const y = xToYearFromAxis(xPx, axisNow);
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
        const axisNow = getTimelineAxis();
        if (!axisNow) {
            hideCrosshair();
            return;
        }
        const z = getTimelineZoom();
        const rect = scroll.getBoundingClientRect();
        const xScaled = scroll.scrollLeft + (e.clientX - rect.left);
        const maxX = axisNow.canvasWidth * z;
        if (xScaled < 0 || xScaled > maxX) {
            hideCrosshair();
            return;
        }
        const x = xScaled / z;
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
     * @param {{ year: number, lineId?: string, eventIds?: number[] }} payload
     */
    function showContextMenu(clientX, clientY, mode, payload) {
        if (!contextMenu) {
            return;
        }
        timelineHidePointTooltip();
        const year = payload.year;
        const lineId = payload.lineId || '';
        const eventIds = Array.isArray(payload.eventIds) ? payload.eventIds : [];

        if (mode === 'canvas') {
            contextMenu.innerHTML =
                '<button type="button" role="menuitem" class="timeline-ctx-item timeline-ctx-create-line">Создать линию</button>';
            const btn = contextMenu.querySelector('.timeline-ctx-create-line');
            if (btn) {
                btn.setAttribute('data-year', String(year));
            }
        } else if (mode === 'lineTrack') {
            const lm = lineMeta(lineId);
            const parts = [];
            parts.push(
                `<button type="button" role="menuitem" class="timeline-ctx-item timeline-ctx-create-event">Создать событие</button>`,
            );
            parts.push(
                `<button type="button" role="menuitem" class="timeline-ctx-item timeline-ctx-create-event-from">Создать событие из…</button>`,
            );
            parts.push(
                `<button type="button" role="menuitem" class="timeline-ctx-item timeline-ctx-edit-line">Редактировать</button>`,
            );
            if (lm.isMain) {
                parts.push(
                    `<button type="button" role="menuitem" disabled class="timeline-ctx-item timeline-ctx-delete-line-disabled" title="Основную линию мира нельзя удалить">Удалить линию</button>`,
                );
            } else {
                parts.push(
                    `<button type="button" role="menuitem" class="timeline-ctx-item timeline-ctx-item--danger timeline-ctx-delete-line">Удалить линию</button>`,
                );
            }
            contextMenu.innerHTML = parts.join('');
            const evBtn = contextMenu.querySelector('.timeline-ctx-create-event');
            if (evBtn) {
                evBtn.setAttribute('data-year', String(year));
                evBtn.setAttribute('data-line-id', lineId);
            }
            const evFromBtn = contextMenu.querySelector('.timeline-ctx-create-event-from');
            if (evFromBtn) {
                evFromBtn.setAttribute('data-year', String(year));
                evFromBtn.setAttribute('data-line-id', lineId);
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
                parts.push(
                    `<button type="button" role="menuitem" class="timeline-ctx-item timeline-ctx-edit-event" data-event-id="${idStr}">Редактировать</button>`,
                );
                parts.push(
                    `<button type="button" role="menuitem" class="timeline-ctx-item timeline-ctx-item--danger timeline-ctx-delete-event" data-event-id="${idStr}">Удалить</button>`,
                );
            });
            contextMenu.innerHTML = parts.join('');
        }

        contextMenu.classList.remove('hidden');
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
        const axisNow = getTimelineAxis();
        if (!axisNow) {
            return;
        }
        const z = getTimelineZoom();
        const rect = scroll.getBoundingClientRect();
        const xScaled = scroll.scrollLeft + (e.clientX - rect.left);
        const maxX = axisNow.canvasWidth * z;
        if (xScaled < 0 || xScaled > maxX) {
            return;
        }
        e.preventDefault();
        const year = xToYearFromAxis(xScaled / z, axisNow);
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
        if (contextMenu && !contextMenu.classList.contains('hidden') && !contextMenu.contains(/** @type {Node} */ (e.target))) {
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

    bindTimelinePointTooltips();

    document.addEventListener(
        'scroll',
        () => {
            if (timelinePointTooltipTarget) {
                timelineHidePointTooltip();
            }
        },
        true,
    );
}

/**
 * Снимает предпросмотр перестановки (классы и инлайн-стили подсветки).
 */
function clearTimelineReorderPreview() {
    document.querySelectorAll('.timeline-track-row--dragging, .timeline-track-row--swap-preview').forEach((el) => {
        el.classList.remove('timeline-track-row--dragging', 'timeline-track-row--swap-preview');
        el.style.removeProperty('z-index');
        el.style.removeProperty('background-color');
    });
}

/**
 * Меняет порядок дорожек в DOM так же, как на сервере (swap с соседом).
 *
 * @param {HTMLElement} row Текущая дорожка (с data-reorder-line-id)
 * @param {'up'|'down'} direction
 */
function applyTimelineLineOrderInDom(row, direction) {
    const parent = document.getElementById('timeline-jpg-export-root');
    if (!parent) {
        return;
    }
    if (direction === 'up') {
        const prev = row.previousElementSibling;
        if (prev && prev.classList.contains('timeline-track-row')) {
            parent.insertBefore(row, prev);
        }
    } else if (direction === 'down') {
        const next = row.nextElementSibling;
        if (next && next.classList.contains('timeline-track-row')) {
            parent.insertBefore(next, row);
        }
    }
}

/**
 * Обновляет data-can-move-* и классы reorderable после смены порядка в DOM без перезагрузки.
 */
function refreshTimelineReorderDataAttributes() {
    const tracks = [...document.querySelectorAll('#timeline-jpg-export-root > .timeline-track-row')];
    tracks.forEach((row, idx) => {
        if (!row.hasAttribute('data-reorder-line-id')) {
            return;
        }
        const prev = tracks[idx - 1];
        const next = tracks[idx + 1];
        const canUp = !!(prev && prev.hasAttribute('data-reorder-line-id'));
        const canDown = !!(next && next.hasAttribute('data-reorder-line-id'));
        row.setAttribute('data-can-move-up', canUp ? '1' : '0');
        row.setAttribute('data-can-move-down', canDown ? '1' : '0');
        const show = canUp || canDown;
        row.classList.toggle('timeline-track-row--reorderable', show);
    });
}

/**
 * Перестановка дополнительных линий: жест вверх/вниз по дорожке (не с основной линией).
 * Предпросмотр соседа; после успешного AJAX порядок обновляется в DOM без перезагрузки.
 */
function initTimelineLineReorder() {
    const meta = readTimelinePageMeta();
    const moveUrlTemplate = meta.urls?.lineMove || '';
    const csrf = getCsrfToken();
    if (!moveUrlTemplate) {
        return;
    }

    const commitThresholdPx = 12;
    const previewThresholdPx = 6;

    document.querySelectorAll('.timeline-track-row--reorderable').forEach((row) => {
        row.addEventListener('pointerdown', (e) => {
            if (e.pointerType === 'mouse' && e.button !== 0) {
                return;
            }
            if (e.target.closest?.('.timeline-point-hit')) {
                return;
            }
            if (
                !e.target.closest?.('.timeline-line-hit') &&
                !e.target.closest?.('.timeline-line-label-reorder-hit')
            ) {
                return;
            }
            e.stopPropagation();
            e.preventDefault();

            const startY = e.clientY;
            const pointerId = e.pointerId;
            const lineId = row.getAttribute('data-reorder-line-id') || '';
            const canUp = row.getAttribute('data-can-move-up') === '1';
            const canDown = row.getAttribute('data-can-move-down') === '1';

            let captureOk = false;
            try {
                row.setPointerCapture(pointerId);
                captureOk = true;
            } catch {
                captureOk = false;
            }

            const applySwapHighlight = (/** @type {HTMLElement} */ el) => {
                el.classList.add('timeline-track-row--swap-preview');
                el.style.zIndex = '50';
                el.style.backgroundColor = 'rgba(255, 255, 255, 0.14)';
            };

            const onMove = (ev) => {
                ev.preventDefault();
                clearTimelineReorderPreview();
                const dy = ev.clientY - startY;
                const rows = [...document.querySelectorAll('#timeline-jpg-export-root > .timeline-track-row')];
                const idx = rows.indexOf(row);
                if (idx === -1) {
                    return;
                }
                if (Math.abs(dy) > 2) {
                    row.classList.add('timeline-track-row--dragging');
                    row.style.zIndex = '50';
                }
                if (dy < -previewThresholdPx && canUp && idx > 0) {
                    applySwapHighlight(rows[idx - 1]);
                } else if (dy > previewThresholdPx && canDown && idx < rows.length - 1) {
                    applySwapHighlight(rows[idx + 1]);
                }
            };

            const detachMoveFallback = () => {
                document.removeEventListener('pointermove', onMove, true);
                document.removeEventListener('mousemove', onMove, true);
            };

            if (captureOk) {
                row.addEventListener('pointermove', onMove);
            } else {
                document.addEventListener('pointermove', onMove, true);
                document.addEventListener('mousemove', onMove, true);
            }

            let finished = false;
            const finish = (ev) => {
                if (finished) {
                    return;
                }
                finished = true;
                if (captureOk) {
                    row.removeEventListener('pointermove', onMove);
                    try {
                        row.releasePointerCapture(pointerId);
                    } catch {
                        /* ignore */
                    }
                } else {
                    detachMoveFallback();
                }
                clearTimelineReorderPreview();

                const dyUp = startY - ev.clientY;
                const dyDown = ev.clientY - startY;
                let direction = null;
                if (dyUp > commitThresholdPx && canUp) {
                    direction = 'up';
                } else if (dyDown > commitThresholdPx && canDown) {
                    direction = 'down';
                }
                if (!direction || !lineId) {
                    return;
                }
                const url = moveUrlTemplate.replace('__ID__', lineId);
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ direction }),
                })
                    .then((r) => {
                        if (!r.ok) {
                            return r.json().then((body) => {
                                throw new Error(body.message || 'Ошибка');
                            });
                        }

                        return r.json();
                    })
                    .then(() => {
                        applyTimelineLineOrderInDom(row, direction);
                        refreshTimelineReorderDataAttributes();
                    })
                    .catch(() => {
                        window.alert('Не удалось изменить порядок линий.');
                    });
            };

            row.addEventListener('pointerup', finish, { once: true });
            row.addEventListener('pointercancel', finish, { once: true });
            if (!captureOk) {
                document.addEventListener(
                    'pointerup',
                    (ev) => {
                        if (ev.pointerId === pointerId) {
                            finish(ev);
                        }
                    },
                    { once: true, capture: true },
                );
                document.addEventListener(
                    'mouseup',
                    (ev) => {
                        if (ev.button === 0) {
                            finish(ev);
                        }
                    },
                    { once: true, capture: true },
                );
            }
        });
    });
}

function clearTimelineSettingsFormErrors(form) {
    ['reference_point', 'timeline_max_year'].forEach((field) => {
        const err = document.getElementById(`timeline-settings-err-${field}`);
        if (err) {
            err.textContent = '';
            err.classList.add('hidden');
        }
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.remove('input-error');
        }
    });
}

/**
 * @param {Record<string, string[] | string> | undefined} errors
 */
function showTimelineSettingsValidationErrors(errors) {
    if (!errors || typeof errors !== 'object') {
        return;
    }
    Object.entries(errors).forEach(([field, msgs]) => {
        const err = document.getElementById(`timeline-settings-err-${field}`);
        const first = Array.isArray(msgs) ? msgs[0] : msgs;
        if (err && first) {
            err.textContent = typeof first === 'string' ? first : String(first);
            err.classList.remove('hidden');
        }
        const input =
            field === 'reference_point'
                ? document.getElementById('timeline-settings-reference')
                : document.getElementById('timeline-settings-max-year');
        if (input) {
            input.classList.add('input-error');
        }
    });
}

/**
 * @param {{ message?: string, axis?: object, canvas_html?: string }} data
 */
function applyTimelineWorldSettingsResponse(data) {
    const axisEl = document.getElementById('timeline-axis-config');
    if (axisEl && data.axis) {
        axisEl.textContent = JSON.stringify(data.axis);
    }
    const root = document.getElementById('timeline-jpg-export-root');
    if (root && typeof data.canvas_html === 'string') {
        root.outerHTML = data.canvas_html;
    }
    bindTimelinePointTooltips();
    initTimelineLineReorder();
    applyTimelineZoom(getTimelineZoom(), { skipScrollPreserve: true });
    initTimelineInitialScroll();
    if (typeof window.showFlashToastMessage === 'function' && data.message) {
        window.showFlashToastMessage(data.message, 'success');
    }
}

function initTimelineWorldSettingsFormAjax() {
    const form = document.getElementById('timeline-settings-form');
    if (!form) {
        return;
    }
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }
        clearTimelineSettingsFormErrors(form);
        try {
            const fd = new FormData(form);
            const r = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: fd,
                credentials: 'same-origin',
            });
            let data = {};
            try {
                data = await r.json();
            } catch {
                data = {};
            }
            if (r.status === 422) {
                showTimelineSettingsValidationErrors(data.errors);
                return;
            }
            if (!r.ok) {
                window.alert(typeof data.message === 'string' ? data.message : 'Не удалось сохранить настройки.');
                return;
            }
            applyTimelineWorldSettingsResponse(data);
            document.getElementById('timelineSettingsModal')?.close();
        } catch {
            window.alert('Сеть недоступна или сервер не ответил.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initTimelineZoom();
    initTimelineCanvas();
    initTimelineModals();
    initTimelineLineReorder();
    initTimelineInitialScroll();
    initTimelineWorldSettingsFormAjax();
});
