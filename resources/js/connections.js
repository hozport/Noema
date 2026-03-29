/**
 * Доска «Связи»: рельс модулей, нити между блоками (SVG), контекстное меню, панорамирование.
 */

/** @type {{ edges: Array<{id: number, from_node_id: number, to_node_id: number}>, boardBase: string, worldData: string, linkMode: boolean, linkFirstId: number | null, redrawEdges: () => void }} */
const connectionsState = {
    edges: [],
    boardBase: '',
    worldData: '',
    linkMode: false,
    linkFirstId: null,
    redrawEdges: () => {},
};

function readConnectionsPageMeta() {
    const el = document.getElementById('connections-page-meta');
    if (!el?.textContent) {
        return { csrf: '', urls: { base: '', worldData: '' }, nodes: [], edges: [] };
    }
    try {
        return JSON.parse(el.textContent);
    } catch {
        return { csrf: '', urls: { base: '', worldData: '' }, nodes: [], edges: [] };
    }
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || readConnectionsPageMeta().csrf || '';
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

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;

    return d.innerHTML;
}

/** @param {string} base */
function apiUrl(base, path) {
    const b = base.replace(/\/$/, '');
    const p = path.startsWith('/') ? path : `/${path}`;

    return `${b}${p}`;
}

function hideConnectionsMenu() {
    const menu = document.getElementById('connections-context-menu');
    if (!menu) {
        return;
    }
    menu.classList.add('hidden');
    menu.innerHTML = '';
    menu.setAttribute('aria-hidden', 'true');
}

/**
 * @param {number} clientX
 * @param {number} clientY
 * @param {string} html
 */
function showConnectionsMenu(clientX, clientY, html) {
    const menu = document.getElementById('connections-context-menu');
    if (!menu) {
        return;
    }
    menu.innerHTML = html;
    menu.classList.remove('hidden');
    menu.setAttribute('aria-hidden', 'false');
    menu.style.left = '0';
    menu.style.top = '0';
    requestAnimationFrame(() => {
        if (!menu) {
            return;
        }
        const pad = 6;
        let left = clientX + pad;
        let top = clientY + pad;
        menu.style.left = `${left}px`;
        menu.style.top = `${top}px`;
        clampFixedToViewport(left, top, menu);
    });
}

async function fetchJson(url, options = {}) {
    const csrf = getCsrfToken();
    const headers = {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers,
    };
    if (options.body && !headers['Content-Type']) {
        headers['Content-Type'] = 'application/json';
    }
    const res = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });
    const text = await res.text();
    let data = null;
    try {
        data = text ? JSON.parse(text) : null;
    } catch {
        data = null;
    }
    if (!res.ok) {
        let msg = (data && data.message) || res.statusText || 'Ошибка запроса';
        if (data && typeof data.errors === 'object' && data.errors !== null) {
            const first = Object.values(data.errors)[0];
            if (Array.isArray(first) && typeof first[0] === 'string') {
                msg = first[0];
            }
        }
        throw new Error(typeof msg === 'string' ? msg : 'Ошибка запроса');
    }

    return data;
}

function getCanvasDropPosition() {
    const scroll = document.getElementById('connections-canvas-scroll');
    if (!scroll) {
        return { x: 160, y: 120 };
    }
    const x = scroll.scrollLeft + scroll.clientWidth / 2 - 88;
    const y = scroll.scrollTop + scroll.clientHeight / 2 - 40;

    return { x: Math.round(Math.max(0, x)), y: Math.round(Math.max(0, y)) };
}

/**
 * @param {number} nodeId
 * @returns {{ x: number, y: number } | null}
 */
function getNodeAnchor(nodeId) {
    const el = document.querySelector(`#connections-nodes-layer [data-node-id="${nodeId}"]`);
    if (!el) {
        return null;
    }

    return {
        x: el.offsetLeft + el.offsetWidth / 2,
        y: el.offsetTop + el.offsetHeight / 2,
    };
}

