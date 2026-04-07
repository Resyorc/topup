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
        // Jangan blokir admin panel, API callback (webhook), dan aset
        if (
            $request->is('admin/*') ||
            $request->is('api/digiflazz-callback') ||
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

        return Inertia::render('maintenance', [
            'estimatedEnd' => $estimatedEnd ?: null,
            'message'      => $message ?: null,
        ])->toResponse($request)->setStatusCode(503);
    }
}
