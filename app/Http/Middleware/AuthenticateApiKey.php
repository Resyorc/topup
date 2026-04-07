<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key') ?? $request->query('api_key');

        if (! $key) {
            return response()->json(['success' => false, 'message' => 'API key tidak ditemukan.'], 401);
        }

        $user = User::where('api_key', $key)->first();

        if (! $user || ! $user->api_access_enabled) {
            return response()->json(['success' => false, 'message' => 'API key tidak valid atau akses API belum diaktifkan.'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