function redrawEdgesSvg() {
    const svg = document.getElementById('connections-edges-svg');
    const inner = document.getElementById('connections-canvas-inner');
    if (!svg || !inner) {
        return;
    }
    const w = inner.offsetWidth;
    const h = inner.offsetHeight;
    svg.setAttribute('width', String(w));
    svg.setAttribute('height', String(h));
    svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
    svg.innerHTML = '';

    const stroke = 'oklch(0.65 0.14 25)';
    connectionsState.edges.forEach((edge) => {
        const a = getNodeAnchor(edge.from_node_id);
        const b = getNodeAnchor(edge.to_node_id);
        if (!a || !b) {
            return;
        }
        const hit = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        hit.setAttribute('x1', String(a.x));
        hit.setAttribute('y1', String(a.y));
        hit.setAttribute('x2', String(b.x));
        hit.setAttribute('y2', String(b.y));
        hit.setAttribute('stroke', 'transparent');
        hit.setAttribute('stroke-width', '18');
        hit.setAttribute('stroke-linecap', 'round');
        hit.classList.add('connections-edge-hit');
        hit.dataset.edgeId = String(edge.id);

        const vis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        vis.setAttribute('x1', String(a.x));
        vis.setAttribute('y1', String(a.y));
        vis.setAttribute('x2', String(b.x));
        vis.setAttribute('y2', String(b.y));
        vis.setAttribute('stroke', stroke);
        vis.setAttribute('stroke-width', '2.5');
        vis.setAttribute('stroke-linecap', 'round');
        vis.setAttribute('pointer-events', 'none');

        svg.appendChild(vis);
        svg.appendChild(hit);
    });
}

/** @param {Record<string, unknown>} node */
function mountNodeEl(node) {
    const layer = document.getElementById('connections-nodes-layer');
    if (!layer || !node?.id) {
        return;
    }
    const el = document.createElement('div');
    el.className = 'connection-board-node absolute';
    el.style.left = `${node.x}px`;
    el.style.top = `${node.y}px`;
    el.dataset.nodeId = String(node.id);
    const label = typeof node.label === 'string' ? node.label : '…';
    const subtitle = typeof node.subtitle === 'string' && node.subtitle ? node.subtitle : '';

    el.innerHTML = `
        <button type="button" class="connection-board-node__remove" data-connection-remove="${node.id}" aria-label="Удалить с доски">×</button>
        <div class="text-sm font-semibold text-base-content leading-snug pr-1">${escapeHtml(label)}</div>
        ${subtitle ? `<div class="text-xs text-base-content/65 mt-1 leading-snug">${escapeHtml(subtitle)}</div>` : ''}
    `;
    layer.appendChild(el);
    connectionsState.redrawEdges();
}

/**
 * @param {string} boardBase
 * @param {Record<string, unknown>} body
 */
async function postNode(boardBase, body) {
    const url = apiUrl(boardBase, '/nodes');
    const data = await fetchJson(url, {
        method: 'POST',
        body: JSON.stringify(body),
    });

    return data?.node;
}

/**
 * @param {string} boardBase
 * @param {number} nodeId
 * @param {number} x
 * @param {number} y
 */
async function putNodePosition(boardBase, nodeId, x, y) {
    const url = apiUrl(boardBase, `/nodes/${nodeId}`);
    await fetchJson(url, {
        method: 'PUT',
        body: JSON.stringify({ x, y }),
    });
}

/**
 * @param {string} boardBase
 * @param {number} nodeId
 */
async function deleteNode(boardBase, nodeId) {
    const url = apiUrl(boardBase, `/nodes/${nodeId}`);
    await fetchJson(url, { method: 'DELETE' });
}

/**
 * @param {string} boardBase
 * @param {number} a
 * @param {number} b
 */
async function postEdge(boardBase, a, b) {
    const url = apiUrl(boardBase, '/edges');
    const data = await fetchJson(url, {
        method: 'POST',
        body: JSON.stringify({ from_node_id: a, to_node_id: b }),
    });

    return data?.edge;
}

/**
 * @param {string} boardBase
 * @param {number} edgeId
 */
async function deleteEdge(boardBase, edgeId) {
    const url = apiUrl(boardBase, `/edges/${edgeId}`);
    await fetchJson(url, { method: 'DELETE' });
}

function clearLinkPickHighlight() {
    document.querySelectorAll('.connection-board-node--link-pick').forEach((el) => {
        el.classList.remove('connection-board-node--link-pick');
    });
}

