/**
 * Карты мира: Konva, холст задаётся на сервере (px), панорамирование, спрайты из public/sprites/…
 */

import Konva from 'konva';

const PLACE_SIZE = 48;
/** Макс. ширина строки подписи: в 2 раза шире объекта — дальше перенос по словам. */
const LABEL_WRAP_WIDTH = PLACE_SIZE * 2;
/** Без зазора между картинкой и текстом — иначе при движении курсора «проваливается» hit и моргает hover. */
const LABEL_GAP_AFTER_IMAGE = 0;
const LABEL_PADDING_BOTTOM = 6;
const MAP_LABEL_FONT_SIZE = 14;
/** Мягкое «сияние» под SVG при наведении (радиальный градиент). */
const MAP_OBJECT_HOVER_HALO = 'map-object-hover-halo';
/** Прозрачная общая hit-область (картинка + подпись), снизу слоем — закрывает пустоты между дочерними фигурами. */
const MAP_OBJECT_HIT_FILL = 'map-object-hit-fill';
/** Фиксированные линейки у края вьюпорта (экранные px). */
const RULER_W = 14;
const RULER_H = 14;
/** Шаг сетки на холсте = шаг засечек на линейках (мировые px). */
const GRID_STEP = 100;
/** Толщина линии по умолчанию, если в сохранённых данных нет strokeWidth. */
const DEFAULT_MAP_LINE_WIDTH = 2;

/** Дефолтный размер холста до initKonva (совпадает с типичным сохранённым картой). */
const DEFAULT_MAP_SIDE_PX = 3000;

/**
 * Ограничение ширины/высоты холста (как на сервере).
 *
 * @param {unknown} n
 * @returns {number}
 */
function clampMapSide(n) {
    const v = Math.round(Number(n));
    if (Number.isNaN(v)) {
        return DEFAULT_MAP_SIDE_PX;
    }

    return Math.max(500, Math.min(5000, v));
}

/**
 * Дебаг заливки карты в консоль. Выкл.: `localStorage.setItem('noema_map_fill_debug','0')`.
 * Вкл. явно: `localStorage.setItem('noema_map_fill_debug','1')`. По умолчанию — логи включены.
 *
 * @returns {boolean}
 */
function mapFillDebugEnabled() {
    try {
        const v = window.localStorage?.getItem('noema_map_fill_debug');
        if (v === '0') {
            return false;
        }
        if (v === '1') {
            return true;
        }
    } catch {
        // sessionStorage в приватном режиме и т.п.
    }

    return true;
}

/**
 * @param {...unknown} args
 * @returns {void}
 */
function mapFillDebugLog(...args) {
    if (!mapFillDebugEnabled()) {
        return;
    }
    console.info('[noema-map-fill]', new Date().toISOString(), ...args);
}

/**
 * Цвета обводки линий карты (ключи = data-map-*-stroke). Серый — для старых сохранённых карт.
 *
 * @type {Record<string, string>}
 */
const MAP_LINE_STROKE_BY_KEY = {
    black: 'rgba(26, 24, 20, 0.96)',
    gray: 'rgba(118, 116, 110, 0.96)',
    earth: '#d4c5b5',
    grass: '#b8c4a8',
    water: '#a8b8c4',
    deep_sea: '#8a9ca8',
    ice: '#e0e4e8',
    forest: '#9ca88e',
    desert: '#e0d4c0',
    mountain: '#c4b8a8',
    swamp: '#a8b0a0',
};

/**
 * Ограничивает толщину линии 1…20 мировых px.
 *
 * @param {number} n
 * @returns {number}
 */
function clampMapLineWidth(n) {
    const w = Math.round(Number(n));
    if (Number.isNaN(w)) {
        return DEFAULT_MAP_LINE_WIDTH;
    }

    return Math.max(1, Math.min(20, w));
}

/**
 * Числа в точках линии для JSON: NaN/Infinity дают null в JSON.stringify и ломают валидацию Laravel.
 *
 * @param {number[]} raw
 * @returns {number[]}
 */
function sanitizeLinePointsForJson(raw) {
    const out = [];
    for (let i = 0; i < raw.length; i++) {
        const n = Number(raw[i]);
        out.push(Number.isFinite(n) ? n : 0);
    }

    return out;
}

/**
 * Пунктир линий границ: шаг масштабируется с толщиной. Фиксированный [5,5] при крупном stroke
 * с lineCap round визуально сливается в сплошную линию.
 *
 * @param {number} strokeWidth
 * @returns {number[]}
 */
function borderLineDashForStrokeWidth(strokeWidth) {
    const w = clampMapLineWidth(strokeWidth);
    const scale = w / DEFAULT_MAP_LINE_WIDTH;

    return [5 * scale, 5 * scale];
}

/**
 * CSS-цвет обводки линии по ключу палитры.
 *
 * @param {string} key
 * @returns {string}
 */
function mapLineStrokeCss(key) {
    if (key && Object.prototype.hasOwnProperty.call(MAP_LINE_STROKE_BY_KEY, key)) {
        return MAP_LINE_STROKE_BY_KEY[key];
    }

    return MAP_LINE_STROKE_BY_KEY.black;
}
/** Видимая рамка листа карты и стена в маске заливки (мировые px). */
const MAP_CANVAS_EDGE_STROKE_WIDTH = 1;
const MAP_CANVAS_EDGE_STROKE = '#000000';
/**
 * Курсор «карандаш»: base64 + запасной crosshair (часть браузеров капризничает с url() на внешнем div, см. applyMapCursor).
 */
const MAP_PENCIL_CURSOR =
    'url("data:image/svg+xml;base64,' +
    btoa(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="#1a1814" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M17 3a2.85 2.85 0 0 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>',
    ) +
    '") 4 20, crosshair';

/**
 * Курсор «заливка» (ведёрко).
 */
const MAP_FILL_CURSOR =
    'url("data:image/svg+xml;base64,' +
    btoa(
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="#1a1814" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="m19 11-8-8-8.5 8.5a2.12 2.12 0 0 0 0 3l3 3c.84.84 2.2.84 3.04 0L19 11"/><path fill="none" stroke="#1a1814" stroke-width="1.75" stroke-linecap="round" d="M5 21h14"/></svg>',
    ) +
    '") 8 18, pointer';

/** Дефолтный радиус кисти ластика (мировые px). */
const DEFAULT_ERASE_RADIUS = 12;
/** Макс. сторона растрового курсора (ограничение браузеров ~128px). */
const ERASER_CURSOR_MAX_PX = 128;

/**
 * Радиус кисти ластика 1…100 мировых px.
 *
 * @param {number} n
 * @returns {number}
 */
function clampEraseRadius(n) {
    const r = Math.round(Number(n));
    if (Number.isNaN(r)) {
        return DEFAULT_ERASE_RADIUS;
    }

    return Math.max(1, Math.min(100, r));
}

/** Кэш data URL курсора ластика по радиусу (пересборка только при смене размера). */
let eraserCursorCacheRadius = -1;
/** @type {string} */
let eraserCursorCacheCss = '';

/**
 * CSS cursor: белый круг, обводка 1px, размер пропорционален радиусу стирания (до ERASER_CURSOR_MAX_PX).
 *
 * @param {number} radiusWorld
 * @returns {string}
 */
function buildMapEraserCursorCss(radiusWorld) {
    const r = clampEraseRadius(radiusWorld);
    const logicalSize = 2 * r + 4;
    const size = Math.min(logicalSize, ERASER_CURSOR_MAX_PX);
    const c = document.createElement('canvas');
    c.width = size;
    c.height = size;
    const ctx = c.getContext('2d');
    if (!ctx) {
        return 'crosshair';
    }
    const cx = size / 2;
    const cy = size / 2;
    const scale = size / logicalSize;
    const rDraw = Math.max(0.5, r * scale - 0.5);
    ctx.beginPath();
    ctx.arc(cx, cy, rDraw, 0, Math.PI * 2);
    ctx.fillStyle = '#ffffff';
    ctx.fill();
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 1;
    ctx.stroke();
    const hsx = Math.floor(cx);
    const hsy = Math.floor(cy);
    const dataUrl = c.toDataURL('image/png');

    return `url("${dataUrl}") ${hsx} ${hsy}, crosshair`;
}

/**
 * Возвращает курсор ластика для текущего mapState.eraseRadius (с кэшем).
 *
 * @returns {string}
 */
function getMapEraserCursorCss() {
    const r = clampEraseRadius(mapState.eraseRadius);
    if (r !== eraserCursorCacheRadius || !eraserCursorCacheCss) {
        eraserCursorCacheRadius = r;
        eraserCursorCacheCss = buildMapEraserCursorCss(r);
    }

    return eraserCursorCacheCss;
}
/** Если конец штриха ближе — дорисовываем до отрезка другой линии (замыкание контура для заливки). */
const GAP_CLOSE_SNAP_PX = 3;

/**
 * Пастельные заливки карты (ключи совпадают с data-fill-color и --noema-* в CSS).
 *
 * @type {Record<string, string>}
 */
const FILL_PALETTE = {
    earth: 'rgba(212, 197, 181, 0.78)',
    grass: 'rgba(184, 196, 168, 0.78)',
    water: 'rgba(168, 184, 196, 0.78)',
    deep_sea: 'rgba(138, 156, 168, 0.78)',
    ice: 'rgba(224, 228, 232, 0.78)',
    forest: 'rgba(156, 168, 142, 0.78)',
    desert: 'rgba(224, 212, 192, 0.78)',
    mountain: 'rgba(196, 184, 168, 0.78)',
    swamp: 'rgba(168, 176, 160, 0.78)',
};

/** Ключ палитры заливки «Вода» (пена у кромки только для него). */
const MAP_WATER_FILL_KEY = 'water';

/**
 * Глубина декоративной полосы от берега вглубь заливки (слои по сетке 4-соседей).
 * Значение 6: расстояния 0…5 от кромки — градиент от пены к основному цвету воды.
 */
const WATER_FOAM_BAND_DEPTH_PX = 6;

/**
 * Konva рендерит в `canvas` внутри `.konvajs-content`; курсор нужно задавать на content/canvas, иначе на холсте «пропадает».
 *
 * @param {string} css Значение CSS cursor
 * @returns {void}
 */
/**
 * Расстояние от точки до отрезка (мировые координаты).
 *
 * @param {number} px
 * @param {number} py
 * @param {number} x1
 * @param {number} y1
 * @param {number} x2
 * @param {number} y2
 * @returns {number}
 */
function pointToSegmentDistance(px, py, x1, y1, x2, y2) {
    const dx = x2 - x1;
    const dy = y2 - y1;
    const lenSq = dx * dx + dy * dy;
    if (lenSq === 0) {
        return Math.hypot(px - x1, py - y1);
    }
    let t = ((px - x1) * dx + (py - y1) * dy) / lenSq;
    t = Math.max(0, Math.min(1, t));
    const nx = x1 + t * dx;
    const ny = y1 + t * dy;

    return Math.hypot(px - nx, py - ny);
}

/**
 * Ближайшая точка на отрезке к P и расстояние до неё (мировые координаты).
 *
 * @param {number} px
 * @param {number} py
 * @param {number} x1
 * @param {number} y1
 * @param {number} x2
 * @param {number} y2
 * @returns {{ x: number, y: number, dist: number }}
 */
function pointToSegmentClosestPoint(px, py, x1, y1, x2, y2) {
    const dx = x2 - x1;
    const dy = y2 - y1;
    const lenSq = dx * dx + dy * dy;
    if (lenSq === 0) {
        const dist = Math.hypot(px - x1, py - y1);

        return { x: x1, y: y1, dist };
    }
    let t = ((px - x1) * dx + (py - y1) * dy) / lenSq;
    t = Math.max(0, Math.min(1, t));
    const nx = x1 + t * dx;
    const ny = y1 + t * dy;

    return { x: nx, y: ny, dist: Math.hypot(px - nx, py - ny) };
}

/**
 * Если конец штриха в пределах GAP_CLOSE_SNAP_PX от другого отрезка (не последнего сегмента этого же штриха),
 * добавляет вершину в точку примыкания на том отрезке.
 *
 * @param {import('konva/lib/shapes/Line').default} line
 * @param {import('konva/lib/Group').default} landscapeDrawGroup
 * @returns {void}
 */
function snapLandscapeStrokeEndToNearbyLines(line, landscapeDrawGroup) {
    const ox = line.x();
    const oy = line.y();
    const pts = line.points().slice();
    if (pts.length < 4) {
        return;
    }
    const n = pts.length;
    const ex = ox + pts[n - 2];
    const ey = oy + pts[n - 1];
    let bestDist = Infinity;
    let bestX = /** @type {number | null} */ (null);
    let bestY = /** @type {number | null} */ (null);
    const numSegsThis = n / 2 - 1;
    landscapeDrawGroup.getChildren().forEach((node) => {
        if (node.getClassName() !== 'Line') {
            return;
        }
        const nodePts = node.points();
        const nx = node.x();
        const ny = node.y();
        const nv = nodePts.length / 2;
        if (nv < 2) {
            return;
        }
        for (let s = 0; s < nv - 1; s++) {
            if (node === line && s === numSegsThis - 1) {
                continue;
            }
            const x1 = nx + nodePts[s * 2];
            const y1 = ny + nodePts[s * 2 + 1];
            const x2 = nx + nodePts[s * 2 + 2];
            const y2 = ny + nodePts[s * 2 + 3];
            const cp = pointToSegmentClosestPoint(ex, ey, x1, y1, x2, y2);
            if (cp.dist < GAP_CLOSE_SNAP_PX && cp.dist > 1e-6 && cp.dist < bestDist) {
                bestDist = cp.dist;
                bestX = cp.x;
                bestY = cp.y;
            }
        }
    });
    if (bestX === null || bestY === null) {
        return;
    }
    const lx = bestX - ox;
    const ly = bestY - oy;
    if (Math.hypot(lx - pts[n - 2], ly - pts[n - 1]) < 1e-6) {
        return;
    }
    line.points(pts.concat([lx, ly]));
}

/**
 * Стирает круг заливки (прозрачность через destination-out).
 *
 * @param {number} wx
 * @param {number} wy
 * @param {number} radius
 * @returns {void}
 */
function eraseFillDiskAt(wx, wy, radius) {
    const canvas = mapState.fillCanvas;
    const imgNode = mapState.fillImageNode;
    if (!canvas || !imgNode) {
        return;
    }
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return;
    }
    ctx.save();
    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(wx, wy, radius, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
    imgNode.image(canvas);
    mapState.fillNeedsSync = true;
    batchDrawMapLayers();
}

/**
 * Части отрезка AB, лежащие вне открытого круга (центр cx,cy, радиус r).
 *
 * @param {number} ax
 * @param {number} ay
 * @param {number} bx
 * @param {number} by
 * @param {number} cx
 * @param {number} cy
 * @param {number} r
 * @returns {Array<{ x0: number, y0: number, x1: number, y1: number }>}
 */
function clipSegmentOutsideOpenDisk(ax, ay, bx, by, cx, cy, r) {
    const eps = 1e-9;
    const inside = (x, y) => Math.hypot(x - cx, y - cy) < r - 1e-7;
    const dx = bx - ax;
    const dy = by - ay;
    const a = dx * dx + dy * dy;
    const ts = [0, 1];
    if (a > eps) {
        const fx = ax - cx;
        const fy = ay - cy;
        const b = 2 * (fx * dx + fy * dy);
        const c = fx * fx + fy * fy - r * r;
        const disc = b * b - 4 * a * c;
        if (disc >= 0) {
            const s = Math.sqrt(disc);
            const t1 = (-b - s) / (2 * a);
            const t2 = (-b + s) / (2 * a);
            if (t1 > eps && t1 < 1 - eps) {
                ts.push(t1);
            }
            if (t2 > eps && t2 < 1 - eps) {
                ts.push(t2);
            }
        }
    }
    ts.sort((x, y) => x - y);
    const uniq = [];
    for (let ti = 0; ti < ts.length; ti++) {
        if (!uniq.length || Math.abs(ts[ti] - uniq[uniq.length - 1]) > 1e-6) {
            uniq.push(ts[ti]);
        }
    }
    /** @type {Array<{ x0: number, y0: number, x1: number, y1: number }>} */
    const out = [];
    for (let i = 0; i < uniq.length - 1; i++) {
        const t0 = uniq[i];
        const t1 = uniq[i + 1];
        const midt = (t0 + t1) / 2;
        const mx = ax + midt * dx;
        const my = ay + midt * dy;
        if (!inside(mx, my)) {
            const x0 = ax + t0 * dx;
            const y0 = ay + t0 * dy;
            const x1 = ax + t1 * dx;
            const y1 = ay + t1 * dy;
            if (Math.hypot(x1 - x0, y1 - y0) > eps) {
                out.push({ x0, y0, x1, y1 });
            }
        }
    }

    return out;
}

