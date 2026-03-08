<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CodashopServices;

class UsernameCheckController extends Controller
{
    public function check(Request $request)
    {
        $request->validate([
            'game' => 'required|string',
            'user_id' => 'required|string',
            'zone_id' => 'nullable|string',
        ]);

        $service = new CodashopServices();
        $result = $service->check(
            $request->input('game'),
            $request->input('user_id'),
            $request->input('zone_id')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json($result);
    }
}