const LINK_RAIL_TITLE_OFF = 'Нить между блоками: два клика по карточкам';
const LINK_RAIL_TITLE_ON =
    'Режим нитей включён. Кликните по двум разным блокам. Перетаскивание отключено — нажмите эту кнопку снова или Escape.';

function setLinkRailActive(active) {
    document.querySelectorAll('[data-connections-rail="link"]').forEach((btn) => {
        btn.classList.toggle('connections-rail-btn--link-on', active);
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        btn.setAttribute('title', active ? LINK_RAIL_TITLE_ON : LINK_RAIL_TITLE_OFF);
        btn.setAttribute(
            'aria-label',
            active
                ? 'Режим нитей включён. Выключить: клик по кнопке или Escape.'
                : 'Включить режим нитей между блоками',
        );
    });
    const banner = document.getElementById('connections-link-mode-banner');
    if (banner) {
        banner.classList.toggle('hidden', !active);
    }
}

function initConnectionsCanvasPan() {
    const scroll = document.getElementById('connections-canvas-scroll');
    if (!scroll) {
        return;
    }

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

    scroll.addEventListener('mousedown', (e) => {
        if (e.button !== 0) {
            return;
        }
        const t = /** @type {HTMLElement} */ (e.target);
        if (t.closest?.('.connection-board-node')) {
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
    });

    scroll.addEventListener('scroll', () => {
        connectionsState.redrawEdges();
    });
}

function initEdgesSvgInteraction(boardBase) {
    const svg = document.getElementById('connections-edges-svg');
    if (!svg) {
        return;
    }
    svg.addEventListener('dblclick', async (e) => {
        const t = /** @type {HTMLElement} */ (e.target);
        const hit = t.closest?.('[data-edge-id]');
        if (!hit) {
            return;
        }
        const edgeId = Number.parseInt(hit.getAttribute('data-edge-id') || '0', 10);
        if (!edgeId) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        if (!window.confirm('Удалить эту связь?')) {
            return;
        }
        try {
            await deleteEdge(boardBase, edgeId);
            connectionsState.edges = connectionsState.edges.filter((x) => x.id !== edgeId);
            redrawEdgesSvg();
        } catch (err) {
            console.error(err);
            alert(err instanceof Error ? err.message : 'Не удалось удалить связь');
        }
    });

    const inner = document.getElementById('connections-canvas-inner');
    if (inner && typeof ResizeObserver !== 'undefined') {
        const ro = new ResizeObserver(() => {
            connectionsState.redrawEdges();
        });
        ro.observe(inner);
    }
}

function initNodeInteractions(boardBase) {
    const layer = document.getElementById('connections-nodes-layer');
    if (!layer) {
        return;
    }

    let dragId = null;
    let startX = 0;
    let startY = 0;
    let origLeft = 0;
    let origTop = 0;
    let elDrag = null;

    layer.addEventListener('click', async (e) => {
        const btn = /** @type {HTMLElement | null} */ (e.target)?.closest?.('[data-connection-remove]');
        if (!btn) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        const id = Number.parseInt(btn.getAttribute('data-connection-remove') || '0', 10);
        if (!id) {
            return;
        }
        try {
            await deleteNode(boardBase, id);
            btn.closest('.connection-board-node')?.remove();
            connectionsState.edges = connectionsState.edges.filter(
                (edge) => edge.from_node_id !== id && edge.to_node_id !== id,
            );
            redrawEdgesSvg();
        } catch (err) {
            console.error(err);
            alert(err instanceof Error ? err.message : 'Не удалось удалить');
        }
    });

    layer.addEventListener('mousedown', (e) => {
        if (e.button !== 0) {
            return;
        }
        const t = /** @type {HTMLElement} */ (e.target);
        if (t.closest?.('[data-connection-remove]')) {
            return;
        }
        const nodeEl = t.closest?.('.connection-board-node');
        if (!nodeEl || !layer.contains(nodeEl)) {
            return;
        }
        const id = Number.parseInt(nodeEl.dataset.nodeId || '0', 10);
        if (!id) {
            return;
        }

        if (connectionsState.linkMode) {
            e.preventDefault();
            e.stopPropagation();
            if (!connectionsState.linkFirstId) {
                clearLinkPickHighlight();
                connectionsState.linkFirstId = id;
                nodeEl.classList.add('connection-board-node--link-pick');
                return;
            }
            if (connectionsState.linkFirstId === id) {
                return;
            }
            const a = connectionsState.linkFirstId;
            const b = id;
            clearLinkPickHighlight();
            connectionsState.linkFirstId = null;
            (async () => {
                try {
                    const edge = await postEdge(boardBase, a, b);
                    if (edge && edge.id) {
                        connectionsState.edges.push({
                            id: edge.id,
                            from_node_id: edge.from_node_id,
                            to_node_id: edge.to_node_id,
                        });
                        redrawEdgesSvg();
                    }
                } catch (err) {
                    console.error(err);
                    alert(err instanceof Error ? err.message : 'Не удалось создать связь');
                }
            })();

            return;
        }

        e.preventDefault();
        e.stopPropagation();
        dragId = id;
        elDrag = nodeEl;
        startX = e.clientX;
        startY = e.clientY;
        origLeft = nodeEl.offsetLeft;
        origTop = nodeEl.offsetTop;
        nodeEl.classList.add('is-dragging');

        function move(ev) {
            if (!elDrag || dragId === null) {
                return;
            }
            const dx = ev.clientX - startX;
            const dy = ev.clientY - startY;
            elDrag.style.left = `${origLeft + dx}px`;
            elDrag.style.top = `${origTop + dy}px`;
            connectionsState.redrawEdges();
        }

        async function up() {
            document.removeEventListener('mousemove', move);
            document.removeEventListener('mouseup', up);
            if (!elDrag || dragId === null) {
                return;
            }
            elDrag.classList.remove('is-dragging');
            const nx = Math.round(parseFloat(elDrag.style.left) || 0);
            const ny = Math.round(parseFloat(elDrag.style.top) || 0);
            const idSave = dragId;
            dragId = null;
            elDrag = null;
            try {
                await putNodePosition(boardBase, idSave, nx, ny);
                connectionsState.redrawEdges();
            } catch (err) {
                console.error(err);
                alert(err instanceof Error ? err.message : 'Не удалось сохранить позицию');
            }
        }

        document.addEventListener('mousemove', move);
        document.addEventListener('mouseup', up);
    });
}

/**
 * @param {string} boardBase
 * @param {string} worldData
 */
function initConnectionsRail(boardBase, worldData) {
    const meta = readConnectionsPageMeta();

    connectionsState.boardBase = boardBase;
    connectionsState.worldData = worldData;
    connectionsState.edges = Array.isArray(meta.edges) ? [...meta.edges] : [];
    connectionsState.redrawEdges = redrawEdgesSvg;
    connectionsState.linkMode = false;
    connectionsState.linkFirstId = null;
    setLinkRailActive(false);

    document.querySelectorAll('[data-connections-rail]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const rail = /** @type {HTMLElement} */ (e.currentTarget).getAttribute('data-connections-rail');
            const rect = /** @type {HTMLElement} */ (e.currentTarget).getBoundingClientRect();
            const clientX = rect.right + 4;
            const clientY = rect.top;
            if (rail === 'link') {
                connectionsState.linkMode = !connectionsState.linkMode;
                connectionsState.linkFirstId = null;
                clearLinkPickHighlight();
                setLinkRailActive(connectionsState.linkMode);
                if (connectionsState.linkMode) {
                    hideConnectionsMenu();
                }

                return;
            }
            if (rail === 'timeline') {
                openTimelineMenu(worldData, clientX, clientY);
            } else if (rail === 'cards') {
                openCardsMenu(worldData, clientX, clientY);
            } else if (rail === 'maps') {
                showConnectionsMenu(
                    clientX,
                    clientY,
                    `<div class="connections-ctx-heading">Объекты карт</div>
                    <div class="px-3 pb-2 text-sm text-base-content/80">Раздел в разработке.</div>`,
                );
            } else if (rail === 'bestiary') {
                openBestiaryMenu(worldData, clientX, clientY);
            } else if (rail === 'biographies') {
                openBiographiesMenu(worldData, clientX, clientY);
            }
        });
    });

    document.addEventListener('mousedown', (e) => {
        if (e.button !== 0) {
            return;
        }
        const menu = document.getElementById('connections-context-menu');
        if (menu && !menu.classList.contains('hidden') && !menu.contains(/** @type {Node} */ (e.target))) {
            hideConnectionsMenu();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            hideConnectionsMenu();
            if (connectionsState.linkMode) {
                connectionsState.linkMode = false;
                connectionsState.linkFirstId = null;
                clearLinkPickHighlight();
                setLinkRailActive(false);
            }
        }
    });

    const nodes = Array.isArray(meta.nodes) ? meta.nodes : [];
    nodes.forEach((n) => mountNodeEl(n));

    initConnectionsCanvasPan();
    initNodeInteractions(boardBase);
    initEdgesSvgInteraction(boardBase);
    redrawEdgesSvg();
}

