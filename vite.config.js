import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    /* Одна копия @codemirror/* в бандле: иначе Facet/Extension из разных инстансов и EditorView падает при init. */
    resolve: {
        dedupe: [
            '@codemirror/state',
            '@codemirror/view',
            '@codemirror/commands',
            '@codemirror/language',
            '@codemirror/search',
            '@codemirror/autocomplete',
            '@codemirror/lint',
        ],
        alias: {
            '@codemirror/state': path.resolve(__dirname, 'node_modules/@codemirror/state'),
            '@codemirror/view': path.resolve(__dirname, 'node_modules/@codemirror/view'),
            '@codemirror/commands': path.resolve(__dirname, 'node_modules/@codemirror/commands'),
            '@codemirror/language': path.resolve(__dirname, 'node_modules/@codemirror/language'),
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/story-cards.js',
                'resources/js/story-card-page.js',
                'resources/js/cards-index.js',
                'resources/js/bestiary.js',
                'resources/js/biographies.js',
                'resources/js/factions.js',
                'resources/js/timeline.js',
                'resources/js/connections.js',
                'resources/js/maps.js',
                'resources/js/svg-viewer.js',
                'resources/js/color-tool.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
