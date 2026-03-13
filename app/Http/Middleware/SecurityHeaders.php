<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Cegah browser menebak tipe konten (MIME sniffing)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Cegah halaman di-embed di iframe (clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Paksa HTTPS selama 1 tahun, termasuk subdomain
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Batasi informasi referrer yang dikirim ke domain lain
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Batasi fitur browser yang boleh dipakai
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy — hanya aktif di production
        // Di local, Vite HMR pakai IPv6 [::1] yang tidak bisa di-whitelist via CSP wildcard
        if (!app()->isLocal()) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com",
                // ❌ Hapus 3 baris di bawah ini (sisa lama):
                // "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                // "img-src 'self' data: blob: https:",
                // "font-src 'self' data: https://fonts.bunny.net",
                "connect-src 'self'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ]));
        }

        return $response;
    }
}