/**
 * @param {string} worldData
 * @param {number} clientX
 * @param {number} clientY
 */
async function openTimelineMenu(worldData, clientX, clientY) {
    try {
        const data = await fetchJson(apiUrl(worldData, '/timeline-lines'));
        const lines = Array.isArray(data?.lines) ? data.lines : [];
        if (lines.length === 0) {
            showConnectionsMenu(
                clientX,
                clientY,
                `<div class="connections-ctx-heading">События таймлайнов</div>
                <div class="px-3 pb-2 text-sm text-base-content/80">Нет линий. Создайте линии на таймлайне.</div>`,
            );

            return;
        }
        const parts = ['<div class="connections-ctx-heading">Выберите линию</div>'];
        lines.forEach((ln) => {
            const id = String(ln.id);
            const name = typeof ln.name === 'string' ? ln.name : 'Линия';
            parts.push(
                `<button type="button" role="menuitem" class="connections-ctx-item" data-conn-tl-line="${id}">${escapeHtml(name)}</button>`,
            );
        });
        showConnectionsMenu(clientX, clientY, parts.join(''));

        const menu = document.getElementById('connections-context-menu');
        menu?.querySelectorAll('[data-conn-tl-line]').forEach((b) => {
            b.addEventListener('click', async () => {
                const lineId = /** @type {HTMLElement} */ (b).getAttribute('data-conn-tl-line');
                if (!lineId) {
                    return;
                }
                await openTimelineEventsForLine(worldData, lineId, clientX, clientY);
            });
        });
    } catch (err) {
        console.error(err);
        alert(err instanceof Error ? err.message : 'Не удалось загрузить линии');
    }
}

