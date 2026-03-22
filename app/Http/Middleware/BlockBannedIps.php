<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockBannedIps
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip untuk admin panel
        if (str_starts_with($request->path(), 'nuvelo-control')) {
            return $next($request);
        }

        if (BlockedIp::isBlocked($request->ip())) {
            abort(403, 'Akses ditolak. IP kamu telah diblokir karena aktivitas mencurigakan.');
        }

        return $next($request);
    }
}
