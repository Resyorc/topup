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
                    // Core React runtime — loaded on every page
                    if (id.includes('node_modules/react-dom')) return 'vendor-react-dom';
                    if (id.includes('node_modules/react/')) return 'vendor-react';

                    // Inertia + Echo — loaded on every page
                    if (id.includes('node_modules/@inertiajs')) return 'vendor-inertia';
                    if (
                        id.includes('node_modules/laravel-echo') ||
                        id.includes('node_modules/@laravel/echo')
                    ) return 'vendor-echo';

                    // Sonner toast — loaded on every page via GuestLayout
                    if (id.includes('node_modules/sonner')) return 'vendor-sonner';

                    // Radix UI primitives — only UI/settings pages, not homepage
                    if (id.includes('node_modules/@radix-ui')) return 'vendor-radix';

                    // Headless UI — only settings pages
                    if (id.includes('node_modules/@headlessui')) return 'vendor-headlessui';

                    // SweetAlert2 — only invoice + game-detail pages
                    if (id.includes('node_modules/sweetalert2')) return 'vendor-swal';

                    // Lucide icons — only admin + settings pages
                    if (id.includes('node_modules/lucide-react')) return 'vendor-lucide';

                    // DOMPurify — only 2FA modal, invoice, settings
                    if (id.includes('node_modules/dompurify')) return 'vendor-dompurify';

                    // OTP input — only 2FA pages
                    if (id.includes('node_modules/input-otp')) return 'vendor-otp';
                },
            },
        },
    },
});
