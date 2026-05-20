import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],

    server: {
        host: '0.0.0.0', // Позволяет Vite принимать соединения извне контейнера
        port: 5173,      // Стандартный порт Vite
        hmr: {
            host: 'localhost', // Указывает браузеру стучаться за HMR на ваш ПК, а не внутрь Docker
        },
        watch: {
            usePolling: true, // Нужно, если вы работаете на Windows (WSL), иначе Vite может не заметить изменение файлов
        },
    },
});