/**
 * @param {string} worldData
 * @param {string} lineId
 * @param {number} clientX
 * @param {number} clientY
 */
async function openTimelineEventsForLine(worldData, lineId, clientX, clientY) {
    try {
        const data = await fetchJson(apiUrl(worldData, `/timeline-lines/${lineId}/events`));
        const events = Array.isArray(data?.events) ? data.events : [];
        const parts = [
            `<button type="button" class="connections-ctx-item connections-ctx-back" data-conn-tl-back>← Линии</button>`,
            '<div class="connections-ctx-heading">Событие</div>',
        ];
        if (events.length === 0) {
            parts.push('<div class="px-3 pb-2 text-sm text-base-content/80">На этой линии пока нет событий.</div>');
        } else {
            events.forEach((ev) => {
                const id = String(ev.id);
                const title = typeof ev.title === 'string' ? ev.title : 'Событие';
                parts.push(
                    `<button type="button" role="menuitem" class="connections-ctx-item" data-conn-tl-ev="${id}">${escapeHtml(title)}</button>`,
                );
            });
        }
        showConnectionsMenu(clientX, clientY, parts.join(''));

        document.getElementById('connections-context-menu')?.querySelector('[data-conn-tl-back]')?.addEventListener('click', () => {
            openTimelineMenu(worldData, clientX, clientY);
        });

        document.getElementById('connections-context-menu')?.querySelectorAll('[data-conn-tl-ev]').forEach((b) => {
            b.addEventListener('click', async () => {
                const eid = Number.parseInt(/** @type {HTMLElement} */ (b).getAttribute('data-conn-tl-ev') || '0', 10);
                if (!eid) {
                    return;
                }
                const pos = getCanvasDropPosition();
                try {
                    const node = await postNode(connectionsState.boardBase, {
                        kind: 'timeline_event',
                        entity_id: eid,
                        meta: null,
                        x: pos.x,
                        y: pos.y,
                    });
                    if (node) {
                        mountNodeEl(node);
                    }
                    hideConnectionsMenu();
                } catch (err) {
                    console.error(err);
                    alert(err instanceof Error ? err.message : 'Не удалось добавить событие');
                }
            });
        });
    } catch (err) {
        console.error(err);
        alert(err instanceof Error ? err.message : 'Не удалось загрузить события');
    }
}

