/**
 * Карты мира: Konva, холст 3000×3000, панорамирование, спрайты из public/sprites/…
 */

import Konva from 'konva';

const MAP_W = 3000;
const MAP_H = 3000;
const PLACE_SIZE = 48;
/** Фиксированные линейки у края вьюпорта (экранные px). */
const RULER_W = 14;
const RULER_H = 14;
/** Шаг сетки на холсте = шаг засечек на линейках (мировые px). */
const GRID_STEP = 100;

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
 *   draggingSprite: boolean,
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
    draggingSprite: false,
};

function batchDrawMapLayers() {
    mapState.layer?.batchDraw();
    mapState.rulerLayer?.batchDraw();
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

    return { id: data.id };
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

function spriteDragBounds() {
    const minX = 0;
    const minY = 0;
    const maxX = MAP_W - PLACE_SIZE;
    const maxY = MAP_H - PLACE_SIZE;

    return { minX, minY, maxX, maxY };
}

/**
 * @param {import('konva/lib/shapes/Image').default} kImg
 * @param {number | null} mapSpriteId
 */
function bindSpriteCursor(kImg, mapSpriteId) {
    const container = mapState.container;
    if (!container) {
        return;
    }
    kImg.on('mouseenter', () => {
        if (!mapState.draggingSprite) {
            container.style.cursor = 'grab';
        }
    });
    kImg.on('mouseleave', () => {
        if (!mapState.draggingSprite) {
            container.style.cursor = '';
        }
    });
    kImg.on('dragstart', () => {
        mapState.draggingSprite = true;
        container.style.cursor = 'grabbing';
    });
    kImg.on('dragend', () => {
        mapState.draggingSprite = false;
        container.style.cursor = '';
        const id = mapSpriteId ?? kImg.getAttr('mapSpriteId');
        if (id != null) {
            void persistSpritePosition(Number(id), kImg.x(), kImg.y());
        }
    });
}

/**
 * @param {HTMLImageElement} img
 * @param {number} posX левый верхний угол
 * @param {number} posY
 * @param {number | null} mapSpriteId
 * @returns {import('konva/lib/shapes/Image').default}
 */
function createPlacedKonvaImage(img, posX, posY, mapSpriteId) {
    const { minX, minY, maxX, maxY } = spriteDragBounds();
    const kImg = new Konva.Image({
        x: posX,
        y: posY,
        image: img,
        width: PLACE_SIZE,
        height: PLACE_SIZE,
        draggable: true,
        name: 'placed-sprite',
        dragBoundFunc: (pos) => ({
            x: Math.max(minX, Math.min(maxX, pos.x)),
            y: Math.max(minY, Math.min(maxY, pos.y)),
        }),
    });
    if (mapSpriteId != null) {
        kImg.setAttr('mapSpriteId', mapSpriteId);
    }
    bindSpriteCursor(kImg, mapSpriteId);

    return kImg;
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
    if (worldX < 0 || worldX > MAP_W || worldY < 0 || worldY > MAP_H) {
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
        const kImg = createPlacedKonvaImage(img, posX, posY, saved.id);
        wg.add(kImg);
        kImg.moveToTop();
        batchDrawMapLayers();
    } catch {
        // ignore failed loads / save
    }
}

/**
 * @param {HTMLElement} container
 */
function initKonva(container) {
    mapState.container = container;

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
        width: MAP_W,
        height: MAP_H,
        fill: '#d8d6cf',
        stroke: '#7a786f',
        strokeWidth: 2,
        name: 'map-bg',
    });
    worldGroup.add(bg);
    mapState.mapBg = bg;

    const gridGroup = new Konva.Group({ listening: false, name: 'map-grid' });
    const gridStroke = 'rgba(168, 166, 160, 0.28)';
    for (let gx = 0; gx <= MAP_W; gx += GRID_STEP) {
        gridGroup.add(
            new Konva.Line({
                points: [gx, 0, gx, MAP_H],
                stroke: gridStroke,
                strokeWidth: 1,
                listening: false,
                perfectDrawEnabled: false,
            }),
        );
    }
    for (let gy = 0; gy <= MAP_H; gy += GRID_STEP) {
        gridGroup.add(
            new Konva.Line({
                points: [0, gy, MAP_W, gy],
                stroke: gridStroke,
                strokeWidth: 1,
                listening: false,
                perfectDrawEnabled: false,
            }),
        );
    }
    worldGroup.add(gridGroup);

    const crosshairGroup = new Konva.Group({ listening: false, name: 'map-crosshair' });
    const guideStroke = 'rgba(120, 118, 110, 0.22)';
    const crosshairV = new Konva.Line({
        points: [0, 0, 0, MAP_H],
        stroke: guideStroke,
        strokeWidth: 1,
        listening: false,
        visible: false,
    });
    const crosshairH = new Konva.Line({
        points: [0, 0, MAP_W, 0],
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

        for (let wwx = 0; wwx <= MAP_W; wwx += 100) {
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

        for (let wwy = 0; wwy <= MAP_H; wwy += 100) {
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
        if (MAP_W >= sw) {
            x = Math.min(0, Math.max(sw - MAP_W, x));
        } else {
            x = (sw - MAP_W) / 2;
        }
        if (MAP_H >= sh) {
            y = Math.min(0, Math.max(sh - MAP_H, y));
        } else {
            y = (sh - MAP_H) / 2;
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
            x: (sw - MAP_W) / 2,
            y: (sh - MAP_H) / 2,
        });
        clampWorldGroupPosition();
    }
    applyInitialWorldPosition();

    let isPanning = false;
    let lastPan = { x: 0, y: 0 };

    function updateCrosshairFromPointer() {
        const pos = stage.getPointerPosition();
        const wg = mapState.worldGroup;
        const v = mapState.crosshairV;
        const h = mapState.crosshairH;
        if (!pos || !wg || !v || !h) {
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
            rel.x > MAP_W ||
            rel.y < 0 ||
            rel.y > MAP_H ||
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
        let onSprite = false;
        let n = hit instanceof Konva.Node ? hit : null;
        while (n) {
            if (n.name() === 'placed-sprite') {
                onSprite = true;
                break;
            }
            n = n.getParent();
        }
        if (onSprite) {
            v.visible(false);
            h.visible(false);

            return;
        }
        v.points([wx, 0, wx, MAP_H]);
        h.points([0, wy, MAP_W, wy]);
        v.visible(true);
        h.visible(true);
    }

    function setMapCursor() {
        const c = mapState.container;
        if (!c) {
            return;
        }
        if (mapState.draggingSprite) {
            c.style.cursor = 'grabbing';

            return;
        }
        const pos = stage.getPointerPosition();
        if (!pos) {
            c.style.cursor = '';

            return;
        }
        if (pos.x < RULER_W || pos.y < RULER_H) {
            c.style.cursor = 'default';

            return;
        }
        if (isPanning) {
            c.style.cursor = 'grabbing';

            return;
        }
        const hit = stage.getIntersection(pos);
        let onSprite = false;
        let n = hit instanceof Konva.Node ? hit : null;
        while (n) {
            if (n.name() === 'placed-sprite') {
                onSprite = true;
                break;
            }
            n = n.getParent();
        }
        if (onSprite) {
            c.style.cursor = 'grab';
        } else {
            c.style.cursor = 'crosshair';
        }
    }

    stage.on('mousedown', (e) => {
        if (e.evt.button === 1) {
            isPanning = true;
            const p = stage.getPointerPosition();
            if (p) {
                lastPan = { x: p.x, y: p.y };
            }
            e.evt.preventDefault();
            setMapCursor();
        }
    });

    window.addEventListener('mouseup', () => {
        if (isPanning) {
            isPanning = false;
            setMapCursor();
        }
    });

    stage.on('mousemove', () => {
        const pos = stage.getPointerPosition();
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
        if (mapState.container && !mapState.draggingSprite) {
            mapState.container.style.cursor = '';
        }
        batchDrawMapLayers();
    });

    stage.on('contextmenu', (e) => {
        e.evt.preventDefault();
        const pos = stage.getPointerPosition();
        if (!pos) {
            return;
        }
        const hit = stage.getIntersection(pos);
        let n = hit instanceof Konva.Node ? hit : null;
        while (n) {
            if (n.name() === 'placed-sprite') {
                const spriteNode = n;
                const id = spriteNode.getAttr('mapSpriteId');
                void (async () => {
                    if (id == null) {
                        spriteNode.destroy();
                        batchDrawMapLayers();

                        return;
                    }
                    const ok = await persistSpriteDelete(Number(id));
                    if (ok) {
                        spriteNode.destroy();
                        batchDrawMapLayers();
                    }
                })();

                return;
            }
            n = n.getParent();
        }
    });

    stage.on('click', (e) => {
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
        if (pos.x < 0 || pos.x > MAP_W || pos.y < 0 || pos.y > MAP_H) {
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

    void hydrateInitialPlacements();
}

/**
 * Загрузка спрайтов из БД после инициализации сцены.
 */
async function hydrateInitialPlacements() {
    const list = mapState.initialPlacements;
    const wg = mapState.worldGroup;
    const base = mapState.spriteBase;
    if (!list?.length || !wg) {
        return;
    }
    for (const row of list) {
        const parts = splitSpritePath(row.sprite_path);
        if (!parts) {
            continue;
        }
        const url = spriteUrl(base, parts.folder, parts.file);
        try {
            const img = await loadImage(url);
            const kImg = createPlacedKonvaImage(img, row.pos_x, row.pos_y, row.id);
            wg.add(kImg);
            kImg.moveToTop();
        } catch {
            // пропускаем битые ссылки
        }
    }
    batchDrawMapLayers();
}

/**
 * @param {{ spriteBaseUrl: string, mapSprites?: Array<{ id: number, sprite_path: string, pos_x: number, pos_y: number }>, mapsSpriteStoreUrl?: string, mapsSpriteUpdateUrlPattern?: string }} opts
 */
export function initMapsPage(opts) {
    mapState.spriteBase = opts.spriteBaseUrl || '/sprites';
    mapState.initialPlacements = opts.mapSprites ?? [];
    mapState.storeUrl = opts.mapsSpriteStoreUrl ?? '';
    mapState.updateUrlPattern = opts.mapsSpriteUpdateUrlPattern ?? '';
    const mount = document.getElementById('map-stage-mount');
    if (!mount) {
        return;
    }

    initKonva(mount);

    const typeSelect = document.getElementById('map-type-select');
    const spriteGrid = document.getElementById('map-sprite-grid');
    const catButtons = document.querySelectorAll('[data-map-category]');
    const hintEl = document.getElementById('map-placement-hint');
    const panel = document.getElementById('map-sidebar-panel');

    /** @type {typeof MAP_SPRITE_CATALOG[0] | null} */
    let activeCategory = null;
    /** @type {string | null} */
    let openCategoryId = null;

    function setHint() {
        if (hintEl) {
            if (!openCategoryId) {
                hintEl.textContent = 'Откройте категорию иконкой слева.';

                return;
            }
            hintEl.textContent = mapState.selectedSpriteUrl
                ? 'Кликните по карте, чтобы поставить объект. Средняя кнопка мыши — сдвиг карты.'
                : 'Выберите спрайт в сетке ниже.';
        }
    }

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
        openCategoryId = null;
        activeCategory = null;
        mapState.selectedSpriteUrl = null;
        mapState.selectedSpritePath = null;
        if (panel) {
            panel.classList.remove('map-sidebar-panel--open');
            panel.setAttribute('aria-hidden', 'true');
        }
        highlightCategoryButtons('');
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

    if (typeSelect) {
        typeSelect.addEventListener('change', buildSpriteGrid);
    }

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
        });
    } catch {
        initMapsPage({ spriteBaseUrl: '/sprites' });
    }
});
