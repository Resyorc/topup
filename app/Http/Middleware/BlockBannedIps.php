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
        $adminPath = trim((string) config('app.admin_path', 'nuvelo-control'), '/');
        $adminDomain = config('app.admin_domain');

        // Skip untuk admin panel dan Livewire internal requests
        if (($adminDomain && $request->getHost() === $adminDomain) ||
            ($adminPath !== '' && str_starts_with($request->path(), $adminPath)) ||
            str_starts_with($request->path(), 'livewire')) {
            return $next($request);
        }

        try {
            if (BlockedIp::isBlocked($request->ip())) {
                abort(403, 'Akses ditolak. IP kamu telah diblokir karena aktivitas mencurigakan.');
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Tabel belum ada (migration belum dijalankan), skip pengecekan
            return $next($request);
        }

        return $next($request);
    }
}