/**
 * @param {string} worldData
 * @param {number} clientX
 * @param {number} clientY
 */
async function openCardsMenu(worldData, clientX, clientY) {
    try {
        const data = await fetchJson(apiUrl(worldData, '/stories'));
        const stories = Array.isArray(data?.stories) ? data.stories : [];
        if (stories.length === 0) {
            showConnectionsMenu(
                clientX,
                clientY,
                `<div class="connections-ctx-heading">Карточки</div>
                <div class="px-3 pb-2 text-sm text-base-content/80">Нет историй. Создайте историю в разделе «Карточки».</div>`,
            );

            return;
        }
        const options = stories
            .map((s) => {
                const id = String(s.id);
                const name = typeof s.name === 'string' ? s.name : 'История';

                return `<option value="${id}">${escapeHtml(name)}</option>`;
            })
            .join('');
        const html = `
            <div class="connections-ctx-heading">Карточки</div>
            <div class="px-3 pb-2">
                <label class="block text-xs text-base-content/60 mb-1" for="conn-story-select">История</label>
                <select id="conn-story-select" class="select select-bordered select-sm w-full max-w-full rounded-none bg-base-100">${options}</select>
            </div>
            <div id="conn-cards-list" class="px-1 pb-2"></div>
        `;
        showConnectionsMenu(clientX, clientY, html);

        const sel = /** @type {HTMLSelectElement | null} */ (document.getElementById('conn-story-select'));
        const listEl = document.getElementById('conn-cards-list');

        async function loadCards(storyId) {
            if (!listEl) {
                return;
            }
            listEl.innerHTML = '<div class="px-2 py-1 text-sm text-base-content/60">Загрузка…</div>';
            try {
                const cdata = await fetchJson(apiUrl(worldData, `/stories/${storyId}/cards`));
                const cards = Array.isArray(cdata?.cards) ? cdata.cards : [];
                if (cards.length === 0) {
                    listEl.innerHTML = '<div class="px-2 py-1 text-sm text-base-content/60">В этой истории нет карточек.</div>';

                    return;
                }
                const btns = cards
                    .map((c) => {
                        const id = String(c.id);
                        const label = typeof c.label === 'string' ? c.label : 'Карточка';

                        return `<button type="button" role="menuitem" class="connections-ctx-item" data-conn-card="${id}">${escapeHtml(label)}</button>`;
                    })
                    .join('');
                listEl.innerHTML = btns;
                listEl.querySelectorAll('[data-conn-card]').forEach((b) => {
                    b.addEventListener('click', async () => {
                        const cid = Number.parseInt(/** @type {HTMLElement} */ (b).getAttribute('data-conn-card') || '0', 10);
                        const sid = Number.parseInt(storyId, 10);
                        if (!cid || !sid) {
                            return;
                        }
                        const pos = getCanvasDropPosition();
                        try {
                            const node = await postNode(connectionsState.boardBase, {
                                kind: 'story_card',
                                entity_id: cid,
                                meta: { story_id: sid },
                                x: pos.x,
                                y: pos.y,
                            });
                            if (node) {
                                mountNodeEl(node);
                            }
                            hideConnectionsMenu();
                        } catch (err) {
                            console.error(err);
                            alert(err instanceof Error ? err.message : 'Не удалось добавить карточку');
                        }
                    });
                });
            } catch (err) {
                listEl.innerHTML = `<div class="px-2 py-1 text-sm text-error">${escapeHtml(err instanceof Error ? err.message : 'Ошибка')}</div>`;
            }
        }

        if (sel) {
            sel.addEventListener('change', () => loadCards(sel.value));
            loadCards(sel.value);
        }
    } catch (err) {
        console.error(err);
        alert(err instanceof Error ? err.message : 'Не удалось загрузить истории');
    }
}

