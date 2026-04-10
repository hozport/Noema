/** @type {Map<string, { title: string, description: string, image_url: string|null }>} */
const previewCache = new Map();

/** @type {number|null} */
let tooltipHideTimer = null;

/** @type {WeakMap<HTMLElement, HTMLElement>} */
const tooltipByRoot = new WeakMap();

function escapeHtml(s) {
    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function escapeAttr(s) {
    return String(s).replace(/"/g, '&quot;');
}

/**
 * @param {HTMLElement} mount
 */
function ensureTooltipEl(mount) {
    let el = tooltipByRoot.get(mount);
    if (el && el.isConnected) {
        return el;
    }
    el = document.createElement('div');
    el.className =
        'noema-entity-tooltip pointer-events-none fixed z-[100000] max-w-xs rounded border border-base-300 bg-base-200 p-2 text-sm shadow-lg opacity-0 transition-opacity';
    el.setAttribute('role', 'tooltip');
    el.style.display = 'none';
    mount.appendChild(el);
    tooltipByRoot.set(mount, el);
    return el;
}

/**
 * @param {string} key
 * @param {string} resolveUrl
 */
async function getPreview(key, resolveUrl) {
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
 * Привязка тултипов к ссылкам сущностей внутри контейнера.
 *
 * @param {HTMLElement} container
 * @param {string} resolveUrl
 * @param {HTMLElement} [tooltipMount] куда монтировать тултип (dialog — поверх модалки)
 */
export function bindEntityLinkTooltips(container, resolveUrl, tooltipMount = document.body) {
    if (!resolveUrl) {
        return;
    }
    const mount = tooltipMount;

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
            const tip = ensureTooltipEl(mount);
            const p = await getPreview(key, resolveUrl);
            if (!p) {
                tip.innerHTML = '<p class="text-base-content/70">Нет данных</p>';
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
                const tip = tooltipByRoot.get(mount);
                if (tip) {
                    tip.style.opacity = '0';
                    tip.style.display = 'none';
                }
            }, 120);
        });
    });
}