/**
 * Полилиния [x0,y0,…] без участков внутри открытого круга.
 *
 * @param {number[]} flat
 * @param {number} cx
 * @param {number} cy
 * @param {number} r
 * @returns {number[][]}
 */
function clipPolylineOutsideOpenDisk(flat, cx, cy, r) {
    const n = flat.length / 2;
    if (n < 2) {
        return [];
    }
    /** @type {number[][]} */
    const polylines = [];
    /** @type {number[] | null} */
    let chain = null;
    const epsJoin = 1.25;

    const flush = () => {
        if (chain && chain.length >= 4) {
            polylines.push(chain);
        }
        chain = null;
    };

    /**
     * @param {{ x0: number, y0: number, x1: number, y1: number }} p
     * @returns {void}
     */
    const appendSegment = (p) => {
        if (!chain) {
            chain = [p.x0, p.y0, p.x1, p.y1];

            return;
        }
        const lx = chain[chain.length - 2];
        const ly = chain[chain.length - 1];
        if (Math.hypot(p.x0 - lx, p.y0 - ly) < epsJoin) {
            if (Math.hypot(p.x1 - lx, p.y1 - ly) > 1e-4) {
                chain.push(p.x1, p.y1);
            }
        } else {
            flush();
            chain = [p.x0, p.y0, p.x1, p.y1];
        }
    };

    for (let i = 0; i < n - 1; i++) {
        const ax = flat[i * 2];
        const ay = flat[i * 2 + 1];
        const bx = flat[(i + 1) * 2];
        const by = flat[(i + 1) * 2 + 1];
        const pieces = clipSegmentOutsideOpenDisk(ax, ay, bx, by, cx, cy, r);
        if (pieces.length === 0) {
            flush();
            continue;
        }
        if (pieces.length === 2) {
            appendSegment(pieces[0]);
            flush();
            chain = [pieces[1].x0, pieces[1].y0, pieces[1].x1, pieces[1].y1];
            continue;
        }
        appendSegment(pieces[0]);
    }
    flush();

    return polylines;
}

/**
 * @param {number[]} a
 * @param {number[]} b
 * @returns {boolean}
 */
function flatCoordsApproximatelyEqual(a, b) {
    if (a.length !== b.length) {
        return false;
    }
    for (let i = 0; i < a.length; i++) {
        if (Math.abs(a[i] - b[i]) > 0.08) {
            return false;
        }
    }

    return true;
}

/**
 * Ластик по линиям: вычитает круг из полилиний (ландшафт и границы), заливка — в eraseFillDiskAt.
 *
 * @param {number} wx
 * @param {number} wy
 * @param {number} radius
 * @returns {void}
 */
function eraseLinesNearWorld(wx, wy, radius) {
    const group = mapState.landscapeDrawGroup;
    if (!group) {
        return;
    }
    let changed = false;
    const nodes = group.getChildren().slice();
    for (let ni = 0; ni < nodes.length; ni++) {
        const node = nodes[ni];
        if (node.getClassName() !== 'Line') {
            continue;
        }
        const ox = node.x();
        const oy = node.y();
        const pts = node.points();
        const flat = [];
        for (let i = 0; i < pts.length; i += 2) {
            flat.push(ox + pts[i], oy + pts[i + 1]);
        }
        const newPolylines = clipPolylineOutsideOpenDisk(flat, wx, wy, radius);
        const unchangedOne =
            newPolylines.length === 1 && flatCoordsApproximatelyEqual(newPolylines[0], flat);
        if (unchangedOne) {
            continue;
        }
        changed = true;
        const stroke = node.stroke();
        const dash = node.dash();
        node.destroy();
        for (let pi = 0; pi < newPolylines.length; pi++) {
            const pl = newPolylines[pi];
            if (pl.length < 4) {
                continue;
            }
            const local = pl.map((v, idx) => (idx % 2 === 0 ? v - ox : v - oy));
            const line = new Konva.Line({
                points: local,
                stroke,
                strokeWidth: clampMapLineWidth(node.strokeWidth()),
                lineCap: 'round',
                lineJoin: 'round',
                listening: false,
                perfectDrawEnabled: false,
                ...(dash && dash.length ? { dash: dash.slice() } : {}),
            });
            group.add(line);
        }
    }
    if (changed) {
        mapState.pruneStaleUndoStrokes?.();
        batchDrawMapLayers();
    }
}

function applyMapCursor(css) {
    const c = mapState.container;
    const st = mapState.stage;
    const inner = st && typeof st.getContent === 'function' ? st.getContent() : null;
    if (c) {
        c.style.cursor = css;
    }
    if (inner) {
        inner.style.cursor = css;
        inner.querySelectorAll('canvas').forEach((canvas) => {
            canvas.style.cursor = css;
        });
    }
}

/** @type {{
 *   selectedSpriteUrl: string | null,
 *   selectedSpritePath: string | null,
 *   spriteBase: string,
 *   storeUrl: string,
 *   updateUrlPattern: string,
 *   initialPlacements: Array<{ id: number, sprite_path: string, pos_x: number, pos_y: number }>,
 *   stage: import('konva/lib/Stage').default | null,
 *   worldGroup: import('konva/lib/Group').default | null,
 *   layer: import('konva/lib/Layer').default | null,
 *   rulerLayer: import('konva/lib/Layer').default | null,
 *   fixedRulersGroup: import('konva/lib/Group').default | null,
 *   container: HTMLElement | null,
 *   crosshairV: import('konva/lib/shapes/Line').default | null,
 *   crosshairH: import('konva/lib/shapes/Line').default | null,
 *   crosshairGroup: import('konva/lib/Group').default | null,
 *   mapBg: import('konva/lib/shapes/Rect').default | null,
 *   landscapeDrawGroup: import('konva/lib/Group').default | null,
 *   fillCanvas: HTMLCanvasElement | null,
 *   fillImageNode: import('konva/lib/shapes/Image').default | null,
 *   draggingSprite: boolean,
 *   drawMode: 'landscape' | 'borders' | 'fill' | 'erase' | null,
 *   landscapeLineWidth: number,
 *   landscapeStrokeKey: string,
 *   bordersLineWidth: number,
 *   bordersStrokeKey: string,
 *   fillColorKey: string,
 *   waterEdgeDecorate: boolean,
 *   eraseRadius: number,
 *   refreshMapCursor: (() => void) | null,
 *   abortLandscapeDraw: (() => void) | null,
 *   flashPlacementHint: ((msg: string) => void) | null,
 *   mapsCanvasSaveUrl: string | null,
 *   mapsFillUploadUrl: string | null,
 *   schedulePersistMapCanvas: (() => void) | null,
 *   pruneStaleUndoStrokes: (() => void) | null,
 *   fillNeedsSync: boolean,
 *   mapWidthPx: number,
 *   mapHeightPx: number,
 * }} */
const mapState = {
    selectedSpriteUrl: null,
    selectedSpritePath: null,
    spriteBase: '',
    storeUrl: '',
    updateUrlPattern: '',
    initialPlacements: [],
    stage: null,
    worldGroup: null,
    layer: null,
    rulerLayer: null,
    fixedRulersGroup: null,
    container: null,
    crosshairV: null,
    crosshairH: null,
    crosshairGroup: null,
    mapBg: null,
    landscapeDrawGroup: null,
    fillCanvas: null,
    fillImageNode: null,
    draggingSprite: false,
    /** Режим с панели «Ландшафт»: карандаш, границы, заливка или ластик. */
    drawMode: /** @type {'landscape' | 'borders' | 'fill' | 'erase' | null} */ (null),
    /** Толщина линии ландшафта перед рисованием (1…20). */
    landscapeLineWidth: DEFAULT_MAP_LINE_WIDTH,
    /** Ключ цвета линии ландшафта (см. MAP_LINE_STROKE_BY_KEY). */
    landscapeStrokeKey: 'black',
    /** Толщина штриха границ (1…20). */
    bordersLineWidth: DEFAULT_MAP_LINE_WIDTH,
    /** Ключ цвета штриха границ (см. MAP_LINE_STROKE_BY_KEY). */
    bordersStrokeKey: 'black',
    /** Ключ цвета заливки (см. FILL_PALETTE). */
    fillColorKey: 'grass',
    /** Пена/светлая кромка у заливки «Вода» (только при выборе цвета Вода). */
    waterEdgeDecorate: true,
    /** Радиус кисти ластика (1…100 мировых px). */
    eraseRadius: DEFAULT_ERASE_RADIUS,
    refreshMapCursor: null,
    abortLandscapeDraw: null,
    flashPlacementHint: null,
    pendingEditGroup: null,
    openMapObjectEditDialog: null,
    /** @type {'fantasy'|'science_fiction'|'modern'} */
    worldSetting: 'fantasy',
    mapLabelFontFamily: 'Cormorant Garamond',
    mapsCanvasSaveUrl: null,
    /** POST multipart PNG заливки (без base64 в JSON). */
    mapsFillUploadUrl: null,
    schedulePersistMapCanvas: null,
    pruneStaleUndoStrokes: null,
    /** Локально меняли слой заливки — нужно отправить PNG или явное очищение на сервер. */
    fillNeedsSync: false,
    /** Ширина мирового холста (px), с сервера или clampMapSide. */
    mapWidthPx: DEFAULT_MAP_SIDE_PX,
    /** Высота мирового холста (px). */
    mapHeightPx: DEFAULT_MAP_SIDE_PX,
};

function batchDrawMapLayers() {
    mapState.layer?.batchDraw();
    mapState.rulerLayer?.batchDraw();
}

/** @type {ReturnType<typeof setTimeout> | null} */
let mapCanvasPersistTimer = null;

/**
 * PNG из canvas как Blob (для multipart, без base64 в JSON).
 *
 * @param {HTMLCanvasElement} canvas
 * @returns {Promise<Blob>}
 */
function canvasToPngBlob(canvas) {
    return new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => {
                if (blob) {
                    resolve(blob);
                } else {
                    reject(new Error('toBlob вернул null'));
                }
            },
            'image/png',
        );
    });
}

/**
 * PNG из canvas без async (toDataURL + разбор base64) — для синхронного сохранения заливки при pagehide.
 * где fetch с большим телом может не успеть до выгрузки документа.
 *
 * @param {HTMLCanvasElement} canvas
 * @returns {Blob}
 */
function canvasToPngBlobSync(canvas) {
    const dataUrl = canvas.toDataURL('image/png');
    const parts = dataUrl.split(',');
    if (parts.length < 2) {
        throw new Error('toDataURL: нет данных');
    }
    const mimeMatch = parts[0].match(/:(.*?);/);
    const mime = mimeMatch ? mimeMatch[1] : 'image/png';
    const bstr = atob(parts[1]);
    const n = bstr.length;
    const u8 = new Uint8Array(n);
    for (let i = 0; i < n; i++) {
        u8[i] = bstr.charCodeAt(i);
    }

    return new Blob([u8], { type: mime });
}

/**
 * Сохраняет заливку синхронным XHR при выгрузке страницы: fetch в pagehide часто обрывается,
 * sendBeacon не гарантирует приём и в ряде браузеров ограничен по размеру тела.
 *
 * @returns {void}
 */
function syncPersistMapFillOnUnloadIfNeeded() {
    mapFillDebugLog('pagehide:syncPersistMapFillOnUnloadIfNeeded enter', {
        fillNeedsSync: mapState.fillNeedsSync,
        hasCanvas: !!mapState.fillCanvas,
        hasUrl: !!mapState.mapsFillUploadUrl,
    });
    if (!mapState.fillNeedsSync || !mapState.fillCanvas || !mapState.mapsFillUploadUrl) {
        mapFillDebugLog('pagehide:sync skip (nothing to send)');

        return;
    }
    let blob;
    try {
        blob = canvasToPngBlobSync(mapState.fillCanvas);
    } catch (e) {
        mapFillDebugLog('pagehide:sync canvasToPngBlobSync failed', e);

        return;
    }
    mapFillDebugLog('pagehide:sync XHR POST', { url: mapState.mapsFillUploadUrl, blobBytes: blob.size });
    try {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', mapState.mapsFillUploadUrl, false);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
        const fd = new FormData();
        fd.append('_token', csrfToken());
        fd.append('fill', blob, 'map_fill.png');
        xhr.send(fd);
        mapFillDebugLog('pagehide:sync XHR done', {
            status: xhr.status,
            responseLen: xhr.responseText?.length ?? 0,
            responseHead: (xhr.responseText || '').slice(0, 200),
        });
        if (xhr.status >= 200 && xhr.status < 300) {
            mapState.fillNeedsSync = false;
        }
    } catch (e) {
        mapFillDebugLog('pagehide:sync XHR threw', e);
    }
}

/**
 * Сообщение об ошибке сохранения карты (тост или консоль).
 *
 * @param {string} message
 * @param {string} [detail]
 * @returns {void}
 */
function showMapPersistError(message, detail) {
    mapFillDebugLog('persist ERROR (toast)', message, detail || '');
    if (typeof window.showFlashToastMessage === 'function') {
        window.showFlashToastMessage(message, 'error');
    } else {
        console.error(message);
    }
    if (detail) {
        console.error(detail);
    }
}

/**
 * Сохраняет только слой заливки (POST PNG), если выставлен fillNeedsSync.
 * Вызывается сразу после каждой заливки/ластика по заливке и из общего persist холста.
 *
 * @returns {Promise<boolean>} true — нечего отправлять или успех; false — ошибка (флаг синхронизации не сброшен при сбое)
 */
async function persistMapFillLayerOnly() {
    const canvas = mapState.fillCanvas;
    const fillUploadUrl = mapState.mapsFillUploadUrl;
    const tokenLen = csrfToken().length;
    mapFillDebugLog('persistMapFillLayerOnly enter', {
        fillNeedsSync: mapState.fillNeedsSync,
        hasCanvas: !!canvas,
        fillUploadUrl: fillUploadUrl || null,
        canvasW: canvas?.width,
        canvasH: canvas?.height,
        csrfTokenLength: tokenLen,
    });
    if (!mapState.fillNeedsSync || !canvas || !fillUploadUrl) {
        mapFillDebugLog('persistMapFillLayerOnly skip (no-op)', {
            fillNeedsSync: mapState.fillNeedsSync,
            hasCanvas: !!canvas,
            hasUrl: !!fillUploadUrl,
        });

        return true;
    }
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        mapFillDebugLog('persistMapFillLayerOnly abort: no 2d context');
        showMapPersistError('Заливка не сохранена: нет контекста canvas.');

        return false;
    }
    let blob;
    try {
        blob = await canvasToPngBlob(canvas);
    } catch (err) {
        mapFillDebugLog('persistMapFillLayerOnly toBlob failed', err);
        showMapPersistError(
            'Не удалось подготовить PNG заливки.',
            err instanceof Error ? err.message : String(err),
        );

        return false;
    }
    mapFillDebugLog('persistMapFillLayerOnly blob ready', { bytes: blob.size, type: blob.type });
    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('fill', blob, 'map_fill.png');
    try {
        mapFillDebugLog('persistMapFillLayerOnly fetch POST start');
        const res = await fetch(fillUploadUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: formData,
            credentials: 'same-origin',
        });
        const text = await res.text().catch(() => '');
        mapFillDebugLog('persistMapFillLayerOnly fetch done', {
            ok: res.ok,
            status: res.status,
            statusText: res.statusText,
            bodyHead: text.slice(0, 400),
        });
        if (res.ok) {
            mapState.fillNeedsSync = false;
            mapFillDebugLog('persistMapFillLayerOnly success, fillNeedsSync=false');

            return true;
        }
        showMapPersistError(
            `Заливка не сохранена (${res.status}).`,
            text.slice(0, 800),
        );

        return false;
    } catch (err) {
        mapFillDebugLog('persistMapFillLayerOnly fetch threw', err);
        showMapPersistError(
            'Не удалось отправить заливку (сеть).',
            err instanceof Error ? err.message : String(err),
        );

        return false;
    }
}