/**
 * @param {string} worldData
 * @param {number} clientX
 * @param {number} clientY
 */
async function openBestiaryMenu(worldData, clientX, clientY) {
    try {
        const data = await fetchJson(apiUrl(worldData, '/creatures'));
        const creatures = Array.isArray(data?.creatures) ? data.creatures : [];
        const parts = ['<div class="connections-ctx-heading">Бестиарий</div>'];
        if (creatures.length === 0) {
            parts.push('<div class="px-3 pb-2 text-sm text-base-content/80">Пока нет существ.</div>');
        } else {
            creatures.forEach((cr) => {
                const id = String(cr.id);
                const name = typeof cr.name === 'string' ? cr.name : 'Существо';
                parts.push(
                    `<button type="button" role="menuitem" class="connections-ctx-item" data-conn-creature="${id}">${escapeHtml(name)}</button>`,
                );
            });
        }
        showConnectionsMenu(clientX, clientY, parts.join(''));

        document.getElementById('connections-context-menu')?.querySelectorAll('[data-conn-creature]').forEach((b) => {
            b.addEventListener('click', async () => {
                const cid = Number.parseInt(/** @type {HTMLElement} */ (b).getAttribute('data-conn-creature') || '0', 10);
                if (!cid) {
                    return;
                }
                const pos = getCanvasDropPosition();
                try {
                    const node = await postNode(connectionsState.boardBase, {
                        kind: 'creature',
                        entity_id: cid,
                        meta: null,
                        x: pos.x,
                        y: pos.y,
                    });
                    if (node) {
                        mountNodeEl(node);
                    }
                    hideConnectionsMenu();
                } catch (err) {
                    console.error(err);
                    alert(err instanceof Error ? err.message : 'Не удалось добавить существо');
                }
            });
        });
    } catch (err) {
        console.error(err);
        alert(err instanceof Error ? err.message : 'Не удалось загрузить бестиарий');
    }
}

/**
 * @param {string} worldData
 * @param {number} clientX
 * @param {number} clientY
 */
async function openBiographiesMenu(worldData, clientX, clientY) {
    try {
        const data = await fetchJson(apiUrl(worldData, '/biographies'));
        const rows = Array.isArray(data?.biographies) ? data.biographies : [];
        const parts = ['<div class="connections-ctx-heading">Биографии</div>'];
        if (rows.length === 0) {
            parts.push('<div class="px-3 pb-2 text-sm text-base-content/80">Пока нет личностей.</div>');
        } else {
            rows.forEach((row) => {
                const id = String(row.id);
                const name = typeof row.name === 'string' ? row.name : 'Личность';
                parts.push(
                    `<button type="button" role="menuitem" class="connections-ctx-item" data-conn-bio="${id}">${escapeHtml(name)}</button>`,
                );
            });
        }
        showConnectionsMenu(clientX, clientY, parts.join(''));

        document.getElementById('connections-context-menu')?.querySelectorAll('[data-conn-bio]').forEach((b) => {
            b.addEventListener('click', async () => {
                const bid = Number.parseInt(/** @type {HTMLElement} */ (b).getAttribute('data-conn-bio') || '0', 10);
                if (!bid) {
                    return;
                }
                const pos = getCanvasDropPosition();
                try {
                    const node = await postNode(connectionsState.boardBase, {
                        kind: 'biography',
                        entity_id: bid,
                        meta: null,
                        x: pos.x,
                        y: pos.y,
                    });
                    if (node) {
                        mountNodeEl(node);
                    }
                    hideConnectionsMenu();
                } catch (err) {
                    console.error(err);
                    alert(err instanceof Error ? err.message : 'Не удалось добавить личность');
                }
            });
        });
    } catch (err) {
        console.error(err);
        alert(err instanceof Error ? err.message : 'Не удалось загрузить биографии');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const meta = readConnectionsPageMeta();
    const boardBase = typeof meta.urls?.base === 'string' ? meta.urls.base : '';
    const worldData = typeof meta.urls?.worldData === 'string' ? meta.urls.worldData : '';
    if (!boardBase || !worldData) {
        return;
    }
    initConnectionsRail(boardBase, worldData);
});
