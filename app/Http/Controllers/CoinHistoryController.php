<?php

namespace App\Http\Controllers;

use App\Models\CoinTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CoinHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $history = CoinTransaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn($tx) => [
                'id'          => $tx->id,
                'type'        => $tx->type,   // 'credit' | 'debit'
                'amount'      => $tx->amount,
                'description' => $tx->description,
                'reference_id'=> $tx->reference_id,
                'created_at'  => $tx->created_at?->toISOString(),
            ])
            ->values();

        return Inertia::render('user/coin-history', [
            'coinBalance' => (int) ($user->fresh()->coin_balance ?? 0),
            'history'     => $history,
        ]);
    }
}