/**
 * Сохраняет заливку (POST multipart PNG), затем линии (PUT JSON).
 * Заливка идёт первой: не блокируется тяжёлым JSON линий; при ошибке линий PNG уже на сервере.
 * Линии не блокируют заливку при сбое PUT (раньше return после линий глушил POST заливки).
 *
 * @param {{ keepalive?: boolean }} [options]
 * @returns {Promise<void>}
 */
async function persistMapCanvas(options = {}) {
    const canvas = mapState.fillCanvas;
    const group = mapState.landscapeDrawGroup;
    const linesUrl = mapState.mapsCanvasSaveUrl;
    const fillUploadUrl = mapState.mapsFillUploadUrl;

    if (!canvas) {
        mapFillDebugLog('persistMapCanvas abort: no fill canvas');

        return;
    }

    const shouldSaveLines = !!(linesUrl && group);
    const wantFill = !!(fillUploadUrl && mapState.fillNeedsSync);

    mapFillDebugLog('persistMapCanvas', {
        shouldSaveLines,
        wantFill,
        keepalive: options.keepalive === true,
    });

    if (!shouldSaveLines && !wantFill) {
        mapFillDebugLog('persistMapCanvas no-op');

        return;
    }

    if (wantFill && fillUploadUrl) {
        const ok = await persistMapFillLayerOnly();
        mapFillDebugLog('persistMapCanvas after persistMapFillLayerOnly', { ok });
    }

    if (!shouldSaveLines) {
        return;
    }

    const lines = [];
    group.getChildren().forEach((node) => {
        if (node.getClassName() !== 'Line') {
            return;
        }
        const pts = sanitizeLinePointsForJson(node.points().slice());
        if (pts.length < 4) {
            return;
        }
        const dash = node.dash();
        const strokeRaw = node.stroke();
        const stroke =
            typeof strokeRaw === 'string' && strokeRaw.trim() !== ''
                ? strokeRaw
                : mapLineStrokeCss('black');
        lines.push({
            points: pts,
            stroke,
            strokeWidth: clampMapLineWidth(node.strokeWidth()),
            dash: dash && dash.length ? dash.map((x) => (Number.isFinite(Number(x)) ? Number(x) : 0)) : null,
        });
    });
    const payload = { lines };
    const body = JSON.stringify(payload);
    const useKeepalive =
        options.keepalive === true && new TextEncoder().encode(body).length <= 65536;
    try {
        const res = await fetch(linesUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body,
            credentials: 'same-origin',
            keepalive: useKeepalive,
        });
        if (!res.ok) {
            const detail = await res.text().catch(() => '');
            showMapPersistError(
                `Линии карты не сохранены (${res.status}).`,
                detail.slice(0, 800),
            );
        }
    } catch (err) {
        showMapPersistError(
            'Не удалось сохранить линии карты (сеть или уход со страницы).',
            err instanceof Error ? err.message : String(err),
        );
    }
}

/**
 * Откладывает сохранение холста после правок.
 *
 * @returns {void}
 */
function schedulePersistMapCanvas() {
    if (!mapState.mapsCanvasSaveUrl && !mapState.mapsFillUploadUrl) {
        return;
    }
    if (mapCanvasPersistTimer !== null) {
        window.clearTimeout(mapCanvasPersistTimer);
    }
    mapCanvasPersistTimer = window.setTimeout(() => {
        mapCanvasPersistTimer = null;
        void persistMapCanvas();
    }, 400);
}

/**
 * Немедленное сохранение (например при pagehide).
 *
 * Тело PUT — только JSON линий; заливка идёт отдельным POST (multipart), без keepalive для большого PNG.
 *
 * @returns {void}
 */
function flushMapCanvasPersist() {
    if (!mapState.mapsCanvasSaveUrl && !mapState.mapsFillUploadUrl) {
        return;
    }
    if (mapCanvasPersistTimer !== null) {
        window.clearTimeout(mapCanvasPersistTimer);
        mapCanvasPersistTimer = null;
    }
    void persistMapCanvas();
}

mapState.schedulePersistMapCanvas = schedulePersistMapCanvas;

/**
 * В режимах карандаша и заливки объекты не участвуют в hit-test и не перетаскиваются — события идут на карту под ними.
 *
 * @returns {void}
 */
function applyMapObjectInteractionForDrawMode() {
    const wg = mapState.worldGroup;
    if (!wg) {
        return;
    }
    const block = mapState.drawMode === 'landscape' ||
        mapState.drawMode === 'borders' ||
        mapState.drawMode === 'fill' ||
        mapState.drawMode === 'erase';
    wg.getChildren().forEach((node) => {
        if (node.getClassName() === 'Group' && node.name() === 'map-object') {
            node.listening(!block);
            node.draggable(!block);
        }
    });
}

/** Порог «свободной» клетки: только почти белый фон; иначе сглаживание обводки (серые пиксели) даёт дыры в стене. */
const MASK_EMPTY_MIN_CHANNEL = 250;

/**
 * Маска «пусто» по растру линий: 1 — не стена, 0 — штрих/антиалиас у линии.
 *
 * Раньше использовали сумму каналов > 500 — светло-серые пиксели AA (например 180+180+180) считались пустотой,
 * «внешность» с края карты протекала внутрь через край линии, не оставалось interior — заливка не срабатывала.
 *
 * @param {ImageData} img
 * @returns {Uint8Array}
 */
function imageDataToEmptyMask(img) {
    const { data, width: W, height: H } = img;
    const mask = new Uint8Array(W * H);
    const m = MASK_EMPTY_MIN_CHANNEL;
    for (let i = 0; i < W * H; i++) {
        const o = i * 4;
        const r = data[o];
        const g = data[o + 1];
        const b = data[o + 2];
        mask[i] = r >= m && g >= m && b >= m ? 1 : 0;
    }

    return mask;
}

/**
 * Растеризация линий ландшафта и рамки листа в ImageData для заливки (белое = пусто, чёрное = стена).
 *
 * Рамка совпадает с видимой чёрной обводкой по периметру холста (см. initKonva).
 *
 * @param {import('konva/lib/Group').default} landscapeDrawGroup
 * @returns {ImageData | null}
 */
function rasterizeLandscapeStrokesToMask(landscapeDrawGroup) {
    const c = document.createElement('canvas');
    c.width = mapState.mapWidthPx;
    c.height = mapState.mapHeightPx;
    const ctx = c.getContext('2d');
    if (!ctx) {
        return null;
    }
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, mapState.mapWidthPx, mapState.mapHeightPx);
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    landscapeDrawGroup.getChildren().forEach((node) => {
        if (node.getClassName() !== 'Line') {
            return;
        }
        const pts = node.points();
        const ox = node.x();
        const oy = node.y();
        if (pts.length < 4) {
            return;
        }
        ctx.strokeStyle = typeof node.stroke === 'function' ? node.stroke() : '#000000';
        ctx.lineWidth = clampMapLineWidth(
            typeof node.strokeWidth === 'function' ? node.strokeWidth() : DEFAULT_MAP_LINE_WIDTH,
        );
        const dash = node.dash();
        if (dash && dash.length > 0) {
            ctx.setLineDash(dash);
        } else {
            ctx.setLineDash([]);
        }
        ctx.beginPath();
        ctx.moveTo(ox + pts[0], oy + pts[1]);
        for (let p = 2; p < pts.length; p += 2) {
            ctx.lineTo(ox + pts[p], oy + pts[p + 1]);
        }
        ctx.stroke();
        ctx.setLineDash([]);
    });

    ctx.strokeStyle = MAP_CANVAS_EDGE_STROKE;
    ctx.lineWidth = MAP_CANVAS_EDGE_STROKE_WIDTH;
    ctx.lineCap = 'butt';
    ctx.lineJoin = 'miter';
    ctx.setLineDash([]);
    ctx.strokeRect(0, 0, mapState.mapWidthPx, mapState.mapHeightPx);

    return ctx.getImageData(0, 0, mapState.mapWidthPx, mapState.mapHeightPx);
}

/**
 * RGBA для ImageData из ключа палитры.
 *
 * @param {string} key
 * @returns {{ r: number, g: number, b: number, a: number }}
 */
function fillRgbaFromKey(key) {
    const s = FILL_PALETTE[key] ?? FILL_PALETTE.grass;
    const m = s.match(/rgba?\(\s*([^)]+)\)/);
    if (!m) {
        return { r: 168, g: 212, b: 168, a: 198 };
    }
    const parts = m[1].split(',').map((x) => parseFloat(String(x).trim()));
    if (parts.length === 4) {
        return {
            r: parts[0] | 0,
            g: parts[1] | 0,
            b: parts[2] | 0,
            a: Math.round(parts[3] * 255),
        };
    }

    return { r: parts[0] | 0, g: parts[1] | 0, b: parts[2] | 0, a: 255 };
}

/**
 * Светлее базового цвета воды — «пена» у кромки залитой области.
 *
 * @param {{ r: number, g: number, b: number, a: number }} base
 * @returns {{ r: number, g: number, b: number, a: number }}
 */
function waterFoamRgbaFromBase(base) {
    const lift = 0.5;
    const aBoost = 14;

    return {
        r: Math.min(255, Math.round(base.r + (255 - base.r) * lift)),
        g: Math.min(255, Math.round(base.g + (255 - base.g) * lift)),
        b: Math.min(255, Math.round(base.b + (255 - base.b) * lift)),
        a: Math.min(255, Math.round(base.a + aBoost)),
    };
}

/**
 * Линейное смешивание двух RGBA (каналы 0…255).
 *
 * @param {{ r: number, g: number, b: number, a: number }} a
 * @param {{ r: number, g: number, b: number, a: number }} b
 * @param {number} t доля второго цвета 0…1
 * @returns {{ r: number, g: number, b: number, a: number }}
 */
function blendRgbaComponents(a, b, t) {
    const u = Math.max(0, Math.min(1, t));

    return {
        r: Math.round(a.r * (1 - u) + b.r * u),
        g: Math.round(a.g * (1 - u) + b.g * u),
        b: Math.round(a.b * (1 - u) + b.b * u),
        a: Math.round(a.a * (1 - u) + b.a * u),
    };
}

/**
 * Отмечает пиксели залитого компонента, граничащие с не-заливкой (берег).
 *
 * @param {number} W
 * @param {number} H
 * @param {Uint8Array} visited маска текущей заливки (1 = эта порция)
 * @returns {Uint8Array} coast[i]=1 у кромки
 */
function markWaterCoastFromVisited(W, H, visited) {
    const coast = new Uint8Array(W * H);
    const inVisited = (nx, ny) => {
        if (nx < 0 || nx >= W || ny < 0 || ny >= H) {
            return false;
        }

        return visited[ny * W + nx] === 1;
    };
    for (let y = 0; y < H; y++) {
        for (let x = 0; x < W; x++) {
            const i = y * W + x;
            if (!visited[i]) {
                continue;
            }
            if (
                !inVisited(x - 1, y) ||
                !inVisited(x + 1, y) ||
                !inVisited(x, y - 1) ||
                !inVisited(x, y + 1)
            ) {
                coast[i] = 1;
            }
        }
    }

    return coast;
}

/**
 * Расстояние от кромки вглубь залитой области (4-соседи): BFS от всех «береговых» пикселей.
 * Учитываются только слои 0…maxDepth−1; дальше dist = −1 (базовый цвет заливки без изменений).
 *
 * @param {number} W
 * @param {number} H
 * @param {Uint8Array} visited
 * @param {Uint8Array} coast
 * @param {number} maxDepth число слоёв (например 6 → расстояния 0…5)
 * @returns {Int16Array} dist[i] ≥ 0 внутри полосы, иначе −1
 */
function waterInwardDistanceFromCoast(W, H, visited, coast, maxDepth) {
    const dist = new Int16Array(W * H);
    for (let i = 0; i < W * H; i++) {
        dist[i] = -1;
    }
    /** @type {number[]} */
    const q = [];
    for (let i = 0; i < W * H; i++) {
        if (visited[i] && coast[i]) {
            dist[i] = 0;
            q.push(i);
        }
    }
    for (let qh = 0; qh < q.length; qh++) {
        const i = q[qh];
        const d0 = dist[i];
        if (d0 >= maxDepth - 1) {
            continue;
        }
        const nd = d0 + 1;
        if (nd >= maxDepth) {
            continue;
        }
        const x = i % W;
        const y = (i / W) | 0;
        const relax = (j) => {
            if (!visited[j] || dist[j] !== -1) {
                return;
            }
            dist[j] = nd;
            q.push(j);
        };
        if (x > 0) {
            relax(i - 1);
        }
        if (x < W - 1) {
            relax(i + 1);
        }
        if (y > 0) {
            relax(i - W);
        }
        if (y < H - 1) {
            relax(i + W);
        }
    }

    return dist;
}

/**
 * Вдоль границы заливки «Вода»: полоса до {@link WATER_FOAM_BAND_DEPTH_PX} пикселей от берега,
 * градиент от светлой пены к основному цвету воды.
 *
 * @param {ImageData} imageData
 * @param {number} W
 * @param {number} H
 * @param {Uint8Array} visited
 * @param {{ r: number, g: number, b: number, a: number }} baseRgba
 * @returns {void}
 */
function applyWaterFoamAlongFilledRegion(imageData, W, H, visited, baseRgba) {
    const coast = markWaterCoastFromVisited(W, H, visited);
    const foam = waterFoamRgbaFromBase(baseRgba);
    const depth = Math.max(1, Math.min(64, WATER_FOAM_BAND_DEPTH_PX));
    const distMap = waterInwardDistanceFromCoast(W, H, visited, coast, depth);
    const px = imageData.data;
    const denom = depth <= 1 ? 1 : depth - 1;

    for (let i = 0; i < W * H; i++) {
        const di = distMap[i];
        if (di < 0) {
            continue;
        }
        const u = depth <= 1 ? 0 : di / denom;
        const c = blendRgbaComponents(foam, baseRgba, u);
        const o = i * 4;
        px[o] = c.r;
        px[o + 1] = c.g;
        px[o + 2] = c.b;
        px[o + 3] = c.a;
    }
}

/**
 * Каталог: категория → типы → файлы в папке категории (UTF-8 в URL).
 * @type {Array<{ id: string, folder: string, label: string, types: Array<{ id: string, label: string, files: string[] }> }>}
 */
