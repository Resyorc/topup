<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VoucherService;
use Illuminate\Http\Request;

class ValidateVoucherController extends Controller
{
    public function __invoke(Request $request, VoucherService $voucherService)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'amount' => 'required|integer|min:1',
        ]);

        $result = $voucherService->validate($validated['code'], (int) $validated['amount']);

        return response()->json([
            'valid' => $result['valid'],
            'discount' => $result['discount'],
            'message' => $result['message'],
        ], $result['valid'] ? 200 : 422);
    }
}
