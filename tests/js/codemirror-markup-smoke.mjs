/**
 * Смоук-тест без браузера: сборка расширений и темы как в resources/js/markup-editor.js.
 * Проверяет, что EditorView.theme() не падает на наших селекторах (без &light/&dark).
 */
import { EditorState } from '@codemirror/state';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import { defaultHighlightStyle, syntaxHighlighting } from '@codemirror/language';
import {
    EditorView,
    drawSelection,
    highlightSpecialChars,
    keymap,
    lineNumbers,
} from '@codemirror/view';

const noemaMarkupEditorExtensions = [
    lineNumbers(),
    highlightSpecialChars(),
    history(),
    drawSelection(),
    syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
    keymap.of([...defaultKeymap, ...historyKeymap]),
];

const themeSpec = {
    '&': { maxHeight: '22rem' },
    '.cm-scroller': { fontFamily: 'ui-monospace, monospace', fontSize: '13px' },
    '.cm-noema-tag': { color: 'var(--color-accent, #7c3aed)' },
    '.cm-noema-escape': { color: 'var(--color-base-content, #ccc)', opacity: 0.55 },
    '.cm-activeLine': { backgroundColor: 'transparent' },
    '.cm-activeLineGutter': { backgroundColor: 'transparent' },
};

const themeExt = EditorView.theme(themeSpec);
if (!themeExt) {
    throw new Error('EditorView.theme() returned empty');
}

const state = EditorState.create({
    doc: 'test [b]x[/b]',
    extensions: [...noemaMarkupEditorExtensions, themeExt],
});
if (state.doc.toString() !== 'test [b]x[/b]') {
    throw new Error('EditorState doc mismatch');
}

let badSelectorThrows = false;
try {
    EditorView.theme({ '&light .cm-activeLine': { backgroundColor: 'transparent' } });
} catch (e) {
    badSelectorThrows =
        e instanceof RangeError && String(e.message).includes('Unsupported selector');
}
if (!badSelectorThrows) {
    throw new Error(
        'Regression: &light in EditorView.theme() should throw (use .cm-activeLine instead)'
    );
}

console.log('codemirror-markup-smoke: ok');