export const MAP_SPRITE_CATALOG = [
    {
        id: 'settlements',
        folder: 'Поселения',
        label: 'Поселения',
        types: [
            { id: 'city', label: 'Город', files: ['gorod_1.svg', 'gorod_2.svg', 'gorod_3.svg'] },
            { id: 'village', label: 'Деревня', files: ['derevnya_1.svg', 'derevnya_2_melnitsa.svg', 'derevnya_3.svg'] },
            { id: 'castle', label: 'Замок', files: ['zamok_1.svg', 'zamok_2.svg'] },
            { id: 'homestead', label: 'Хутор', files: ['hutor_1.svg'] },
            { id: 'bastion', label: 'Бастион', files: ['bastion_1.svg'] },
        ],
    },
    {
        id: 'mountains',
        folder: 'Горы',
        label: 'Горы',
        types: [
            { id: 'range', label: 'Горный массив', files: ['massiv_1.svg'] },
            { id: 'peak', label: 'Гора', files: ['gora_1.svg'] },
            { id: 'rocks', label: 'Скалы', files: ['skaly_1.svg'] },
            { id: 'volcano', label: 'Вулкан', files: ['vulkan_1.svg'] },
        ],
    },
    {
        id: 'forests',
        folder: 'Леса',
        label: 'Леса',
        types: [
            { id: 'deciduous', label: 'Лиственный лес', files: ['listvennyj_1.svg'] },
            { id: 'coniferous', label: 'Хвойный лес', files: ['hvojnyj_1.svg'] },
            { id: 'swamp', label: 'Болотистый лес', files: ['bolotistyj_1.svg'] },
            { id: 'dead', label: 'Мёртвый лес', files: ['mjortvyj_1.svg'] },
        ],
    },
];

/**
 * @param {string} base
 * @param {string} folder
 * @param {string} file
 */
function spriteUrl(base, folder, file) {
    const b = base.replace(/\/$/, '');
    const f = encodeURIComponent(folder);
    const n = encodeURIComponent(file);

    return `${b}/${f}/${n}`;
}

/**
 * @param {string} spritePath путь вида «Папка/файл.svg»
 * @returns {{ folder: string, file: string } | null}
 */
function splitSpritePath(spritePath) {
    const s = spritePath.trim();
    const i = s.indexOf('/');
    if (i <= 0 || i >= s.length - 1) {
        return null;
    }

    return { folder: s.slice(0, i), file: s.slice(i + 1) };
}

/**
 * @returns {string}
 */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/**
 * @param {number} id
 * @param {number} posX
 * @param {number} posY
 */
async function persistSpritePosition(id, posX, posY) {
    const pattern = mapState.updateUrlPattern;
    if (!pattern) {
        return;
    }
    const url = pattern.replace('__ID__', String(id));
    try {
        await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ pos_x: posX, pos_y: posY }),
        });
    } catch {
        // ignore
    }
}

/**
 * @param {number} id
 * @returns {Promise<boolean>}
 */
async function persistSpriteDelete(id) {
    const pattern = mapState.updateUrlPattern;
    if (!pattern) {
        return false;
    }
    const url = pattern.replace('__ID__', String(id));
    try {
        const res = await fetch(url, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });

        return res.ok;
    } catch {
        return false;
    }
}

/**
 * @param {string} spritePath
 * @param {number} posX
 * @param {number} posY
 * @returns {Promise<{ id: number } | null>}
 */
async function persistNewSprite(spritePath, posX, posY) {
    const storeUrl = mapState.storeUrl;
    if (!storeUrl) {
        return null;
    }
    const res = await fetch(storeUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ sprite_path: spritePath, pos_x: posX, pos_y: posY }),
    });
    if (!res.ok) {
        return null;
    }
    const data = await res.json();

    return {
        id: data.id,
        title: data.title ?? null,
        description: data.description ?? null,
    };
}

/**
 * @param {string} url
 * @returns {Promise<HTMLImageElement>}
 */
function loadImage(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error(`Не удалось загрузить ${url}`));
        img.src = url;
    });
}

/**
 * Границы перетаскивания объекта; `labelReserve` — место под подпись под спрайтом (0, если подписи нет).
 *
 * @param {number} labelReserve
 */
function spriteDragBounds(labelReserve) {
    const minX = 0;
    const minY = 0;
    const maxX = mapState.mapWidthPx - PLACE_SIZE;
    const maxY = mapState.mapHeightPx - PLACE_SIZE - labelReserve;

    return { minX, minY, maxX, maxY };
}

/**
 * Подпись на карте показывается только при непустом названии (описание без названия на полотне не выводится).
 *
 * @param {string | null | undefined} title
 * @returns {boolean}
 */
function shouldShowMapLabel(title) {
    return (title || '').trim() !== '';
}

/**
 * Убирает невидимые символы и лишние пробелы — иначе Konva даёт «кривую» центровку строк.
 *
 * У неперенесённой первой строки Konva не обрезает хвостовые пробелы при измерении ширины;
 * неразрывные пробелы и NBSP тоже смещают визуальный центр.
 *
 * @param {string | null | undefined} raw
 * @returns {string}
 */
function formatMapLabelText(raw) {
    let t = (raw || '')
        .replace(/[\u200B-\u200D\uFEFF]/g, '')
        .replace(/\u00a0|\u202f/g, ' ')
        .replace(/\r\n/g, '\n');
    t = t
        .split('\n')
        .map((line) => line.replace(/\s+/g, ' ').trim())
        .filter((line) => line.length > 0)
        .join('\n')
        .trim();
    t = t.replace(/[ \t]+/g, ' ');

    return t;
}

/**
 * Подпись под спрайтом: полное название, по центру относительно картинки, перенос по словам (ширина блока 2× объект).
 *
 * Курсив отключён: при align center наклон шрифта визуально смещает первую строку относительно спрайта.
 *
 * @param {string | null | undefined} title
 * @returns {{ node: import('konva/lib/shapes/Text').default, reserve: number } | null}
 */
function buildMapLabelLayer(title) {
    const formatted = formatMapLabelText(title ?? '');
    if (!formatted) {
        return null;
    }
    const fontFamily = mapState.mapLabelFontFamily || 'Cormorant Garamond';

    const textNode = new Konva.Text({
        name: 'map-object-label',
        x: (PLACE_SIZE - LABEL_WRAP_WIDTH) / 2,
        y: PLACE_SIZE + LABEL_GAP_AFTER_IMAGE,
        width: LABEL_WRAP_WIDTH,
        text: formatted,
        fontSize: MAP_LABEL_FONT_SIZE,
        lineHeight: 1.28,
        fontFamily,
        fontStyle: 'normal',
        fill: 'rgba(28, 26, 22, 0.96)',
        align: 'center',
        wrap: 'word',
        padding: 0,
        ellipsis: false,
        listening: true,
        shadowColor: 'rgba(255, 252, 245, 0.55)',
        shadowBlur: 2.5,
        shadowOffset: { x: 0, y: 0 },
        shadowOpacity: 1,
    });
    const reserve = LABEL_GAP_AFTER_IMAGE + textNode.height() + LABEL_PADDING_BOTTOM;

    return { node: textNode, reserve };
}

/**
 * Геометрия общей hit-области: без зазоров между спрайтом и подписью для Konva hit-test.
 *
 * @param {import('konva/lib/shapes/Rect').default} rect
 * @param {number} reserveBelowImage То же, что mapLabelReserve (пространство под низом картинки).
 */
function setMapObjectHitFillGeometry(rect, reserveBelowImage) {
    if (reserveBelowImage <= 0) {
        rect.setAttrs({ x: 0, y: 0, width: PLACE_SIZE, height: PLACE_SIZE });
    } else {
        rect.setAttrs({
            x: (PLACE_SIZE - LABEL_WRAP_WIDTH) / 2,
            y: 0,
            width: LABEL_WRAP_WIDTH,
            height: PLACE_SIZE + reserveBelowImage,
        });
    }
}

/**
 * @param {number} reserveBelowImage
 * @returns {import('konva/lib/shapes/Rect').default}
 */
function createMapObjectHitFillRect(reserveBelowImage) {
    const r = new Konva.Rect({
        name: MAP_OBJECT_HIT_FILL,
        fill: 'rgba(0,0,0,0.003)',
        listening: true,
        perfectDrawEnabled: false,
    });
    setMapObjectHitFillGeometry(r, reserveBelowImage);

    return r;
}

/**
 * @param {import('konva/lib/Node').default | null} node
 * @returns {import('konva/lib/Group').default | null}
 */
function findMapObjectGroup(node) {
    let n = node instanceof Konva.Node ? node : null;
    while (n) {
        if (n.name() === 'map-object') {
            return /** @type {import('konva/lib/Group').default} */ (n);
        }
        n = n.getParent();
    }

    return null;
}

/**
 * Подсветка при наведении: мягкое «солнечное» свечение под SVG + лёгкая тень (без прямоугольной рамки).
 *
 * @param {import('konva/lib/Group').default} group
 * @param {boolean} on
 */
function setMapObjectHoverHighlight(group, on) {
    const halo = group.findOne((node) => node.name() === MAP_OBJECT_HOVER_HALO);
    const img = group.findOne((node) => node.name() === 'map-object-img');
    if (halo) {
        halo.visible(on);
    }
    if (img) {
        if (on) {
            img.shadowColor('rgba(160, 110, 40, 0.5)');
            img.shadowBlur(20);
            img.shadowOffset({ x: 0, y: 2 });
            img.shadowOpacity(0.9);
        } else {
            img.shadowBlur(0);
            img.shadowOpacity(0);
        }
    }
    batchDrawMapLayers();
}

/**
 * @param {import('konva/lib/Group').default} group
 * @param {string} title
 */
function replaceMapLabelLayer(group, title) {
    const old = group.findOne((node) => node.name() === 'map-object-label');
    old?.destroy();
    const built = buildMapLabelLayer(title);
    const hit = group.findOne((node) => node.name() === MAP_OBJECT_HIT_FILL);
    if (built) {
        group.add(built.node);
        group.setAttr('mapLabelReserve', built.reserve);
        if (hit) {
            setMapObjectHitFillGeometry(hit, built.reserve);
        }
    } else {
        group.setAttr('mapLabelReserve', 0);
        if (hit) {
            setMapObjectHitFillGeometry(hit, 0);
        }
    }
    batchDrawMapLayers();
}

/**
 * @param {number} id
 * @param {string} title
 * @param {string} description
 * @returns {Promise<boolean>}
 */
async function persistMapObjectText(id, title, description) {
    const pattern = mapState.updateUrlPattern;
    if (!pattern) {
        return false;
    }
    const url = pattern.replace('__ID__', String(id));
    try {
        const res = await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ title, description }),
        });

        return res.ok;
    } catch {
        return false;
    }
}

/**
 * @param {import('konva/lib/Group').default} group
 * @param {number | null} mapSpriteId
 */
function bindMapObjectGroup(group, mapSpriteId) {
    if (!mapState.container) {
        return;
    }
    group.on('mouseenter', () => {
        if (mapState.drawMode === 'landscape' ||
        mapState.drawMode === 'borders' ||
        mapState.drawMode === 'fill' ||
        mapState.drawMode === 'erase') {
            return;
        }
        if (mapState.draggingSprite) {
            return;
        }
        applyMapCursor('grab');
        setMapObjectHoverHighlight(group, true);
    });
    group.on('mouseleave', () => {
        if (mapState.drawMode === 'landscape' ||
        mapState.drawMode === 'borders' ||
        mapState.drawMode === 'fill' ||
        mapState.drawMode === 'erase') {
            return;
        }
        if (!mapState.draggingSprite) {
            mapState.refreshMapCursor?.();
        }
        setMapObjectHoverHighlight(group, false);
    });
    group.on('dragstart', () => {
        if (mapState.drawMode === 'landscape' ||
        mapState.drawMode === 'borders' ||
        mapState.drawMode === 'fill' ||
        mapState.drawMode === 'erase') {
            return;
        }
        mapState.draggingSprite = true;
        applyMapCursor('grabbing');
        setMapObjectHoverHighlight(group, false);
    });
    group.on('dragend', () => {
        mapState.draggingSprite = false;
        const id = mapSpriteId ?? group.getAttr('mapSpriteId');
        if (id != null) {
            void persistSpritePosition(Number(id), group.x(), group.y());
        }
        const st = mapState.stage;
        const pos = st?.getPointerPosition();
        if (pos && st) {
            const hit = st.getIntersection(pos);
            const g = findMapObjectGroup(hit instanceof Konva.Node ? hit : null);
            if (g === group) {
                applyMapCursor('grab');
                setMapObjectHoverHighlight(group, true);

                return;
            }
        }
        mapState.refreshMapCursor?.();
    });
}

/**
 * @param {HTMLImageElement} img
 * @param {number} posX левый верхний угол спрайта
 * @param {number} posY
 * @param {number | null} mapSpriteId
 * @param {string | null | undefined} title
 * @param {string | null | undefined} description
 * @returns {import('konva/lib/Group').default}
 */
function createMapObjectGroup(img, posX, posY, mapSpriteId, title, description) {
    const builtLabel = buildMapLabelLayer(title);
    const labelReserve = builtLabel ? builtLabel.reserve : 0;
    const group = new Konva.Group({
        x: posX,
        y: posY,
        draggable: true,
        name: 'map-object',
        dragBoundFunc: (pos) => {
            const r = group.getAttr('mapLabelReserve') ?? labelReserve;
            const { minX, minY, maxX, maxY } = spriteDragBounds(r);

            return {
                x: Math.max(minX, Math.min(maxX, pos.x)),
                y: Math.max(minY, Math.min(maxY, pos.y)),
            };
        },
    });
    if (mapSpriteId != null) {
        group.setAttr('mapSpriteId', mapSpriteId);
    }
    group.setAttr('mapTitle', title ?? '');
    group.setAttr('mapDescription', description ?? '');
    group.setAttr('mapLabelReserve', labelReserve);
    const hitFill = createMapObjectHitFillRect(labelReserve);
    group.add(hitFill);
    const rHalo = PLACE_SIZE * 0.72;
    const hoverHalo = new Konva.Circle({
        name: MAP_OBJECT_HOVER_HALO,
        x: PLACE_SIZE / 2,
        y: PLACE_SIZE / 2,
        radius: rHalo,
        fillRadialGradientStartPoint: { x: 0, y: 0 },
        fillRadialGradientEndPoint: { x: 0, y: 0 },
        fillRadialGradientStartRadius: 0,
        fillRadialGradientEndRadius: rHalo,
        fillRadialGradientColorStops: [
            0,
            'rgba(255, 220, 165, 0.42)',
            0.45,
            'rgba(210, 160, 75, 0.18)',
            0.78,
            'rgba(190, 140, 60, 0.06)',
            1,
            'rgba(190, 140, 60, 0)',
        ],
        visible: false,
        listening: false,
        perfectDrawEnabled: false,
    });
    group.add(hoverHalo);
    const kImg = new Konva.Image({
        x: 0,
        y: 0,
        image: img,
        width: PLACE_SIZE,
        height: PLACE_SIZE,
        name: 'map-object-img',
    });
    group.add(kImg);
    if (builtLabel) {
        group.add(builtLabel.node);
    }
    bindMapObjectGroup(group, mapSpriteId);
    group.on('dblclick dbltap', (e) => {
        if (mapState.drawMode === 'landscape' ||
        mapState.drawMode === 'borders' ||
        mapState.drawMode === 'fill' ||
        mapState.drawMode === 'erase') {
            return;
        }
        e.cancelBubble = true;
        mapState.openMapObjectEditDialog?.(group);
    });

    return group;
}

/**
 * @param {number} worldX центр
 * @param {number} worldY центр
 */
async function placeSpriteAt(worldX, worldY) {
    const path = mapState.selectedSpritePath;
    const url = mapState.selectedSpriteUrl;
    const wg = mapState.worldGroup;
    const layer = mapState.layer;
    if (!path || !url || !wg || !layer) {
        return;
    }
    if (worldX < 0 || worldX > mapState.mapWidthPx || worldY < 0 || worldY > mapState.mapHeightPx) {
        return;
    }
    const half = PLACE_SIZE / 2;
    const posX = worldX - half;
    const posY = worldY - half;
    try {
        const img = await loadImage(url);
        const saved = await persistNewSprite(path, posX, posY);
        if (!saved) {
            return;
        }
        const kGroup = createMapObjectGroup(img, posX, posY, saved.id, saved.title, saved.description);
        wg.add(kGroup);
        kGroup.moveToTop();
        applyMapObjectInteractionForDrawMode();
        batchDrawMapLayers();
    } catch {
        // ignore failed loads / save
    }
}

