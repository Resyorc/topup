<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Voucher;
use App\Services\TierService;
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

        $tierService   = app(TierService::class);
        $currentTier   = $user->tier ?? 'bronze';
        $tierInfo = [
            'current'    => $currentTier,
            'multiplier' => $tierService->getMultiplier($currentTier),
        ];

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

        // Tier yang boleh dilihat user: dari bronze sampai tier mereka
        $tierOrder     = ['bronze', 'silver', 'gold', 'platinum'];
        $userTierIndex = array_search($currentTier, $tierOrder);
        $eligibleTiers = array_slice($tierOrder, 0, $userTierIndex + 1);

        $usedCodes = Transaction::where('user_id', $user->id)
            ->whereNotNull('voucher_code')
            ->whereIn('status', ['success', 'processing', 'paid', 'pending'])
            ->pluck('voucher_code')
            ->unique()
            ->values()
            ->all();

        $promoVouchers = Voucher::where('is_active', true)
            ->where('is_public', true)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->where(fn ($q) => $q->whereNull('min_tier')->orWhereIn('min_tier', $eligibleTiers))
            ->orderByRaw("CASE min_tier WHEN 'platinum' THEN 1 WHEN 'gold' THEN 2 WHEN 'silver' THEN 3 WHEN 'bronze' THEN 4 ELSE 5 END ASC")
            ->get()
            ->map(fn (Voucher $v) => [
                'code'         => $v->code,
                'type'         => $v->type,
                'value'        => (float) $v->value,
                'max_discount' => $v->max_discount ? (int) $v->max_discount : null,
                'min_amount'   => (int) $v->min_amount,
                'valid_until'  => $v->valid_until?->toISOString(),
                'min_tier'     => $v->min_tier,
                'used'         => in_array($v->code, $usedCodes),
            ])
            ->values();

        return Inertia::render('dashboard', [
            'dashboardStats'     => $dashboardStats,
            'coinsBalance'       => $coinsBalance,
            'recentTransactions' => $recentTransactions,
            'tierInfo'           => $tierInfo,
            'promoVouchers'      => $promoVouchers,
        ]);
    }

    public function memberClub(Request $request)
    {
        $user        = $request->user();
        $tierService = app(TierService::class);
        $currentTier = $user->tier ?? 'bronze';

        return Inertia::render('user/member-club', [
            'tierInfo' => [
                'current'    => $currentTier,
                'multiplier' => $tierService->getMultiplier($currentTier),
            ],
        ]);
    }
}
