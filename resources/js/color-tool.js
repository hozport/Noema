/**
 * Инструмент «Цвета»: разбор CSS-цвета в поле ввода и обратно с нативного color input.
 */

/** @type {HTMLCanvasElement | null} */
let parseCanvas = null;

/**
 * Контекст 1×1 px для разбора строки цвета через движок CSS браузера.
 *
 * @returns {CanvasRenderingContext2D | null}
 */
function getParseCtx() {
    if (!parseCanvas) {
        parseCanvas = document.createElement('canvas');
        parseCanvas.width = 1;
        parseCanvas.height = 1;
    }

    return parseCanvas.getContext('2d', { willReadFrequently: true });
}

/**
 * Пытается распарсить строку как CSS Color. Невалидные значения дают null.
 *
 * @param {string} raw
 * @returns {{ r: number, g: number, b: number, a: number } | null}
 */
function parseCssColor(raw) {
    const s = raw.trim();
    if (!s) {
        return null;
    }
    const ctx = getParseCtx();
    if (!ctx) {
        return null;
    }
    ctx.fillStyle = 'rgb(0, 1, 2)';
    const sentinel = ctx.fillStyle;
    ctx.fillStyle = s;
    if (ctx.fillStyle === sentinel) {
        return null;
    }
    ctx.fillRect(0, 0, 1, 1);
    const d = ctx.getImageData(0, 0, 1, 1).data;

    return { r: d[0], g: d[1], b: d[2], a: d[3] / 255 };
}

/**
 * RGB → HSL (градусы и проценты).
 *
 * @param {number} r
 * @param {number} g
 * @param {number} b
 * @returns {{ h: number, s: number, l: number }}
 */
function rgbToHsl(r, g, b) {
    const rn = r / 255;
    const gn = g / 255;
    const bn = b / 255;
    const max = Math.max(rn, gn, bn);
    const min = Math.min(rn, gn, bn);
    const l = (max + min) / 2;
    if (max === min) {
        return { h: 0, s: 0, l: l * 100 };
    }
    const d = max - min;
    const s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    let h = 0;
    switch (max) {
        case rn:
            h = (gn - bn) / d + (gn < bn ? 6 : 0);
            break;
        case gn:
            h = (bn - rn) / d + 2;
            break;
        default:
            h = (rn - gn) / d + 4;
    }
    h /= 6;

    return { h: h * 360, s: s * 100, l: l * 100 };
}

/**
 * @param {number} n
 * @returns {string}
 */
function hexByte(n) {
    return Math.round(Math.max(0, Math.min(255, n)))
        .toString(16)
        .padStart(2, '0');
}

/**
 * Форматы вывода для rgba.
 *
 * @param {number} r
 * @param {number} g
 * @param {number} b
 * @param {number} a
 * @returns {Record<string, string>}
 */
function buildFormats(r, g, b, a) {
    const hsl = rgbToHsl(r, g, b);
    const hRound = Math.round(hsl.h * 10) / 10;
    const sRound = Math.round(hsl.s * 10) / 10;
    const lRound = Math.round(hsl.l * 10) / 10;
    const hex =
        a >= 1 - 1e-4
            ? `#${hexByte(r)}${hexByte(g)}${hexByte(b)}`
            : `#${hexByte(r)}${hexByte(g)}${hexByte(b)}${hexByte(a * 255)}`;

    const rgbStr =
        a >= 1 - 1e-4
            ? `rgb(${r}, ${g}, ${b})`
            : `rgba(${r}, ${g}, ${b}, ${a.toFixed(3).replace(/\.?0+$/, '') || '0'})`;
    const hslStr =
        a >= 1 - 1e-4
            ? `hsl(${hRound}, ${sRound}%, ${lRound}%)`
            : `hsla(${hRound}, ${sRound}%, ${lRound}%, ${a.toFixed(3).replace(/\.?0+$/, '') || '0'})`;

    return {
        HEX: hex.toLowerCase(),
        RGB: rgbStr,
        HSL: hslStr,
    };
}

/**
 * @returns {void}
 */
function initColorToolPage() {
    const input = document.getElementById('color-tool-input');
    const preview = document.getElementById('color-tool-preview');
    const picker = document.getElementById('color-tool-picker');
    const codesEl = document.getElementById('color-tool-codes');
    const errEl = document.getElementById('color-tool-error');
    const i18n = window.colorToolI18n || {};

    if (!input || !preview || !picker || !codesEl) {
        return;
    }

    let syncing = false;

    /**
     * @param {{ r: number, g: number, b: number, a: number }} c
     * @returns {void}
     */
    function applyColor(c) {
        const bg =
            c.a < 1 - 1e-4
                ? `rgba(${c.r}, ${c.g}, ${c.b}, ${c.a})`
                : `rgb(${c.r}, ${c.g}, ${c.b})`;
        preview.style.background = bg;
        const fmts = buildFormats(c.r, c.g, c.b, c.a);
        codesEl.innerHTML = '';
        const dl = document.createElement('dl');
        dl.className = 'space-y-2 text-sm font-mono';
        Object.entries(fmts).forEach(([label, value]) => {
            const dt = document.createElement('dt');
            dt.className = 'text-xs text-base-content/55 uppercase tracking-wide';
            dt.textContent = label;
            const dd = document.createElement('dd');
            const code = document.createElement('code');
            code.className = 'text-base-content break-all';
            code.textContent = value;
            code.title = i18n.copyHint || '';
            code.style.cursor = 'pointer';
            code.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(value);
                    if (typeof window.showFlashToastMessage === 'function') {
                        window.showFlashToastMessage(i18n.copied || 'Copied', 'success');
                    }
                } catch {
                    //
                }
            });
            dd.appendChild(code);
            dl.appendChild(dt);
            dl.appendChild(dd);
        });
        codesEl.appendChild(dl);
    }

    /**
     * @param {{ r: number, g: number, b: number, a: number }} c
     * @returns {void}
     */
    function setPickerFromRgb(c) {
        const hex = `#${hexByte(c.r)}${hexByte(c.g)}${hexByte(c.b)}`;
        if (picker.value !== hex) {
            picker.value = hex;
        }
    }

    input.addEventListener('input', () => {
        if (syncing) {
            return;
        }
        const c = parseCssColor(input.value);
        if (!c) {
            if (errEl) {
                errEl.textContent = i18n.invalid || '';
                errEl.classList.remove('hidden');
            }
            preview.style.background = 'transparent';
            codesEl.innerHTML = '';

            return;
        }
        if (errEl) {
            errEl.classList.add('hidden');
        }
        syncing = true;
        setPickerFromRgb(c);
        syncing = false;
        applyColor(c);
    });

    picker.addEventListener('input', () => {
        if (syncing) {
            return;
        }
        const hex = picker.value;
        const c = parseCssColor(hex);
        if (!c) {
            return;
        }
        syncing = true;
        input.value = hex.toLowerCase();
        syncing = false;
        if (errEl) {
            errEl.classList.add('hidden');
        }
        applyColor({ r: c.r, g: c.g, b: c.b, a: 1 });
    });

    const initial = parseCssColor(input.value.trim()) || parseCssColor('#4a7dbc');
    if (initial) {
        syncing = true;
        input.value = buildFormats(initial.r, initial.g, initial.b, initial.a).HEX;
        setPickerFromRgb({ ...initial, a: 1 });
        syncing = false;
        if (errEl) {
            errEl.classList.add('hidden');
        }
        applyColor({ r: initial.r, g: initial.g, b: initial.b, a: initial.a });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initColorToolPage);
} else {
    initColorToolPage();
}