/**
 * @param {HTMLElement} container
 * @param {{ mapDrawingLines?: Array<{ points: number[], stroke: string, dash: number[] | null }>, mapFillUrl?: string | null, mapsCanvasSaveUrl?: string | null, mapsFillUploadUrl?: string | null, mapWidth?: number, mapHeight?: number }} [canvasOpts]
 */
function initKonva(container, canvasOpts = {}) {
    mapState.container = container;
    mapState.mapsCanvasSaveUrl = canvasOpts.mapsCanvasSaveUrl || null;
    mapState.mapsFillUploadUrl = canvasOpts.mapsFillUploadUrl || null;
    mapState.mapWidthPx = clampMapSide(canvasOpts.mapWidth);
    mapState.mapHeightPx = clampMapSide(canvasOpts.mapHeight);

    const stage = new Konva.Stage({
        container,
        width: container.clientWidth || 800,
        height: container.clientHeight || 600,
    });

    const layer = new Konva.Layer();
    const rulerLayer = new Konva.Layer();
    const worldGroup = new Konva.Group({ name: 'world' });
    const fixedRulersGroup = new Konva.Group({ name: 'fixed-rulers', listening: false });

    const bg = new Konva.Rect({
        x: 0,
        y: 0,
        width: mapState.mapWidthPx,
        height: mapState.mapHeightPx,
        fill: '#d8d6cf',
        stroke: '#7a786f',
        strokeWidth: 2,
        name: 'map-bg',
    });
    worldGroup.add(bg);
    mapState.mapBg = bg;

    const landscapeFillGroup = new Konva.Group({ name: 'map-landscape-fill', listening: false });
    const fillCanvasEl = document.createElement('canvas');
    fillCanvasEl.width = mapState.mapWidthPx;
    fillCanvasEl.height = mapState.mapHeightPx;
    const fillImageNode = new Konva.Image({
        image: fillCanvasEl,
        x: 0,
        y: 0,
        width: mapState.mapWidthPx,
        height: mapState.mapHeightPx,
        listening: false,
        name: 'map-fill-image',
    });
    landscapeFillGroup.add(fillImageNode);
    worldGroup.add(landscapeFillGroup);
    mapState.fillCanvas = fillCanvasEl;
    mapState.fillImageNode = fillImageNode;

    const gridGroup = new Konva.Group({ listening: false, name: 'map-grid' });
    const gridStroke = 'rgba(168, 166, 160, 0.28)';
    for (let gx = 0; gx <= mapState.mapWidthPx; gx += GRID_STEP) {
        gridGroup.add(
            new Konva.Line({
                points: [gx, 0, gx, mapState.mapHeightPx],
                stroke: gridStroke,
                strokeWidth: 1,
                listening: false,
                perfectDrawEnabled: false,
            }),
        );
    }
    for (let gy = 0; gy <= mapState.mapHeightPx; gy += GRID_STEP) {
        gridGroup.add(
            new Konva.Line({
                points: [0, gy, mapState.mapWidthPx, gy],
                stroke: gridStroke,
                strokeWidth: 1,
                listening: false,
                perfectDrawEnabled: false,
            }),
        );
    }
    worldGroup.add(gridGroup);

    const mapCanvasEdgeRect = new Konva.Rect({
        x: 0,
        y: 0,
        width: mapState.mapWidthPx,
        height: mapState.mapHeightPx,
        fillEnabled: false,
        stroke: MAP_CANVAS_EDGE_STROKE,
        strokeWidth: MAP_CANVAS_EDGE_STROKE_WIDTH,
        listening: false,
        name: 'map-canvas-edge',
    });
    worldGroup.add(mapCanvasEdgeRect);

    const landscapeDrawGroup = new Konva.Group({ name: 'map-landscape-draw', listening: false });
    worldGroup.add(landscapeDrawGroup);
    mapState.landscapeDrawGroup = landscapeDrawGroup;

    const MAP_UNDO_STACK_MAX = 10;
    /** @type {Array<{ type: 'stroke', line: import('konva/lib/shapes/Line').default } | { type: 'fill', imageData: ImageData }>} */
    const mapUndoStack = [];

    /**
     * Включает или отключает кнопку «Отменить» в шапке по длине стека отмены.
     *
     * @returns {void}
     */
    function syncMapUndoUi() {
        const btn = document.getElementById('map-undo-last-action');
        if (!btn) {
            return;
        }
        btn.disabled = mapUndoStack.length === 0;
    }

    /**
     * Добавляет запись в стек отмены (не больше MAP_UNDO_STACK_MAX последних).
     *
     * @param {{ type: 'stroke', line: import('konva/lib/shapes/Line').default } | { type: 'fill', imageData: ImageData }} entry
     * @returns {void}
     */
    function pushMapUndoEntry(entry) {
        mapUndoStack.push(entry);
        while (mapUndoStack.length > MAP_UNDO_STACK_MAX) {
            mapUndoStack.shift();
        }
        syncMapUndoUi();
    }

    /**
     * Отменяет последнее действие на карте (штрих или заливку).
     *
     * @returns {void}
     */
    function undoLastMapAction() {
        try {
            while (mapUndoStack.length > 0) {
                const entry = mapUndoStack.pop();
                if (!entry) {
                    return;
                }
                if (entry.type === 'stroke') {
                    if (entry.line.getParent()) {
                        entry.line.destroy();
                        batchDrawMapLayers();
                        schedulePersistMapCanvas();
                        pruneStaleUndoStrokes();

                        return;
                    }

                    continue;
                }
                if (entry.type === 'fill') {
                    const fctx = mapState.fillCanvas?.getContext('2d');
                    if (fctx && mapState.fillCanvas) {
                        fctx.putImageData(entry.imageData, 0, 0);
                        fillImageNode.image(mapState.fillCanvas);
                        mapState.fillNeedsSync = true;
                        batchDrawMapLayers();
                    }
                    mapFillDebugLog('undoLastMapAction fill branch, calling persistMapFillLayerOnly');
                    void persistMapFillLayerOnly().then((ok) => {
                        mapFillDebugLog('undoLastMapAction persistMapFillLayerOnly done', { ok });
                    });

                    return;
                }
            }
        } finally {
            syncMapUndoUi();
        }
    }

    /**
     * Убирает из стека отмены ссылки на штрихи, уже уничтоженные ластиком или иначе.
     *
     * @returns {void}
     */
    function pruneStaleUndoStrokes() {
        const kept = [];
        for (let i = 0; i < mapUndoStack.length; i++) {
            const e = mapUndoStack[i];
            if (e.type === 'fill') {
                kept.push(e);
            } else if (e.type === 'stroke' && e.line.getParent()) {
                kept.push(e);
            }
        }
        mapUndoStack.length = 0;
        for (let k = 0; k < kept.length; k++) {
            mapUndoStack.push(kept[k]);
        }
        syncMapUndoUi();
    }

    mapState.pruneStaleUndoStrokes = pruneStaleUndoStrokes;

    /**
     * Мировые координаты для действия заливки/снятия заливки: не на линейках, не по спрайту, внутри листа.
     *
     * @returns {{ x: number, y: number } | null}
     */
    function getWorldPointForMapFillAction() {
        const sp = stage.getPointerPosition();
        if (!sp || sp.x < RULER_W || sp.y < RULER_H) {
            return null;
        }
        const hit = stage.getIntersection(sp);
        if (findMapObjectGroup(hit instanceof Konva.Node ? hit : null) !== null) {
            return null;
        }
        const pos = worldGroup.getRelativePointerPosition();
        if (!pos || pos.x < 0 || pos.x > mapState.mapWidthPx || pos.y < 0 || pos.y > mapState.mapHeightPx) {
            return null;
        }

        return pos;
    }

    /**
     * Заливка по клику: связная компонента «пустых» клеток от точки клика (4-связность), без выхода за холст.
     * Стены — штрихи ландшафта/границ и чёрная рамка листа (как в маске rasterizeLandscapeStrokesToMask).
     * После заливки сразу отправляет PNG на сервер (await).
     *
     * @param {number} worldX
     * @param {number} worldY
     * @returns {Promise<void>}
     */
    async function runMapFillAt(worldX, worldY) {
        const ix = Math.floor(worldX);
        const iy = Math.floor(worldY);
        if (ix < 0 || ix >= mapState.mapWidthPx || iy < 0 || iy >= mapState.mapHeightPx) {
            return;
        }
        const maskImg = rasterizeLandscapeStrokesToMask(landscapeDrawGroup);
        if (!maskImg) {
            return;
        }
        const maskEmpty = imageDataToEmptyMask(maskImg);
        const W = mapState.mapWidthPx;
        const H = mapState.mapHeightPx;
        const idx = iy * W + ix;
        if (maskEmpty[idx] === 0) {
            mapState.flashPlacementHint?.('Клик по линии. Выберите область рядом со штрихом.');

            return;
        }
        const fillCanvas = mapState.fillCanvas;
        if (!fillCanvas) {
            return;
        }
        const fctx = fillCanvas.getContext('2d');
        if (!fctx) {
            return;
        }
        const fillData = fctx.getImageData(0, 0, W, H);
        const rgba = fillRgbaFromKey(mapState.fillColorKey);
        const queue = new Int32Array(W * H);
        let head = 0;
        let tail = 0;
        const visited = new Uint8Array(W * H);
        const canFill = (i) => maskEmpty[i] === 1;
        pushMapUndoEntry({
            type: 'fill',
            imageData: new ImageData(new Uint8ClampedArray(fillData.data), W, H),
        });
        visited[idx] = 1;
        queue[tail++] = idx;
        while (head < tail) {
            const i = queue[head++];
            const o = i * 4;
            fillData.data[o] = rgba.r;
            fillData.data[o + 1] = rgba.g;
            fillData.data[o + 2] = rgba.b;
            fillData.data[o + 3] = rgba.a;
            const x = i % W;
            const y = (i / W) | 0;
            const neighbors = [
                x > 0 ? i - 1 : -1,
                x < W - 1 ? i + 1 : -1,
                y > 0 ? i - W : -1,
                y < H - 1 ? i + W : -1,
            ];
            for (const ni of neighbors) {
                if (ni < 0 || visited[ni] || !canFill(ni)) {
                    continue;
                }
                visited[ni] = 1;
                queue[tail++] = ni;
            }
        }
        if (mapState.fillColorKey === MAP_WATER_FILL_KEY && mapState.waterEdgeDecorate) {
            applyWaterFoamAlongFilledRegion(fillData, W, H, visited, rgba);
        }
        fctx.putImageData(fillData, 0, 0);
        fillImageNode.image(fillCanvas);
        mapState.fillNeedsSync = true;
        batchDrawMapLayers();
        mapFillDebugLog('runMapFillAt local flood done', {
            worldX,
            worldY,
            filledCells: tail,
            colorKey: mapState.fillColorKey,
        });
        const ok = await persistMapFillLayerOnly();
        mapFillDebugLog('runMapFillAt after persistMapFillLayerOnly', { ok });
    }

    /**
     * Снимает заливку в той же связной области, что и заливка ЛКМ (маска линий и рамки листа).
     * После снятия сразу сохраняет PNG на сервер (await).
     *
     * @param {number} worldX
     * @param {number} worldY
     * @returns {Promise<void>}
     */
    async function runMapFillEraseAt(worldX, worldY) {
        const ix = Math.floor(worldX);
        const iy = Math.floor(worldY);
        if (ix < 0 || ix >= mapState.mapWidthPx || iy < 0 || iy >= mapState.mapHeightPx) {
            return;
        }
        const maskImg = rasterizeLandscapeStrokesToMask(landscapeDrawGroup);
        if (!maskImg) {
            return;
        }
        const maskEmpty = imageDataToEmptyMask(maskImg);
        const W = mapState.mapWidthPx;
        const H = mapState.mapHeightPx;
        const idx = iy * W + ix;
        if (maskEmpty[idx] === 0) {
            mapState.flashPlacementHint?.('Клик по линии. Выберите область рядом со штрихом.');

            return;
        }
        const fillCanvas = mapState.fillCanvas;
        if (!fillCanvas) {
            return;
        }
        const fctx = fillCanvas.getContext('2d');
        if (!fctx) {
            return;
        }
        const fillData = fctx.getImageData(0, 0, W, H);
        const queue = new Int32Array(W * H);
        let head = 0;
        let tail = 0;
        const visited = new Uint8Array(W * H);
        const canFill = (i) => maskEmpty[i] === 1;
        pushMapUndoEntry({
            type: 'fill',
            imageData: new ImageData(new Uint8ClampedArray(fillData.data), W, H),
        });
        visited[idx] = 1;
        queue[tail++] = idx;
        while (head < tail) {
            const i = queue[head++];
            const o = i * 4;
            fillData.data[o] = 0;
            fillData.data[o + 1] = 0;
            fillData.data[o + 2] = 0;
            fillData.data[o + 3] = 0;
            const x = i % W;
            const y = (i / W) | 0;
            const neighbors = [
                x > 0 ? i - 1 : -1,
                x < W - 1 ? i + 1 : -1,
                y > 0 ? i - W : -1,
                y < H - 1 ? i + W : -1,
            ];
            for (const ni of neighbors) {
                if (ni < 0 || visited[ni] || !canFill(ni)) {
                    continue;
                }
                visited[ni] = 1;
                queue[tail++] = ni;
            }
        }
        fctx.putImageData(fillData, 0, 0);
        fillImageNode.image(fillCanvas);
        mapState.fillNeedsSync = true;
        batchDrawMapLayers();
        mapFillDebugLog('runMapFillEraseAt local done', { worldX, worldY, erasedCells: tail });
        const ok = await persistMapFillLayerOnly();
        mapFillDebugLog('runMapFillEraseAt after persistMapFillLayerOnly', { ok });
    }

    const crosshairGroup = new Konva.Group({ listening: false, name: 'map-crosshair' });
    const guideStroke = 'rgba(120, 118, 110, 0.22)';
    const crosshairV = new Konva.Line({
        points: [0, 0, 0, mapState.mapHeightPx],
        stroke: guideStroke,
        strokeWidth: 1,
        listening: false,
        visible: false,
    });
    const crosshairH = new Konva.Line({
        points: [0, 0, mapState.mapWidthPx, 0],
        stroke: guideStroke,
        strokeWidth: 1,
        listening: false,
        visible: false,
    });
    crosshairGroup.add(crosshairV);
    crosshairGroup.add(crosshairH);
    worldGroup.add(crosshairGroup);
    mapState.crosshairGroup = crosshairGroup;
    mapState.crosshairV = crosshairV;
    mapState.crosshairH = crosshairH;

    layer.add(worldGroup);
    rulerLayer.add(fixedRulersGroup);
    stage.add(layer);
    stage.add(rulerLayer);

    mapState.stage = stage;
    mapState.layer = layer;
    mapState.rulerLayer = rulerLayer;
    mapState.fixedRulersGroup = fixedRulersGroup;
    mapState.worldGroup = worldGroup;

    function batchDrawAll() {
        layer.batchDraw();
        rulerLayer.batchDraw();
    }

    const rulerFill = '#4a4846';
    const cornerFill = '#3a3836';
    const tickStroke = 'rgba(235, 232, 225, 0.55)';
    const labelFill = 'rgba(248, 246, 240, 0.92)';
    const fontFamily = 'Instrument Sans, system-ui, sans-serif';

    /**
     * Линейки закреплены у краёв вьюпорта; деления синхронизированы с панорамированием карты.
     */
    function drawFixedRulers() {
        const g = fixedRulersGroup;
        const wg = worldGroup;
        g.destroyChildren();

        const sw = stage.width();
        const sh = stage.height();
        if (sw <= 0 || sh <= 0) {
            return;
        }

        const ox = wg.x();
        const oy = wg.y();

        g.add(
            new Konva.Rect({
                x: 0,
                y: 0,
                width: RULER_W,
                height: RULER_H,
                fill: cornerFill,
                listening: false,
            }),
        );
        g.add(
            new Konva.Rect({
                x: RULER_W,
                y: 0,
                width: sw - RULER_W,
                height: RULER_H,
                fill: rulerFill,
                listening: false,
            }),
        );
        g.add(
            new Konva.Rect({
                x: 0,
                y: RULER_H,
                width: RULER_W,
                height: sh - RULER_H,
                fill: rulerFill,
                listening: false,
            }),
        );
        g.add(
            new Konva.Line({
                points: [RULER_W, RULER_H, sw, RULER_H],
                stroke: 'rgba(20, 18, 16, 0.35)',
                strokeWidth: 1,
                listening: false,
            }),
        );
        g.add(
            new Konva.Line({
                points: [RULER_W, RULER_H, RULER_W, sh],
                stroke: 'rgba(20, 18, 16, 0.35)',
                strokeWidth: 1,
                listening: false,
            }),
        );

        for (let wwx = 0; wwx <= mapState.mapWidthPx; wwx += 100) {
            const sx = ox + wwx;
            if (sx < RULER_W || sx > sw) {
                continue;
            }
            const tickH = wwx % 500 === 0 ? 5 : 3;
            g.add(
                new Konva.Line({
                    points: [sx, RULER_H, sx, RULER_H - tickH],
                    stroke: tickStroke,
                    strokeWidth: 1,
                    listening: false,
                }),
            );
            if (wwx > 0 && wwx % 500 === 0) {
                g.add(
                    new Konva.Text({
                        x: sx - 11,
                        y: 1,
                        width: 22,
                        text: String(wwx),
                        fontSize: 7,
                        fontFamily,
                        fill: labelFill,
                        listening: false,
                    }),
                );
            }
        }

        for (let wwy = 0; wwy <= mapState.mapHeightPx; wwy += 100) {
            const sy = oy + wwy;
            if (sy < RULER_H || sy > sh) {
                continue;
            }
            const tickW = wwy % 500 === 0 ? 5 : 3;
            g.add(
                new Konva.Line({
                    points: [RULER_W, sy, RULER_W - tickW, sy],
                    stroke: tickStroke,
                    strokeWidth: 1,
                    listening: false,
                }),
            );
            if (wwy > 0 && wwy % 500 === 0) {
                const label = String(wwy);
                const fs = 7;
                const approxW = label.length * fs * 0.55;
                const approxH = fs;
                g.add(
                    new Konva.Text({
                        x: RULER_W / 2,
                        y: sy,
                        text: label,
                        fontSize: fs,
                        fontFamily,
                        fill: labelFill,
                        listening: false,
                        align: 'center',
                        verticalAlign: 'middle',
                        rotation: -90,
                        offsetX: approxW / 2,
                        offsetY: approxH / 2,
                    }),
                );
            }
        }
    }

    function clampWorldGroupPosition() {
        const sw = stage.width();
        const sh = stage.height();
        let x = worldGroup.x();
        let y = worldGroup.y();
        if (mapState.mapWidthPx >= sw) {
            x = Math.min(0, Math.max(sw - mapState.mapWidthPx, x));
        } else {
            x = (sw - mapState.mapWidthPx) / 2;
        }
        if (mapState.mapHeightPx >= sh) {
            y = Math.min(0, Math.max(sh - mapState.mapHeightPx, y));
        } else {
            y = (sh - mapState.mapHeightPx) / 2;
        }
        worldGroup.position({ x, y });
        drawFixedRulers();
        batchDrawAll();
    }

    /** Стартовый вид: карта по центру родителя (вьюпорта сцены). */
    function applyInitialWorldPosition() {
        const sw = stage.width();
        const sh = stage.height();
        worldGroup.position({
            x: (sw - mapState.mapWidthPx) / 2,
            y: (sh - mapState.mapHeightPx) / 2,
        });
        clampWorldGroupPosition();
    }
    applyInitialWorldPosition();

    let isPanning = false;
    let lastPan = { x: 0, y: 0 };
    /** Левая кнопка: кандидат в панораму (как на таймлайне), без старта при клике по объекту. */
    let leftPanCandidate = false;
    let leftPanStart = { x: 0, y: 0 };
    /** После перетаскивания холста подавляем следующий click (чтобы не ставить спрайт). */
    let suppressNextMapClick = false;
    /** @type {ReturnType<typeof setTimeout> | null} */
    let suppressClearTimer = null;
    const LEFT_PAN_THRESHOLD_PX = 4;
    let isLandscapeDrawing = false;
    /** Левая кнопка в режиме «Стереть»: перетаскивание по карте. */
    let isErasing = false;
    /** @type {import('konva/lib/shapes/Line').default | null} */
    let currentLandscapeLine = null;

    /**
     * Прерывает незавершённый штрих (смена режима или закрытие панели).
     *
     * @returns {void}
     */
    function abortLandscapeDrawInProgress() {
        if (isLandscapeDrawing && currentLandscapeLine) {
            currentLandscapeLine.destroy();
            currentLandscapeLine = null;
            isLandscapeDrawing = false;
            batchDrawMapLayers();
        }
    }

    mapState.abortLandscapeDraw = abortLandscapeDrawInProgress;

    /**
     * Завершает штрих ландшафта при mouseup.
     *
     * @returns {void}
     */
    function finishLandscapeStroke() {
        if (!isLandscapeDrawing || !currentLandscapeLine) {
            return;
        }
        const line = currentLandscapeLine;
        const pts = line.points();
        isLandscapeDrawing = false;
        currentLandscapeLine = null;
        if (pts.length < 4) {
            line.destroy();
            batchDrawMapLayers();
        } else {
            if (mapState.drawMode === 'landscape' || mapState.drawMode === 'borders') {
                snapLandscapeStrokeEndToNearbyLines(line, landscapeDrawGroup);
            }
            pushMapUndoEntry({ type: 'stroke', line });
            suppressNextMapClick = true;
            batchDrawMapLayers();
            schedulePersistMapCanvas();
        }
    }

    function updateCrosshairFromPointer() {
        const pos = stage.getPointerPosition();
        const wg = mapState.worldGroup;
        const v = mapState.crosshairV;
        const h = mapState.crosshairH;
        if (!pos || !wg || !v || !h) {
            return;
        }
        if (mapState.drawMode === 'landscape' ||
        mapState.drawMode === 'borders' ||
        mapState.drawMode === 'fill' ||
        mapState.drawMode === 'erase') {
            v.visible(false);
            h.visible(false);

            return;
        }
        if (pos.x < RULER_W || pos.y < RULER_H) {
            v.visible(false);
            h.visible(false);

            return;
        }
        const rel = wg.getRelativePointerPosition();
        if (
            !rel ||
            rel.x < 0 ||
            rel.x > mapState.mapWidthPx ||
            rel.y < 0 ||
            rel.y > mapState.mapHeightPx ||
            pos.x < 0 ||
            pos.x > stage.width() ||
            pos.y < 0 ||
            pos.y > stage.height()
        ) {
            v.visible(false);
            h.visible(false);

            return;
        }
        const wx = rel.x;
        const wy = rel.y;
        const hit = stage.getIntersection(pos);
        const onSprite = findMapObjectGroup(hit instanceof Konva.Node ? hit : null) !== null;
        if (onSprite) {
            v.visible(false);
            h.visible(false);

            return;
        }
        v.points([wx, 0, wx, mapState.mapHeightPx]);
        h.points([0, wy, mapState.mapWidthPx, wy]);
        v.visible(true);
        h.visible(true);
    }

    function setMapCursor() {
        if (!mapState.container) {
            return;
        }
        if (mapState.draggingSprite) {
            applyMapCursor('grabbing');

            return;
        }
        if (mapState.drawMode === 'landscape' || mapState.drawMode === 'borders') {
            applyMapCursor(isPanning ? 'grabbing' : MAP_PENCIL_CURSOR);

            return;
        }
        if (mapState.drawMode === 'fill') {
            applyMapCursor(isPanning ? 'grabbing' : MAP_FILL_CURSOR);

            return;
        }
        if (mapState.drawMode === 'erase') {
            applyMapCursor(isPanning ? 'grabbing' : getMapEraserCursorCss());

            return;
        }
        const pos = stage.getPointerPosition();
        if (!pos) {
            applyMapCursor('');

            return;
        }
        if (pos.x < RULER_W || pos.y < RULER_H) {
            applyMapCursor('default');

            return;
        }
        if (isPanning) {
            applyMapCursor('grabbing');

            return;
        }
        const hit = stage.getIntersection(pos);
        const onSprite = findMapObjectGroup(hit instanceof Konva.Node ? hit : null) !== null;
        if (onSprite) {
            applyMapCursor('grab');
        } else if (mapState.selectedSpriteUrl) {
            applyMapCursor('crosshair');
        } else {
            applyMapCursor('grab');
        }
    }

    stage.on('mousedown', (e) => {
        if (e.evt.button === 1) {
            isPanning = true;
            leftPanCandidate = false;
            const p = stage.getPointerPosition();
            if (p) {
                lastPan = { x: p.x, y: p.y };
            }
            e.evt.preventDefault();
            setMapCursor();

            return;
        }
        if (e.evt.button !== 0) {
            return;
        }
        const pos = stage.getPointerPosition();
        if (!pos || pos.x < RULER_W || pos.y < RULER_H) {
            return;
        }
        const hit = stage.getIntersection(pos);
        const onSprite = findMapObjectGroup(hit instanceof Konva.Node ? hit : null) !== null;
        if (mapState.drawMode === 'landscape' || mapState.drawMode === 'borders') {
            if (onSprite) {
                return;
            }
            const rel = worldGroup.getRelativePointerPosition();
            if (!rel || rel.x < 0 || rel.x > mapState.mapWidthPx || rel.y < 0 || rel.y > mapState.mapHeightPx) {
                return;
            }
            isLandscapeDrawing = true;
            leftPanCandidate = false;
            const isBorderStroke = mapState.drawMode === 'borders';
            const strokeW = isBorderStroke ? mapState.bordersLineWidth : mapState.landscapeLineWidth;
            const strokeCss = isBorderStroke
                ? mapLineStrokeCss(mapState.bordersStrokeKey)
                : mapLineStrokeCss(mapState.landscapeStrokeKey);
            currentLandscapeLine = new Konva.Line({
                points: [rel.x, rel.y],
                stroke: strokeCss,
                strokeWidth: clampMapLineWidth(strokeW),
                lineCap: 'round',
                lineJoin: 'round',
                listening: false,
                perfectDrawEnabled: false,
                ...(isBorderStroke ? { dash: borderLineDashForStrokeWidth(strokeW) } : {}),
            });
            landscapeDrawGroup.add(currentLandscapeLine);
            batchDrawMapLayers();
            setMapCursor();

            return;
        }
        if (mapState.drawMode === 'fill') {
            return;
        }
        if (mapState.drawMode === 'erase') {
            if (onSprite) {
                return;
            }
            const relErase = worldGroup.getRelativePointerPosition();
            if (
                !relErase ||
                relErase.x < 0 ||
                relErase.x > mapState.mapWidthPx ||
                relErase.y < 0 ||
                relErase.y > mapState.mapHeightPx
            ) {
                return;
            }
            isErasing = true;
            leftPanCandidate = false;
            const er = clampEraseRadius(mapState.eraseRadius);
            eraseFillDiskAt(relErase.x, relErase.y, er);
            eraseLinesNearWorld(relErase.x, relErase.y, er);
            suppressNextMapClick = true;
            setMapCursor();

            return;
        }
        if (onSprite) {
            return;
        }
        leftPanCandidate = true;
        leftPanStart = { x: pos.x, y: pos.y };
        lastPan = { x: pos.x, y: pos.y };
    });

    function endMapPanGesture() {
        if (isPanning) {
            isPanning = false;
            setMapCursor();
        }
        leftPanCandidate = false;
    }

    document.addEventListener('mouseup', () => {
        finishLandscapeStroke();
        if (isErasing) {
            schedulePersistMapCanvas();
        }
        isErasing = false;
        endMapPanGesture();
        if (suppressNextMapClick) {
            if (suppressClearTimer !== null) {
                window.clearTimeout(suppressClearTimer);
            }
            suppressClearTimer = window.setTimeout(() => {
                suppressNextMapClick = false;
                suppressClearTimer = null;
            }, 400);
        }
    });

    stage.on('mousemove', () => {
        const pos = stage.getPointerPosition();
        if (
            isLandscapeDrawing &&
            currentLandscapeLine &&
            (mapState.drawMode === 'landscape' || mapState.drawMode === 'borders')
        ) {
            const rel = worldGroup.getRelativePointerPosition();
            if (rel && rel.x >= 0 && rel.x <= mapState.mapWidthPx && rel.y >= 0 && rel.y <= mapState.mapHeightPx) {
                const pts = currentLandscapeLine.points().slice();
                const lastX = pts.length >= 2 ? pts[pts.length - 2] : null;
                const lastY = pts.length >= 2 ? pts[pts.length - 1] : null;
                if (lastX === null || Math.hypot(rel.x - lastX, rel.y - lastY) >= 0.5) {
                    currentLandscapeLine.points(pts.concat([rel.x, rel.y]));
                    batchDrawMapLayers();
                    suppressNextMapClick = true;
                }
            }
            updateCrosshairFromPointer();
            setMapCursor();

            return;
        }
        if (isErasing && mapState.drawMode === 'erase') {
            const relErase = worldGroup.getRelativePointerPosition();
            if (relErase && relErase.x >= 0 && relErase.x <= mapState.mapWidthPx && relErase.y >= 0 && relErase.y <= mapState.mapHeightPx) {
                const er = clampEraseRadius(mapState.eraseRadius);
                eraseFillDiskAt(relErase.x, relErase.y, er);
                eraseLinesNearWorld(relErase.x, relErase.y, er);
                suppressNextMapClick = true;
            }
            updateCrosshairFromPointer();
            setMapCursor();

            return;
        }
        if (leftPanCandidate && !isPanning && pos) {
            const d = Math.hypot(pos.x - leftPanStart.x, pos.y - leftPanStart.y);
            if (d > LEFT_PAN_THRESHOLD_PX) {
                isPanning = true;
                suppressNextMapClick = true;
            }
        }
        if (isPanning && pos) {
            const dx = pos.x - lastPan.x;
            const dy = pos.y - lastPan.y;
            worldGroup.x(worldGroup.x() + dx);
            worldGroup.y(worldGroup.y() + dy);
            lastPan = { x: pos.x, y: pos.y };
            clampWorldGroupPosition();
        }
        updateCrosshairFromPointer();
        setMapCursor();
    });

    stage.on('mouseleave', () => {
        mapState.crosshairV?.visible(false);
        mapState.crosshairH?.visible(false);
        if (!mapState.draggingSprite) {
            applyMapCursor('');
        }
        batchDrawMapLayers();
    });

    stage.on('contextmenu', (e) => {
        e.evt.preventDefault();
        if (mapState.drawMode === 'fill') {
            const pos = getWorldPointForMapFillAction();
            if (!pos) {
                return;
            }
            void (async () => {
                await runMapFillEraseAt(pos.x, pos.y);
            })();

            return;
        }
        if (mapState.drawMode === 'landscape' ||
        mapState.drawMode === 'borders' ||
        mapState.drawMode === 'erase') {
            return;
        }
        const pos = stage.getPointerPosition();
        if (!pos) {
            return;
        }
        const hit = stage.getIntersection(pos);
        const g = findMapObjectGroup(hit instanceof Konva.Node ? hit : null);
        if (g) {
            const id = g.getAttr('mapSpriteId');
            void (async () => {
                if (id == null) {
                    g.destroy();
                    batchDrawMapLayers();

                    return;
                }
                const ok = await persistSpriteDelete(Number(id));
                if (ok) {
                    g.destroy();
                    batchDrawMapLayers();
                }
            })();

            return;
        }
    });

    stage.on('click', async (e) => {
        const clickBtn = typeof e.evt.button === 'number' ? e.evt.button : 0;
        if (clickBtn !== 0) {
            return;
        }
        if (mapState.drawMode === 'erase') {
            if (suppressNextMapClick) {
                suppressNextMapClick = false;
                if (suppressClearTimer !== null) {
                    window.clearTimeout(suppressClearTimer);
                    suppressClearTimer = null;
                }

                return;
            }

            return;
        }
        if (mapState.drawMode === 'fill') {
            if (suppressNextMapClick) {
                suppressNextMapClick = false;
                if (suppressClearTimer !== null) {
                    window.clearTimeout(suppressClearTimer);
                    suppressClearTimer = null;
                }

                return;
            }
            const pos = getWorldPointForMapFillAction();
            if (!pos) {
                return;
            }
            await runMapFillAt(pos.x, pos.y);

            return;
        }
        if (mapState.drawMode === 'landscape' || mapState.drawMode === 'borders') {
            return;
        }
        if (suppressNextMapClick) {
            suppressNextMapClick = false;
            if (suppressClearTimer !== null) {
                window.clearTimeout(suppressClearTimer);
                suppressClearTimer = null;
            }

            return;
        }
        if (e.target !== bg) {
            return;
        }
        if (!mapState.selectedSpriteUrl) {
            return;
        }
        const sp = stage.getPointerPosition();
        if (!sp || sp.x < RULER_W || sp.y < RULER_H) {
            return;
        }
        const pos = worldGroup.getRelativePointerPosition();
        if (!pos) {
            return;
        }
        if (pos.x < 0 || pos.x > mapState.mapWidthPx || pos.y < 0 || pos.y > mapState.mapHeightPx) {
            return;
        }
        placeSpriteAt(pos.x, pos.y);
    });

    const ro = new ResizeObserver(() => {
        stage.width(container.clientWidth);
        stage.height(container.clientHeight);
        clampWorldGroupPosition();
    });
    ro.observe(container);

    mapState.refreshMapCursor = setMapCursor;

    applyMapObjectInteractionForDrawMode();

    /**
     * Ctrl+Z / Cmd+Z — отмена последнего штриха или заливки (до 10 шагов).
     *
     * @param {KeyboardEvent} e
     * @returns {void}
     */
    function onMapUndoKeydown(e) {
        if (!e.ctrlKey && !e.metaKey) {
            return;
        }
        if (e.code !== 'KeyZ') {
            return;
        }
        if (e.shiftKey) {
            return;
        }
        const el = e.target;
        if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement || el instanceof HTMLSelectElement) {
            return;
        }
        if (el instanceof HTMLElement && el.isContentEditable) {
            return;
        }
        e.preventDefault();
        undoLastMapAction();
    }
    document.addEventListener('keydown', onMapUndoKeydown);

    const mapUndoBtn = document.getElementById('map-undo-last-action');
    mapUndoBtn?.addEventListener('click', () => {
        undoLastMapAction();
    });
    syncMapUndoUi();

    /**
     * Восстанавливает сохранённые линии и слой заливки до загрузки спрайтов.
     *
     * @returns {Promise<void>}
     */
    async function restoreMapCanvasState() {
        const lines = canvasOpts.mapDrawingLines;
        if (Array.isArray(lines) && lines.length > 0) {
            const g = mapState.landscapeDrawGroup;
            if (g) {
                for (let li = 0; li < lines.length; li++) {
                    const item = lines[li];
                    if (!item || !Array.isArray(item.points) || item.points.length < 4) {
                        continue;
                    }
                    const sw =
                        item.strokeWidth != null && item.strokeWidth !== ''
                            ? clampMapLineWidth(Number(item.strokeWidth))
                            : DEFAULT_MAP_LINE_WIDTH;
                    const stroke =
                        typeof item.stroke === 'string' && item.stroke !== ''
                            ? item.stroke
                            : mapLineStrokeCss('black');
                    let dashFromSave = null;
                    if (Array.isArray(item.dash) && item.dash.length) {
                        const raw = item.dash.map((x) => Number(x));
                        if (
                            raw.length === 2 &&
                            Math.abs(raw[0] - 5) < 0.01 &&
                            Math.abs(raw[1] - 5) < 0.01 &&
                            sw > DEFAULT_MAP_LINE_WIDTH + 0.5
                        ) {
                            dashFromSave = borderLineDashForStrokeWidth(sw);
                        } else {
                            dashFromSave = raw;
                        }
                    }
                    const line = new Konva.Line({
                        points: item.points.map((v) => Number(v)),
                        stroke,
                        strokeWidth: sw,
                        lineCap: 'round',
                        lineJoin: 'round',
                        listening: false,
                        perfectDrawEnabled: false,
                        ...(dashFromSave ? { dash: dashFromSave } : {}),
                    });
                    g.add(line);
                }
            }
        }
        const fillUrl = canvasOpts.mapFillUrl;
        mapFillDebugLog('restoreMapCanvasState fillUrl', {
            fillUrl: typeof fillUrl === 'string' ? fillUrl.slice(0, 120) : fillUrl,
            fillNeedsSyncBeforeLoad: mapState.fillNeedsSync,
        });
        if (typeof fillUrl === 'string' && fillUrl !== '' && mapState.fillCanvas && mapState.fillImageNode) {
            try {
                const img = await loadImage(fillUrl);
                const ctx = mapState.fillCanvas.getContext('2d');
                // Пока шёл await, пользователь мог уже залить область — не перезатирать локальный слой
                // и не сбрасывать fillNeedsSync (иначе отложенный POST заливки не уйдёт).
                if (ctx && !mapState.fillNeedsSync) {
                    ctx.clearRect(0, 0, mapState.mapWidthPx, mapState.mapHeightPx);
                    ctx.drawImage(img, 0, 0, mapState.mapWidthPx, mapState.mapHeightPx);
                    mapState.fillImageNode.image(mapState.fillCanvas);
                    mapFillDebugLog('restoreMapCanvasState fill image drawn from server', {
                        imgNaturalW: img.naturalWidth,
                        imgNaturalH: img.naturalHeight,
                    });
                } else {
                    mapFillDebugLog('restoreMapCanvasState fill draw skipped', {
                        hasCtx: !!ctx,
                        fillNeedsSync: mapState.fillNeedsSync,
                    });
                }
            } catch (err) {
                mapFillDebugLog('restoreMapCanvasState loadImage(fill) failed', err, fillUrl?.slice(0, 200));
            }
        } else {
            mapFillDebugLog('restoreMapCanvasState no fill URL or no canvas', {
                hasUrl: !!(typeof fillUrl === 'string' && fillUrl !== ''),
            });
        }
        mapUndoStack.length = 0;
        batchDrawMapLayers();
    }

    /**
     * Загрузка спрайтов из БД после инициализации сцены.
     *
     * @returns {Promise<void>}
     */
    async function hydrateInitialPlacements() {
        await restoreMapCanvasState();
        const list = mapState.initialPlacements;
        const wg = mapState.worldGroup;
        const base = mapState.spriteBase;
        if (!wg) {
            applyMapObjectInteractionForDrawMode();

            return;
        }
        if (list?.length) {
            for (const row of list) {
                const parts = splitSpritePath(row.sprite_path);
                if (!parts) {
                    continue;
                }
                const url = spriteUrl(base, parts.folder, parts.file);
                try {
                    const img = await loadImage(url);
                    const kGroup = createMapObjectGroup(
                        img,
                        row.pos_x,
                        row.pos_y,
                        row.id,
                        row.title,
                        row.description,
                    );
                    wg.add(kGroup);
                    kGroup.moveToTop();
                } catch {
                    // пропускаем битые ссылки
                }
            }
            batchDrawMapLayers();
        }
        applyMapObjectInteractionForDrawMode();
    }

    void hydrateInitialPlacements();
}

