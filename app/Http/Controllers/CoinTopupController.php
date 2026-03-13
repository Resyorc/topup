<?php

namespace App\Http\Controllers;

use App\Models\CoinTopup;
use App\Services\TripayService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CoinTopupController extends Controller
{
    public function index(Request $request)
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

        return Inertia::render('user/topup-saldo', [
            'coinsBalance' => (int) ($user->fresh()->coin_balance ?? 0),
        ]);
    }

    public function store(Request $request, TripayService $tripayService)
    {
        $user = $request->user();

        $validated = $request->validate([
            'amount' => 'required|integer|min:1000|max:1000000',
            'customer_whatsapp' => 'required|string|regex:/^\+?[0-9]{8,15}$/',
        ]);

        $qrisMethod = $this->resolveQrisMethod($tripayService);

        $merchantRef = 'CTP-' . strtoupper(Str::ulid());
        $expiredTime = time() + 3600;

        $orderItems = [[
            'sku' => 'KRYSTA-COIN',
            'name' => 'Top Up Krysta Coin',
            'price' => (int) $validated['amount'],
            'quantity' => 1,
        ]];

        $paymentResponse = $tripayService->createTransaction(
            $qrisMethod['code'],
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
            'payment_method' => $paymentResponse['payment_method'] ?? $qrisMethod['code'],
            'payment_name' => $paymentResponse['payment_name'] ?? $qrisMethod['name'],
            'payment_url' => $paymentResponse['checkout_url'] ?? null,
            'pay_code' => $paymentResponse['pay_code'] ?? null,
            'qr_url' => $paymentResponse['qr_url'] ?? null,
            'pay_url' => $paymentResponse['pay_url'] ?? null,
            'reference_id_provider' => $paymentResponse['reference'] ?? null,
            'expired_at' => now()->setTimestamp($expiredTime),
            'api_logs' => $paymentResponse,
        ]);

        return redirect()->route('invoice', ['invoice_id' => $merchantRef]);
    }

    private function resolveQrisMethod(TripayService $tripayService): array
    {
        $channels = $tripayService->getPaymentChannels();

        foreach ($channels as $channel) {
            if (!($channel['active'] ?? false)) {
                continue;
            }

            $group = strtoupper((string) ($channel['group'] ?? ''));
            $code = strtoupper((string) ($channel['code'] ?? ''));
            $name = strtoupper((string) ($channel['name'] ?? ''));

            if ($group === 'QRIS' || $code === 'QRIS' || str_contains($name, 'QRIS')) {
                return [
                    'code' => $channel['code'],
                    'name' => $channel['name'],
                ];
            }
        }

        abort(422, 'Metode pembayaran QRIS tidak tersedia saat ini.');
    }
}
