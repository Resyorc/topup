<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ApiCredentialController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('user/api-credentials', [
            'apiKey'           => $user->api_access_enabled ? $user->api_key : null,
            'apiAccessEnabled' => (bool) $user->api_access_enabled,
            'emailVerified'    => ! is_null($user->email_verified_at),
        ]);
    }

    public function regenerate(Request $request)
    {
        $user = $request->user();

        abort_unless($user->api_access_enabled, 403, 'Akses API belum diaktifkan.');
        abort_unless(! is_null($user->email_verified_at), 403, 'Email belum diverifikasi.');

        $user->update(['api_key' => Str::random(48)]);

        return back()->with('success', 'API Key berhasil diperbarui.');
    }
}
