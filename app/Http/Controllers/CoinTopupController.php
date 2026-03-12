<?php

namespace App\Http\Controllers;

use App\Models\CoinTopup;
use App\Services\TripayService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CoinTopupController extends Controller
{
    public function index(Request $request, TripayService $tripayService)
    {
        $user = $request->user();

        CoinTopup::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->update([
                'status' => 'expired',
                'failure_reason' => 'Pembayaran melewati batas waktu (expired).',
                'updated_at' => now(),
            ]);

        $activeTopup = CoinTopup::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        $paymentMethods = [];

        try {
            $channels = $tripayService->getPaymentChannels();

            foreach ($channels as $channel) {
                if (!($channel['active'] ?? false)) {
                    continue;
                }

                $group = $channel['group'] ?? 'Lainnya';
                $paymentMethods[$group][] = [
                    'id' => $channel['code'],
                    'name' => $channel['name'],
                    'icon_url' => $channel['icon_url'] ?? null,
                    'minimum_amount' => (int) ($channel['minimum_amount'] ?? 0),
                ];
            }
        } catch (\Throwable $exception) {
            $paymentMethods = [];
        }

        return Inertia::render('user/topup-saldo', [
            'coinsBalance' => (int) ($user->fresh()->coin_balance ?? 0),
            'paymentMethods' => $paymentMethods,
            'activeTopup' => $activeTopup ? [
                'invoice_id' => $activeTopup->invoice_id,
                'amount' => $activeTopup->amount,
                'status' => $activeTopup->status,
                'payment_name' => $activeTopup->payment_name,
                'payment_url' => $activeTopup->payment_url,
                'pay_code' => $activeTopup->pay_code,
                'qr_url' => $activeTopup->qr_url,
                'pay_url' => $activeTopup->pay_url,
                'expired_at' => $activeTopup->expired_at?->format('d M Y H:i:s'),
                'failure_reason' => $activeTopup->failure_reason,
            ] : null,
        ]);
    }

    public function store(Request $request, TripayService $tripayService)
    {
        $user = $request->user();

        $validated = $request->validate([
            'amount' => 'required|integer|min:1000|max:1000000',
            'payment_method' => 'required|string|max:50',
            'customer_whatsapp' => 'required|string|regex:/^\+?[0-9]{8,15}$/',
        ]);

        $merchantRef = 'CTP-' . strtoupper(Str::ulid());
        $expiredTime = time() + 3600;

        $orderItems = [[
            'sku' => 'KRYSTA-COIN',
            'name' => 'Top Up Krysta Coin',
            'price' => (int) $validated['amount'],
            'quantity' => 1,
        ]];

        $paymentResponse = $tripayService->createTransaction(
            $validated['payment_method'],
            $merchantRef,
            (int) $validated['amount'],
            $user->name,
            $user->email,
            $validated['customer_whatsapp'],
            $orderItems,
            $expiredTime,
        );

        CoinTopup::create([
            'user_id' => $user->id,
            'invoice_id' => $merchantRef,
            'amount' => (int) $validated['amount'],
            'status' => 'pending',
            'customer_whatsapp' => $validated['customer_whatsapp'],
            'payment_method' => $paymentResponse['payment_method'] ?? $validated['payment_method'],
            'payment_name' => $paymentResponse['payment_name'] ?? null,
            'payment_url' => $paymentResponse['checkout_url'] ?? null,
            'pay_code' => $paymentResponse['pay_code'] ?? null,
            'qr_url' => $paymentResponse['qr_url'] ?? null,
            'pay_url' => $paymentResponse['pay_url'] ?? null,
            'reference_id_provider' => $paymentResponse['reference'] ?? null,
            'expired_at' => now()->setTimestamp($expiredTime),
            'api_logs' => $paymentResponse,
        ]);

        return redirect()->route('dashboard.coin-topups.index');
    }
}
