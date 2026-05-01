<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        $adminPath = trim((string) config('app.admin_path', 'nuvelo-control'), '/');
        $adminDomain = config('app.admin_domain');

        // Jangan blokir admin panel, API callback (webhook), dan aset
        if (
            ($adminDomain && $request->getHost() === $adminDomain) ||
            ($adminPath !== '' && $request->is($adminPath.'*')) ||
            $request->is('api/digiflazz-callback') ||
            $request->is('api/callback/tripay') ||
            $request->is('api/callback/digiflazz') ||
            $request->is('storage/*')
        ) {
            return $next($request);
        }

        $isOn = (bool) Setting::get('maintenance_mode', false);

        if (! $isOn) {
            return $next($request);
        }

        $estimatedEnd = Setting::get('maintenance_estimated_end');
        $message      = Setting::get('maintenance_message');
        $waNumber     = Setting::get('wa_bubble_number');
        $logo         = Setting::get('web_logo') ? asset('storage/' . Setting::get('web_logo')) : null;

        return Inertia::render('maintenance', [
            'estimatedEnd' => $estimatedEnd ?: null,
            'message'      => $message ?: null,
            'waNumber'     => $waNumber ?: null,
            'logo'         => $logo,
        ])->toResponse($request)->setStatusCode(503);
    }
}
