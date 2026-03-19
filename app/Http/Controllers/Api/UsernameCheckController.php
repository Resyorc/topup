<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserIdCheckService;
use Illuminate\Http\Request;

class UsernameCheckController extends Controller
{
    public function check(Request $request, UserIdCheckService $userIdCheckService)
    {
        $validated = $request->validate([
            'game' => 'required|string|max:100',
            'user_id' => 'required|string|max:100',
            'zone_id' => 'nullable|string|max:100',
        ]);

        $result = $userIdCheckService->check(
            $validated['game'],
            $validated['user_id'],
            $validated['zone_id'] ?? null,
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json($result);
    }
}
