<?php

namespace App\Http\Middleware;

use App\Models\VisitorLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogVisitor
{
    // Prefix URL yang tidak perlu dicatat
    private const SKIP_PREFIXES = [
        '/nuvelo-control',  // admin panel
        '/up',              // health check
        '/livewire',        // Livewire internal
    ];

    // Ekstensi file statis yang tidak perlu dicatat
    private const SKIP_EXTENSIONS = [
        'js', 'css', 'webp', 'png', 'jpg', 'jpeg', 'gif', 'svg',
        'ico', 'woff', 'woff2', 'ttf', 'map',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Hanya catat GET request (skip POST checkout, API, dll.)
        if (! $request->isMethod('GET')) {
            return $response;
        }

        $path = $request->path();

        // Skip admin panel dan health check
        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with('/'.$path, $prefix)) {
                return $response;
            }
        }

        // Skip file statis
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ($ext && in_array(strtolower($ext), self::SKIP_EXTENSIONS)) {
            return $response;
        }

        // Skip response error (404, 500, dll.) agar log tidak penuh noise
        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        VisitorLog::create([
            'ip'         => $request->ip(),
            'url'        => '/'.$path,
            'method'     => $request->method(),
            'user_agent' => $request->userAgent(),
            'referer'    => $request->headers->get('referer'),
            'user_id'    => $request->user()?->id,
            'visited_at' => now(),
        ]);

        return $response;
    }
}
