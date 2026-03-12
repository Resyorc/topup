<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionExpiryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, TransactionExpiryService $transactionExpiryService)
    {
        $user = $request->user();

        $transactionExpiryService->expireOverdue(userId: (int) $user->id);

        $baseTodayQuery = Transaction::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString());

        $dashboardStats = [
            'pending' => (clone $baseTodayQuery)->whereIn('status', ['pending', 'paid'])->count(),
            'processing' => (clone $baseTodayQuery)->where('status', 'processing')->count(),
            'success' => (clone $baseTodayQuery)->where('status', 'success')->count(),
            'failed' => (clone $baseTodayQuery)->where('status', 'failed')->count(),
        ];

        $coinsBalance = (int) ($user->fresh()->coin_balance ?? 0);

        $recentTransactions = Transaction::query()
            ->where('user_id', $user->id)
            ->with(['product:id,game_id,name', 'product.game:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (Transaction $transaction) {
                return [
                    'invoice_id' => $transaction->invoice_id,
                    'game_name' => $transaction->product?->game?->name ?? '-',
                    'product_name' => $transaction->product?->name ?? '-',
                    'amount' => (float) $transaction->amount,
                    'created_at' => $transaction->created_at?->toISOString(),
                    'status' => $transaction->status,
                ];
            })
            ->values();

        return Inertia::render('dashboard', [
            'dashboardStats' => $dashboardStats,
            'coinsBalance' => $coinsBalance,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
