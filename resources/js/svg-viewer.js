/**
 * Просмотр SVG: код слева, рендер справа.
 */

const DEBOUNCE_MS = 120;

/**
 * @param {SVGElement} root
 */
function sanitizeSvgElement(root) {
    root.querySelectorAll('script').forEach((el) => el.remove());
    root.querySelectorAll('*').forEach((el) => {
        [...el.attributes].forEach((attr) => {
            const n = attr.name.toLowerCase();
            const v = attr.value;
            if (n.startsWith('on') || v.trim().toLowerCase().startsWith('javascript:')) {
                el.removeAttribute(attr.name);
            }
        });
    });
}

/**
 * @param {string} raw
 * @param {HTMLElement} preview
 * @param {HTMLElement | null} errorEl
 */
function renderSvg(raw, preview, errorEl) {
    preview.innerHTML = '';
    if (errorEl) {
        errorEl.classList.add('hidden');
        errorEl.textContent = '';
    }

    const trimmed = raw.trim();
    if (!trimmed) {
        return;
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(trimmed, 'image/svg+xml');
    const err = doc.querySelector('parsererror');
    if (err) {
        if (errorEl) {
            const i18n = typeof window !== 'undefined' ? window.svgViewerI18n : undefined;
            errorEl.textContent =
                err.textContent?.trim() || (i18n && i18n.parseFail) || 'XML parse error.';
            errorEl.classList.remove('hidden');
        }

        return;
    }

    const root = doc.documentElement;
    if (root.tagName.toLowerCase() !== 'svg') {
        if (errorEl) {
            const i18n = typeof window !== 'undefined' ? window.svgViewerI18n : undefined;
            errorEl.textContent = (i18n && i18n.notSvg) || 'Root element must be <svg>.';
            errorEl.classList.remove('hidden');
        }

        return;
    }

    const svg = /** @type {SVGElement} */ (root.cloneNode(true));
    sanitizeSvgElement(svg);
    if (!svg.getAttribute('xmlns')) {
        svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    }
    svg.setAttribute('class', 'svg-viewer-preview-svg max-w-full max-h-full w-auto h-auto');
    preview.appendChild(svg);
}

function initSvgViewerPage() {
    const ta = document.getElementById('svg-viewer-input');
    const preview = document.getElementById('svg-viewer-preview');
    const errorEl = document.getElementById('svg-viewer-error');
    if (!ta || !preview) {
        return;
    }

    let t = 0;
    const run = () => {
        renderSvg(ta.value, preview, errorEl);
    };

    ta.addEventListener('input', () => {
        window.clearTimeout(t);
        t = window.setTimeout(run, DEBOUNCE_MS);
    });

    run();
}

document.addEventListener('DOMContentLoaded', initSvgViewerPage);