/**
 * @param {{ spriteBaseUrl: string, mapSprites?: Array<{ id: number, sprite_path: string, pos_x: number, pos_y: number }>, mapsSpriteStoreUrl?: string, mapsSpriteUpdateUrlPattern?: string, mapsCanvasSaveUrl?: string | null, mapsFillUploadUrl?: string | null, mapDrawingLines?: Array<{ points: number[], stroke: string, dash: number[] | null }>, mapFillUrl?: string | null, mapWidth?: number, mapHeight?: number, worldSetting?: string, mapObjectLabelFontFamily?: string }} opts
 */
export function initMapsPage(opts) {
    mapFillDebugLog('initMapsPage', {
        mapsFillUploadUrl: opts.mapsFillUploadUrl || null,
        mapsCanvasSaveUrl: opts.mapsCanvasSaveUrl || null,
        mapFillUrl: opts.mapFillUrl ? String(opts.mapFillUrl).slice(0, 160) : null,
        mapWidth: opts.mapWidth,
        mapHeight: opts.mapHeight,
    });
    mapState.spriteBase = opts.spriteBaseUrl || '/sprites';
    mapState.initialPlacements = opts.mapSprites ?? [];
    mapState.storeUrl = opts.mapsSpriteStoreUrl ?? '';
    mapState.updateUrlPattern = opts.mapsSpriteUpdateUrlPattern ?? '';
    mapState.worldSetting = /** @type {'fantasy'|'science_fiction'|'modern'} */ (
        opts.worldSetting === 'science_fiction' || opts.worldSetting === 'modern' || opts.worldSetting === 'fantasy'
            ? opts.worldSetting
            : 'fantasy'
    );
    mapState.mapLabelFontFamily = opts.mapObjectLabelFontFamily ?? 'Cormorant Garamond';
    const mount = document.getElementById('map-stage-mount');
    if (!mount) {
        return;
    }

    initKonva(mount, {
        mapDrawingLines: opts.mapDrawingLines ?? [],
        mapFillUrl: opts.mapFillUrl || null,
        mapsCanvasSaveUrl: opts.mapsCanvasSaveUrl || null,
        mapsFillUploadUrl: opts.mapsFillUploadUrl || null,
        mapWidth: opts.mapWidth,
        mapHeight: opts.mapHeight,
    });

    window.addEventListener('pagehide', () => {
        syncPersistMapFillOnUnloadIfNeeded();
        flushMapCanvasPersist();
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            flushMapCanvasPersist();
        }
    });

    const typeSelect = document.getElementById('map-type-select');
    const spriteGrid = document.getElementById('map-sprite-grid');
    const catButtons = document.querySelectorAll('[data-map-category]');
    const toolButtons = document.querySelectorAll('[data-map-tool]');
    const hintEl = document.getElementById('map-placement-hint');
    const panel = document.getElementById('map-sidebar-panel');
    const landscapePanel = document.getElementById('map-panel-tool-landscape');
    const labelsPanel = document.getElementById('map-panel-tool-labels');
    const spritesPanel = document.getElementById('map-panel-sprites');
    const drawLandscapeBtn = document.getElementById('map-draw-landscape');
    const drawBordersBtn = document.getElementById('map-draw-borders');
    const drawEraseBtn = document.getElementById('map-draw-erase');
    const drawFillBtn = document.getElementById('map-draw-fill');
    const fillPaletteEl = document.getElementById('map-fill-palette');
    const waterEdgeWrap = document.getElementById('map-water-edge-wrap');
    const waterEdgeCheckbox = document.getElementById('map-water-edge-decorate');
    const SS_WATER_EDGE = 'noema_map_water_edge_decoration';
    try {
        const saved = sessionStorage.getItem(SS_WATER_EDGE);
        if (saved === '0') {
            mapState.waterEdgeDecorate = false;
        } else if (saved === '1') {
            mapState.waterEdgeDecorate = true;
        }
        // Иначе ключа нет или старое значение — по умолчанию включаем прибрежную полосу.
    } catch {
        mapState.waterEdgeDecorate = true;
    }
    const eraseSettingsEl = document.getElementById('map-erase-settings');
    const eraseRadiusRange = document.getElementById('map-erase-radius');
    const eraseRadiusVal = document.getElementById('map-erase-radius-val');
    const strokeSettingsEl = document.getElementById('map-stroke-settings');
    const strokeLandscapeGroupEl = document.getElementById('map-stroke-landscape-group');
    const strokeBordersGroupEl = document.getElementById('map-stroke-borders-group');
    const landscapeWidthRange = document.getElementById('map-stroke-landscape-width');
    const landscapeWidthVal = document.getElementById('map-stroke-landscape-width-val');
    const bordersWidthRange = document.getElementById('map-stroke-borders-width');
    const bordersWidthVal = document.getElementById('map-stroke-borders-width-val');

    /**
     * Синхронизирует ползунки и свотчи толщины/цвета линий с mapState.
     *
     * @returns {void}
     */
    function syncStrokeSettingsUi() {
        if (landscapeWidthRange) {
            landscapeWidthRange.value = String(mapState.landscapeLineWidth);
        }
        if (landscapeWidthVal) {
            landscapeWidthVal.textContent = String(mapState.landscapeLineWidth);
        }
        if (bordersWidthRange) {
            bordersWidthRange.value = String(mapState.bordersLineWidth);
        }
        if (bordersWidthVal) {
            bordersWidthVal.textContent = String(mapState.bordersLineWidth);
        }
        document.querySelectorAll('.map-landscape-stroke-swatch[data-map-landscape-stroke]').forEach((btn) => {
            const k = btn.getAttribute('data-map-landscape-stroke');
            const on = k === mapState.landscapeStrokeKey;
            btn.classList.toggle('ring-2', on);
            btn.classList.toggle('ring-primary', on);
            btn.classList.toggle('ring-offset-1', on);
            btn.classList.toggle('ring-offset-base-200', on);
        });
        document.querySelectorAll('.map-borders-stroke-swatch[data-map-borders-stroke]').forEach((btn) => {
            const k = btn.getAttribute('data-map-borders-stroke');
            const on = k === mapState.bordersStrokeKey;
            btn.classList.toggle('ring-2', on);
            btn.classList.toggle('ring-primary', on);
            btn.classList.toggle('ring-offset-1', on);
            btn.classList.toggle('ring-offset-base-200', on);
        });
    }

    /** @type {typeof MAP_SPRITE_CATALOG[0] | null} */
    let activeCategory = null;
    /** @type {string | null} */
    let openCategoryId = null;
    /** @type {'landscape' | 'labels' | null} */
    let openToolId = null;

    function highlightToolButtons(toolId) {
        toolButtons.forEach((b) => {
            const id = b.getAttribute('data-map-tool');
            const on = id === toolId;
            b.classList.toggle('bg-primary/20', on);
            b.classList.toggle('ring-1', on);
            b.classList.toggle('ring-primary/50', on);
        });
    }

    /**
     * Подсветка выбранного под-инструмента в панели «Ландшафт».
     *
     * @returns {void}
     */
    function syncDrawToolUi() {
        drawLandscapeBtn?.classList.toggle('bg-primary/20', mapState.drawMode === 'landscape');
        drawLandscapeBtn?.classList.toggle('ring-1', mapState.drawMode === 'landscape');
        drawLandscapeBtn?.classList.toggle('ring-primary/50', mapState.drawMode === 'landscape');
        drawBordersBtn?.classList.toggle('bg-primary/20', mapState.drawMode === 'borders');
        drawBordersBtn?.classList.toggle('ring-1', mapState.drawMode === 'borders');
        drawBordersBtn?.classList.toggle('ring-primary/50', mapState.drawMode === 'borders');
        drawFillBtn?.classList.toggle('bg-primary/20', mapState.drawMode === 'fill');
        drawFillBtn?.classList.toggle('ring-1', mapState.drawMode === 'fill');
        drawFillBtn?.classList.toggle('ring-primary/50', mapState.drawMode === 'fill');
        drawEraseBtn?.classList.toggle('bg-primary/20', mapState.drawMode === 'erase');
        drawEraseBtn?.classList.toggle('ring-1', mapState.drawMode === 'erase');
        drawEraseBtn?.classList.toggle('ring-primary/50', mapState.drawMode === 'erase');
        applyMapObjectInteractionForDrawMode();
        syncStrokeSettingsUi();
        syncEraseSettingsUi();
        syncStrokeToolPanelsVisibility();
        syncFillPaletteVisibility();
        syncEraseSettingsVisibility();
    }

    /**
     * Блоки «Ландшафт» / «Границы» в панели — только при активном соответствующем под-инструменте.
     *
     * @returns {void}
     */
    function syncStrokeToolPanelsVisibility() {
        const land = mapState.drawMode === 'landscape';
        const bor = mapState.drawMode === 'borders';
        strokeLandscapeGroupEl?.classList.toggle('hidden', !land);
        strokeBordersGroupEl?.classList.toggle('hidden', !bor);
        strokeSettingsEl?.classList.toggle('hidden', !land && !bor);
    }

    /**
     * Палитра заливки видна только в режиме «Заливка».
     *
     * @returns {void}
     */
    function syncFillPaletteVisibility() {
        fillPaletteEl?.classList.toggle('hidden', mapState.drawMode !== 'fill');
        syncWaterEdgeDecorationUi();
    }

    /**
     * Чекбокс «Украсить край» — только в режиме заливки и при выбранном цвете «Вода».
     *
     * @returns {void}
     */
    function syncWaterEdgeDecorationUi() {
        const show =
            mapState.drawMode === 'fill' && mapState.fillColorKey === MAP_WATER_FILL_KEY;
        waterEdgeWrap?.classList.toggle('hidden', !show);
        if (waterEdgeCheckbox) {
            waterEdgeCheckbox.checked = mapState.waterEdgeDecorate;
        }
    }

    /**
     * Ползунок размера ластика — только в режиме «Стереть».
     *
     * @returns {void}
     */
    function syncEraseSettingsVisibility() {
        eraseSettingsEl?.classList.toggle('hidden', mapState.drawMode !== 'erase');
    }

    /**
     * Синхронизирует ползунок радиуса ластика с mapState.
     *
     * @returns {void}
     */
    function syncEraseSettingsUi() {
        const r = clampEraseRadius(mapState.eraseRadius);
        if (eraseRadiusRange) {
            eraseRadiusRange.value = String(r);
        }
        if (eraseRadiusVal) {
            eraseRadiusVal.textContent = String(r);
        }
    }

    /**
     * Подсветка выбранного пастельного цвета.
     *
     * @returns {void}
     */
    function syncFillColorSwatches() {
        document.querySelectorAll('[data-fill-color]').forEach((btn) => {
            const k = btn.getAttribute('data-fill-color');
            const on = k === mapState.fillColorKey;
            btn.classList.toggle('ring-2', on);
            btn.classList.toggle('ring-primary', on);
            btn.classList.toggle('ring-offset-1', on);
            btn.classList.toggle('ring-offset-base-200', on);
        });
        syncWaterEdgeDecorationUi();
    }

    function setHint() {
        if (hintEl) {
            if (openToolId === 'landscape') {
                if (mapState.drawMode === 'landscape') {
                    hintEl.textContent = 'Левая кнопка — рисовать линию; средняя — сдвиг карты.';

                    return;
                }
                if (mapState.drawMode === 'borders') {
                    hintEl.textContent = 'Левая кнопка — пунктирная линия границы; средняя — сдвиг карты.';

                    return;
                }
                if (mapState.drawMode === 'fill') {
                    if (mapState.fillColorKey === MAP_WATER_FILL_KEY && mapState.waterEdgeDecorate) {
                        hintEl.textContent =
                            'Клик внутри области «Вода»: светлая кромка вдоль берега. Средняя кнопка — сдвиг карты.';

                        return;
                    }
                    hintEl.textContent =
                        'Выберите цвет и кликните внутри контура, ограниченного линиями или краем карты. Средняя кнопка — сдвиг.';

                    return;
                }
                if (mapState.drawMode === 'erase') {
                    hintEl.textContent =
                        'Ластик стирает линии и заливку на карте; объекты не затрагивает. Левая кнопка — стереть, средняя — сдвиг.';

                    return;
                }
                hintEl.textContent = 'Выберите инструмент: карандаш, границы, стереть, заливка.';

                return;
            }
            if (openToolId === 'labels') {
                hintEl.textContent = 'Инструменты подписей появятся в следующих версиях.';

                return;
            }
            if (!openCategoryId) {
                hintEl.textContent = 'Откройте категорию иконкой слева.';

                return;
            }
            hintEl.textContent = mapState.selectedSpriteUrl
                ? 'Кликните по карте, чтобы поставить объект. Средняя кнопка мыши — сдвиг карты.'
                : 'Выберите спрайт в сетке ниже.';
        }
    }

    function openToolPanel(tool) {
        if (tool === 'labels') {
            mapState.abortLandscapeDraw?.();
            mapState.drawMode = null;
            syncDrawToolUi();
            mapState.refreshMapCursor?.();
        }
        openCategoryId = null;
        activeCategory = null;
        mapState.selectedSpriteUrl = null;
        mapState.selectedSpritePath = null;
        if (typeSelect) {
            typeSelect.innerHTML = '';
        }
        if (spriteGrid) {
            spriteGrid.innerHTML = '';
        }
        highlightCategoryButtons('');
        openToolId = tool;
        if (landscapePanel) {
            landscapePanel.classList.toggle('hidden', tool !== 'landscape');
        }
        if (labelsPanel) {
            labelsPanel.classList.toggle('hidden', tool !== 'labels');
        }
        spritesPanel?.classList.add('hidden');
        if (panel) {
            panel.classList.add('map-sidebar-panel--open');
            panel.setAttribute('aria-hidden', 'false');
        }
        highlightToolButtons(tool);
        setHint();
        syncDrawToolUi();
    }

    mapState.flashPlacementHint = (msg) => {
        if (!hintEl) {
            return;
        }
        hintEl.textContent = msg;
        window.setTimeout(() => {
            setHint();
        }, 2800);
    };

    drawLandscapeBtn?.addEventListener('click', () => {
        mapState.abortLandscapeDraw?.();
        if (mapState.drawMode === 'landscape') {
            mapState.drawMode = null;
        } else {
            mapState.drawMode = 'landscape';
        }
        syncDrawToolUi();
        mapState.refreshMapCursor?.();
        setHint();
    });
    drawBordersBtn?.addEventListener('click', () => {
        mapState.abortLandscapeDraw?.();
        if (mapState.drawMode === 'borders') {
            mapState.drawMode = null;
        } else {
            mapState.drawMode = 'borders';
        }
        syncDrawToolUi();
        mapState.refreshMapCursor?.();
        setHint();
    });
    drawEraseBtn?.addEventListener('click', () => {
        mapState.abortLandscapeDraw?.();
        if (mapState.drawMode === 'erase') {
            mapState.drawMode = null;
        } else {
            mapState.drawMode = 'erase';
        }
        syncDrawToolUi();
        mapState.refreshMapCursor?.();
        setHint();
    });
    drawFillBtn?.addEventListener('click', () => {
        mapState.abortLandscapeDraw?.();
        if (mapState.drawMode === 'fill') {
            mapState.drawMode = null;
        } else {
            mapState.drawMode = 'fill';
        }
        syncDrawToolUi();
        mapState.refreshMapCursor?.();
        setHint();
    });

    document.querySelectorAll('[data-fill-color]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const k = btn.getAttribute('data-fill-color');
            if (!k) {
                return;
            }
            mapState.fillColorKey = k;
            syncFillColorSwatches();
            setHint();
        });
    });
    syncFillColorSwatches();

    waterEdgeCheckbox?.addEventListener('change', () => {
        mapState.waterEdgeDecorate = Boolean(waterEdgeCheckbox.checked);
        try {
            sessionStorage.setItem(SS_WATER_EDGE, mapState.waterEdgeDecorate ? '1' : '0');
        } catch {
            //
        }
        setHint();
    });

    landscapeWidthRange?.addEventListener('input', () => {
        mapState.landscapeLineWidth = clampMapLineWidth(Number(landscapeWidthRange.value));
        if (landscapeWidthVal) {
            landscapeWidthVal.textContent = String(mapState.landscapeLineWidth);
        }
    });
    bordersWidthRange?.addEventListener('input', () => {
        mapState.bordersLineWidth = clampMapLineWidth(Number(bordersWidthRange.value));
        if (bordersWidthVal) {
            bordersWidthVal.textContent = String(mapState.bordersLineWidth);
        }
    });
    eraseRadiusRange?.addEventListener('input', () => {
        mapState.eraseRadius = clampEraseRadius(Number(eraseRadiusRange.value));
        if (eraseRadiusVal) {
            eraseRadiusVal.textContent = String(mapState.eraseRadius);
        }
        mapState.refreshMapCursor?.();
    });
    document.querySelectorAll('.map-landscape-stroke-swatch[data-map-landscape-stroke]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const k = btn.getAttribute('data-map-landscape-stroke');
            if (k && Object.prototype.hasOwnProperty.call(MAP_LINE_STROKE_BY_KEY, k)) {
                mapState.landscapeStrokeKey = k;
                syncStrokeSettingsUi();
            }
        });
    });
    document.querySelectorAll('.map-borders-stroke-swatch[data-map-borders-stroke]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const k = btn.getAttribute('data-map-borders-stroke');
            if (k && Object.prototype.hasOwnProperty.call(MAP_LINE_STROKE_BY_KEY, k)) {
                mapState.bordersStrokeKey = k;
                syncStrokeSettingsUi();
            }
        });
    });
    syncStrokeSettingsUi();
    syncEraseSettingsUi();

    function buildTypeOptions() {
        if (!typeSelect || !activeCategory) {
            return;
        }
        typeSelect.innerHTML = '';
        activeCategory.types.forEach((t) => {
            const o = document.createElement('option');
            o.value = t.id;
            o.textContent = t.label;
            typeSelect.appendChild(o);
        });
    }

    function buildSpriteGrid() {
        if (!spriteGrid || !activeCategory || !typeSelect) {
            return;
        }
        const typeId = typeSelect.value;
        const typeDef = activeCategory.types.find((x) => x.id === typeId);
        spriteGrid.innerHTML = '';
        mapState.selectedSpriteUrl = null;
        mapState.selectedSpritePath = null;
        if (!typeDef) {
            setHint();

            return;
        }
        const folder = activeCategory.folder;
        typeDef.files.forEach((file) => {
            const url = spriteUrl(mapState.spriteBase, folder, file);
            const path = `${folder}/${file}`;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className =
                'map-sprite-btn flex items-center justify-center p-2 border border-base-300 bg-base-200 hover:bg-base-300 rounded-none';
            btn.title = file;
            const im = document.createElement('img');
            im.src = url;
            im.alt = '';
            im.className = 'max-w-[48px] max-h-[48px] w-full h-auto object-contain';
            btn.appendChild(im);
            btn.addEventListener('click', () => {
                mapState.selectedSpriteUrl = url;
                mapState.selectedSpritePath = path;
                spriteGrid.querySelectorAll('.map-sprite-btn').forEach((b) => b.classList.remove('ring-2', 'ring-primary'));
                btn.classList.add('ring-2', 'ring-primary');
                setHint();
            });
            spriteGrid.appendChild(btn);
        });
        setHint();
    }

    function highlightCategoryButtons(categoryId) {
        catButtons.forEach((b) => {
            const id = b.getAttribute('data-map-category');
            const on = id === categoryId;
            b.classList.toggle('bg-primary/20', on);
            b.classList.toggle('ring-1', on);
            b.classList.toggle('ring-primary/50', on);
        });
    }

    function openCategoryPanel(categoryId) {
        mapState.abortLandscapeDraw?.();
        mapState.drawMode = null;
        syncDrawToolUi();
        mapState.refreshMapCursor?.();
        openToolId = null;
        highlightToolButtons('');
        landscapePanel?.classList.add('hidden');
        labelsPanel?.classList.add('hidden');
        spritesPanel?.classList.remove('hidden');
        activeCategory = MAP_SPRITE_CATALOG.find((c) => c.id === categoryId) ?? null;
        openCategoryId = categoryId;
        if (panel) {
            panel.classList.add('map-sidebar-panel--open');
            panel.setAttribute('aria-hidden', 'false');
        }
        highlightCategoryButtons(categoryId);
        buildTypeOptions();
        buildSpriteGrid();
    }

    function closeCategoryPanel() {
        mapState.abortLandscapeDraw?.();
        mapState.drawMode = null;
        syncDrawToolUi();
        mapState.refreshMapCursor?.();
        openCategoryId = null;
        openToolId = null;
        activeCategory = null;
        mapState.selectedSpriteUrl = null;
        mapState.selectedSpritePath = null;
        if (panel) {
            panel.classList.remove('map-sidebar-panel--open');
            panel.setAttribute('aria-hidden', 'true');
        }
        highlightCategoryButtons('');
        highlightToolButtons('');
        landscapePanel?.classList.add('hidden');
        labelsPanel?.classList.add('hidden');
        spritesPanel?.classList.remove('hidden');
        if (typeSelect) {
            typeSelect.innerHTML = '';
        }
        if (spriteGrid) {
            spriteGrid.innerHTML = '';
        }
        setHint();
    }

    catButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-map-category');
            if (!id) {
                return;
            }
            if (openCategoryId === id && panel?.classList.contains('map-sidebar-panel--open')) {
                closeCategoryPanel();

                return;
            }
            openCategoryPanel(id);
        });
    });

    toolButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const tool = btn.getAttribute('data-map-tool');
            if (!tool || (tool !== 'landscape' && tool !== 'labels')) {
                return;
            }
            if (openToolId === tool && panel?.classList.contains('map-sidebar-panel--open')) {
                closeCategoryPanel();

                return;
            }
            openToolPanel(tool);
        });
    });

    if (typeSelect) {
        typeSelect.addEventListener('change', buildSpriteGrid);
    }

    const editDialog = document.getElementById('map-object-edit-dialog');
    const titleInput = document.getElementById('map-object-edit-title');
    const descInput = document.getElementById('map-object-edit-description');
    const saveBtn = document.getElementById('map-object-edit-save');
    const cancelBtn = document.getElementById('map-object-edit-cancel');

    mapState.openMapObjectEditDialog = (group) => {
        mapState.pendingEditGroup = group;
        const rawTitle = group.getAttr('mapTitle') || '';
        if (titleInput) {
            titleInput.value = rawTitle;
        }
        if (descInput) {
            descInput.value = group.getAttr('mapDescription') || '';
        }
        editDialog?.showModal();
        requestAnimationFrame(() => {
            titleInput?.focus();
        });
    };

    saveBtn?.addEventListener('click', async () => {
        const g = mapState.pendingEditGroup;
        if (!g || !titleInput || !descInput) {
            return;
        }
        const id = g.getAttr('mapSpriteId');
        const title = titleInput.value.trim();
        const desc = descInput.value.trim();
        const ok = await persistMapObjectText(Number(id), title, desc);
        if (!ok) {
            window.alert('Не удалось сохранить. Попробуйте ещё раз.');

            return;
        }
        g.setAttr('mapTitle', title);
        g.setAttr('mapDescription', desc);
        replaceMapLabelLayer(g, title);
        mapState.pendingEditGroup = null;
        editDialog?.close();
    });

    cancelBtn?.addEventListener('click', () => {
        mapState.pendingEditGroup = null;
        editDialog?.close();
    });

    setHint();
}

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('map-page-meta');
    if (!el?.textContent) {
        return;
    }
    try {
        const meta = JSON.parse(el.textContent);
        initMapsPage({
            spriteBaseUrl: meta.spriteBaseUrl || '/sprites',
            mapSprites: meta.mapSprites,
            mapsSpriteStoreUrl: meta.mapsSpriteStoreUrl,
            mapsSpriteUpdateUrlPattern: meta.mapsSpriteUpdateUrlPattern,
            mapsCanvasSaveUrl: meta.mapsCanvasSaveUrl,
            mapsFillUploadUrl: meta.mapsFillUploadUrl,
            mapDrawingLines: meta.mapDrawingLines,
            mapFillUrl: meta.mapFillUrl,
            mapWidth: meta.mapWidth,
            mapHeight: meta.mapHeight,
            worldSetting: meta.worldSetting,
            mapObjectLabelFontFamily: meta.mapObjectLabelFontFamily,
        });
    } catch {
        initMapsPage({ spriteBaseUrl: '/sprites' });
    }
});
