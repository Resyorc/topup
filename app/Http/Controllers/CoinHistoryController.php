<?php

namespace App\Http\Controllers;

use App\Models\CoinTopup;
use App\Models\CoinTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CoinHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Expire overdue pending topups before showing
        CoinTopup::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->update(['status' => 'expired', 'failure_reason' => 'Pembayaran melewati batas waktu (expired).']);

        $transactions = CoinTransaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn($tx) => [
                'id'           => 'tx_' . $tx->id,
                'source'       => 'transaction',
                'type'         => $tx->type,
                'amount'       => $tx->amount,
                'description'  => $tx->description,
                'reference_id' => $tx->reference_id,
                'status'       => null,
                'created_at'   => $tx->created_at?->toISOString(),
            ]);

        $topups = CoinTopup::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn($topup) => [
                'id'           => 'topup_' . $topup->id,
                'source'       => 'topup',
                'type'         => 'credit',
                'amount'       => $topup->amount,
                'description'  => 'Top Up Krysta Coin',
                'reference_id' => $topup->invoice_id,
                'status'       => $topup->status,
                'created_at'   => $topup->created_at?->toISOString(),
            ]);

        $history = $transactions
            ->concat($topups)
            ->sortByDesc('created_at')
            ->values();

        return Inertia::render('user/coin-history', [
            'coinBalance' => (int) ($user->fresh()->coin_balance ?? 0),
            'history'     => $history,
        ]);
    }
}
