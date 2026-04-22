import { createInertiaApp } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import { GuestInvoiceProvider } from '@/contexts/guest-invoice-context';

// Konfigurasi Echo di SSR agar useEcho/useEchoPublic tidak crash.
// Koneksi WebSocket tidak akan terbentuk saat SSR (hanya setup singleton).
if (import.meta.env.VITE_REVERB_APP_KEY) {
    configureEcho({ broadcaster: 'reverb' });
}

const appName = import.meta.env.VITE_APP_NAME || 'Nuvelo';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob('./pages/**/*.tsx'),
            ),
        setup: ({ App, props }) => {
            return (
                <GuestInvoiceProvider>
                    <App {...props} />
                </GuestInvoiceProvider>
            );
        },
    }),
);

