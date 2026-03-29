import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/story-cards.js',
                'resources/js/cards-index.js',
                'resources/js/bestiary.js',
                'resources/js/biographies.js',
                'resources/js/factions.js',
                'resources/js/timeline.js',
                'resources/js/connections.js',
                'resources/js/maps.js',
                'resources/js/svg-viewer.js',
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
