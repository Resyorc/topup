import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/react-dom')) return 'vendor-react-dom';
                    if (id.includes('node_modules/react/')) return 'vendor-react';
                    if (id.includes('node_modules/@inertiajs')) return 'vendor-inertia';
                    if (
                        id.includes('node_modules/laravel-echo') ||
                        id.includes('node_modules/@laravel/echo')
                    ) return 'vendor-echo';
                    if (id.includes('node_modules/sweetalert2')) return 'vendor-swal';
                    if (id.includes('node_modules/lucide-react')) return 'vendor-lucide';
                },
            },
        },
    },
});
