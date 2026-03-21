<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark'=> ($appearance ?? 'system') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script nonce="{{ Vite::cspNonce() }}">
        (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    {{-- Google Analytics — dimuat setelah browser idle agar tidak blokir render --}}
    <script nonce="{{ Vite::cspNonce() }}">
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-VC29SDR832');

        function loadGtag() {
            var s = document.createElement('script');
            s.src = 'https://www.googletagmanager.com/gtag/js?id=G-VC29SDR832';
            s.async = true;
            document.head.appendChild(s);
        }

        if ('requestIdleCallback' in window) {
            requestIdleCallback(loadGtag, { timeout: 4000 });
        } else {
            window.addEventListener('load', loadGtag);
        }
    </script>

    <title inertia>{{ config('app.name', 'Nuvelo') }}</title>

    <link rel="icon" href="{{ secure_asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ secure_asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ secure_asset('apple-touch-icon.png') }}">

    {{-- Preconnect untuk mempercepat handshake ke font servers --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Font stylesheets — dimuat via <link> bukan @import agar tidak render-blocking --}}
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Orbitron:wght@400;500;700&family=Oxanium:wght@300;400;600;700&display=swap" rel="stylesheet">

    @viteReactRefresh
    @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>