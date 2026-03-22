<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate nonce SEBELUM view dirender agar @vite dan blade bisa pakai nonce yang sama.
        // Hanya di non-local karena CSP hanya aktif di production/staging.
        $nonce = null;
        if (! app()->isLocal()) {
            $nonce = base64_encode(random_bytes(16));
            Vite::useCspNonce($nonce);
        }

        $response = $next($request);

        // Cegah browser menebak tipe konten (MIME sniffing)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Cegah halaman di-embed di iframe (clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Paksa HTTPS selama 1 tahun, termasuk subdomain.
        // preload: memungkinkan domain didaftarkan ke browser HSTS preload list (hstspreload.org)
        // agar browser langsung pakai HTTPS tanpa perlu request pertama via HTTP.
        // PERINGATAN: Setelah didaftarkan ke preload list, sulit untuk di-undo.
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Batasi informasi referrer yang dikirim ke domain lain
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Batasi fitur browser yang boleh dipakai
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Cegah window opener attacks: tab baru yang dibuka dari halaman ini
        // tidak bisa mengakses window.opener dan memanipulasi halaman asal.
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // Wajibkan Trusted Types untuk mencegah DOM-based XSS.
        // 'allow-duplicates' diperlukan agar Google Tag Manager bisa jalan.
        $response->headers->set('Content-Security-Policy-Report-Only', "require-trusted-types-for 'script'; trusted-types 'allow-duplicates'");

        // Content Security Policy — hanya aktif di non-local.
        // Di local, Vite HMR pakai IPv6 [::1] yang tidak bisa di-whitelist via CSP wildcard.
        if ($nonce !== null) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",

                // Nonce-based: hanya script dengan nonce yang boleh jalan.
                // 'strict-dynamic' membolehkan script yang dimuat secara dinamis oleh script ber-nonce (Vite modules).
                // 'unsafe-inline' diabaikan browser modern yang support nonce/strict-dynamic,
                // tapi diperlukan sebagai fallback untuk browser lama (meningkatkan skor Lighthouse).
                // Domain GA sebagai fallback untuk browser lama yang tidak support strict-dynamic.
                "script-src 'unsafe-inline' 'nonce-{$nonce}' 'strict-dynamic' https://www.googletagmanager.com",

                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com",

                // Izinkan Google Analytics melakukan beacon/fetch dari JS
                "connect-src 'self' https://www.google-analytics.com https://analytics.google.com",

                // Blokir plugin lama (Flash, dll.)
                "object-src 'none'",

                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ]));
        }

        return $response;
    }
}
