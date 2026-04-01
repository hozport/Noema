/**
 * Разметка карточек Noema (зеркало PHP NoemaMarkupParser).
 * @typedef {{ type: 'text', text: string }} TextNode
 * @typedef {{ type: 'bold'|'italic'|'underline'|'strike', children: Node[] }} FormatNode
 * @typedef {{ type: 'link', module: number, entity: number, children: Node[] }} LinkNode
 * @typedef {TextNode|FormatNode|LinkNode} Node
 */

export class NoemaMarkupParserJs {
    constructor() {
        this.s = '';
        this.len = 0;
        this.pos = 0;
        /** @type {string[]} */
        this.errors = [];
    }

    /**
     * @returns {Node[]}
     */
    parse(input) {
        this.s = input;
        this.len = input.length;
        this.pos = 0;
        this.errors = [];
        const nodes = this.parseUntil(null);
        return this.mergeAdjacentText(nodes);
    }

    getErrors() {
        return this.errors;
    }

    /**
     * @param {string|null} close
     * @returns {Node[]}
     */
    parseUntil(close) {
        /** @type {Node[]} */
        const nodes = [];
        while (this.pos < this.len) {
            if (close !== null) {
                const chk = this.checkClosing(close);
                if (chk === true) {
                    return nodes;
                }
                if (chk === 'mismatch') {
                    this.errors.push(`Неверный закрывающий тег (ожидался [/${close}]).`);
                    nodes.push({ type: 'text', text: '[' });
                    this.pos++;
                    continue;
                }
            }

            if (this.peek() === '\\') {
                this.pos++;
                if (this.pos >= this.len) {
                    nodes.push({ type: 'text', text: '\\' });
                    break;
                }
                nodes.push({ type: 'text', text: this.s[this.pos] });
                this.pos++;
                continue;
            }

            if (this.peek() === '[') {
                const opened = this.tryParseOpenTag();
                if (opened !== null) {
                    nodes.push(opened);
                    continue;
                }
                nodes.push({ type: 'text', text: '[' });
                this.pos++;
                continue;
            }

            nodes.push({ type: 'text', text: this.peek() });
            this.pos++;
        }

        if (close !== null) {
            this.errors.push(`Не найден закрывающий тег [/${close}].`);
        }

        return nodes;
    }

    peek() {
        return this.s[this.pos] ?? '';
    }

    /**
     * @returns {true|'mismatch'|false}
     */
    checkClosing(expected) {
        if (this.peek() !== '[') {
            return false;
        }
        const sub = this.s.slice(this.pos);
        const re = new RegExp(`^\\[\\/(${expected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})\\]`);
        const m = sub.match(re);
        if (m) {
            this.pos += m[0].length;
            return true;
        }
        if (/^\[\//.test(sub)) {
            return 'mismatch';
        }
        return false;
    }

    /**
     * @returns {Node|null}
     */
    tryParseOpenTag() {
        const sub = this.s.slice(this.pos);
        if (sub.startsWith('[/')) {
            return null;
        }

        const linkOpen = sub.match(/^\[link\s+module\s*=\s*(\d+)\s+entity\s*=\s*(\d+)\s*\]/);
        if (linkOpen) {
            this.pos += linkOpen[0].length;
            const module = parseInt(linkOpen[1], 10);
            const entity = parseInt(linkOpen[2], 10);
            const children = this.parseUntil('link');
            return {
                type: 'link',
                module,
                entity,
                children: this.mergeAdjacentText(children),
            };
        }

        const map = [
            ['b', 'bold'],
            ['i', 'italic'],
            ['u', 'underline'],
            ['s', 'strike'],
        ];
        for (const [tag, type] of map) {
            const open = `[${tag}]`;
            if (sub.startsWith(open)) {
                this.pos += open.length;
                const children = this.parseUntil(tag);
                return { type, children: this.mergeAdjacentText(children) };
            }
        }

        return null;
    }

    /**
     * @param {Node[]} nodes
     * @returns {Node[]}
     */
    mergeAdjacentText(nodes) {
        /** @type {Node[]} */
        const out = [];
        for (const n of nodes) {
            if (n.type === 'text' && n.text === '') {
                continue;
            }
            if (n.type === 'text' && out.length > 0) {
                const last = out[out.length - 1];
                if (last.type === 'text') {
                    last.text += n.text;
                    continue;
                }
            }
            out.push(n);
        }
        return out;
    }
}

/**
 * @param {Node[]} nodes
 */
export function renderNoemaMarkupHtml(nodes) {
    let html = '';
    for (const n of nodes) {
        html += renderNodeHtml(n);
    }
    return html;
}

/**
 * @param {Node} n
 */
function renderNodeHtml(n) {
    switch (n.type) {
        case 'text':
            return escapeHtml(n.text);
        case 'bold':
            return `<strong>${renderNoemaMarkupHtml(n.children)}</strong>`;
        case 'italic':
            return `<em>${renderNoemaMarkupHtml(n.children)}</em>`;
        case 'underline':
            return `<u>${renderNoemaMarkupHtml(n.children)}</u>`;
        case 'strike':
            return `<s>${renderNoemaMarkupHtml(n.children)}</s>`;
        case 'link': {
            const m = n.module;
            const e = n.entity;
            const inner = renderNoemaMarkupHtml(n.children);
            const attrs = ` class="noema-entity-link" data-noema-module="${m}" data-noema-entity="${e}" href="#" role="button"`;
            return `<a${attrs}>${inner}</a>`;
        }
        default:
            return '';
    }
}

/**
 * @param {Node[]} nodes
 */
export function plainFromNodes(nodes) {
    let t = '';
    for (const n of nodes) {
        t += plainNode(n);
    }
    return t;
}

/**
 * @param {Node} n
 */
function plainNode(n) {
    switch (n.type) {
        case 'text':
            return n.text;
        case 'bold':
        case 'italic':
        case 'underline':
        case 'strike':
        case 'link':
            return plainFromNodes(n.children || []);
        default:
            return '';
    }
}

/**
 * @param {Node[]} nodes
 * @param {{ module: number, entity: number }[]} [out]
 */
export function collectLinkRefs(nodes, out = []) {
    for (const n of nodes) {
        if (n.type === 'link') {
            out.push({ module: n.module, entity: n.entity });
        }
        if (n.children && n.children.length) {
            collectLinkRefs(n.children, out);
        }
    }
    return out;
}

export function escapeHtml(s) {
    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * @param {string} source
 */
export function parseAndRenderHtml(source) {
    const p = new NoemaMarkupParserJs();
    const nodes = p.parse(source);
    return { html: renderNoemaMarkupHtml(nodes), nodes, errors: p.getErrors() };
}

/**
 * @param {string} source
 */
export function parseToPlain(source) {
    const p = new NoemaMarkupParserJs();
    const nodes = p.parse(source);
    return plainFromNodes(nodes);
}

export const MODULE_OPTIONS = [
    { value: 1, label: 'Объекты карты (скоро)' },
    { value: 2, label: 'Линия таймлайна' },
    { value: 3, label: 'Бестиарий' },
    { value: 4, label: 'Биография' },
    { value: 5, label: 'Фракция' },
];
