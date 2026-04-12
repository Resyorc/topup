<?php

namespace App\Http\Controllers;

use App\Models\MembershipOrder;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\TierService;
use App\Services\TripayService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MembershipController extends Controller
{
    private const TIER_ORDER = ['bronze', 'silver', 'gold', 'platinum'];

    public function index(Request $request)
    {
        $user = $request->user();

        // Expire pending orders yang sudah kedaluwarsa
        MembershipOrder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->update(['status' => 'expired']);

        $prices = $this->getMembershipPrices();
        $tierOrder = self::TIER_ORDER;
        $userTierIdx = array_search($user->tier, $tierOrder);

        // Tampilkan hanya tier yang lebih tinggi dari tier user saat ini
        $upgradable = [];
        foreach ($prices as $tier => $price) {
            $tierIdx = array_search($tier, $tierOrder);
            if ($tierIdx !== false && $tierIdx > $userTierIdx) {
                $upgradable[$tier] = $price;
            }
        }

        return Inertia::render('membership', [
            'currentTier' => $user->tier,
            'upgradable'  => $upgradable,
            'prices'      => $prices,
        ]);
    }

    public function checkout(Request $request, TripayService $tripayService)
    {
        $user = $request->user();

        $validated = $request->validate([
            'to_tier' => 'required|in:silver,gold,platinum',
        ]);

        $toTier = $validated['to_tier'];
        $tierOrder = self::TIER_ORDER;
        $userTierIdx = array_search($user->tier, $tierOrder);
        $toTierIdx = array_search($toTier, $tierOrder);

        // Pastikan user hanya bisa upgrade ke tier yang lebih tinggi
        if ($toTierIdx <= $userTierIdx) {
            return back()->withErrors(['to_tier' => 'Anda sudah berada di tier ini atau lebih tinggi.']);
        }

        $prices = $this->getMembershipPrices();
        $amount = $prices[$toTier] ?? 0;

        if ($amount <= 0) {
            return back()->withErrors(['to_tier' => 'Harga upgrade tidak valid.']);
        }

        // Buat via QRIS
        $qrisMethod = $this->resolveQrisMethod($tripayService);

        $merchantRef = 'MBR-' . strtoupper(Str::ulid());
        $expiredTime = time() + (24 * 60 * 60); // 24 jam

        $orderItems = [[
            'sku'      => 'MEMBERSHIP-' . strtoupper($toTier),
            'name'     => 'Upgrade Membership ke ' . ucfirst($toTier),
            'price'    => $amount,
            'quantity' => 1,
        ]];

        $paymentResponse = $tripayService->createTransaction(
            $qrisMethod['code'],
            $merchantRef,
            $amount,
            $user->name,
            $user->email,
            $user->phone ?? '08000000000',
            $orderItems,
            $expiredTime,
        );

        MembershipOrder::create([
            'invoice_id'     => $merchantRef,
            'user_id'        => $user->id,
            'from_tier'      => $user->tier,
            'to_tier'        => $toTier,
            'amount'         => $amount,
            'status'         => 'pending',
            'payment_method' => $paymentResponse['payment_method'] ?? $qrisMethod['code'],
            'payment_name'   => $paymentResponse['payment_name']   ?? $qrisMethod['name'],
            'payment_url'    => $paymentResponse['checkout_url']   ?? null,
            'pay_code'       => $paymentResponse['pay_code']       ?? null,
            'qr_url'         => $paymentResponse['qr_url']         ?? null,
            'pay_url'        => $paymentResponse['pay_url']        ?? null,
            'reference'      => $paymentResponse['reference']      ?? null,
            'expired_at'     => now()->setTimestamp($expiredTime),
            'api_logs'       => $paymentResponse,
        ]);

        AuditLogger::log(
            event: 'membership_upgrade',
            description: "User mengajukan upgrade membership ke {$toTier} — {$merchantRef}",
            subjectType: 'MembershipOrder',
            subjectId: $merchantRef,
            request: $request,
        );

        return redirect()->route('invoice', ['invoice_id' => $merchantRef]);
    }

    private function getMembershipPrices(): array
    {
        return [
            'silver'   => (int) Setting::get('membership_price_silver',   25_000),
            'gold'     => (int) Setting::get('membership_price_gold',     75_000),
            'platinum' => (int) Setting::get('membership_price_platinum', 150_000),
        ];
    }

    private function resolveQrisMethod(TripayService $tripayService): array
    {
        $channels = $tripayService->getPaymentChannels();

        foreach ($channels as $channel) {
            if (! ($channel['active'] ?? false)) {
                continue;
            }
            $group = strtoupper((string) ($channel['group'] ?? ''));
            $code  = strtoupper((string) ($channel['code'] ?? ''));
            $name  = strtoupper((string) ($channel['name'] ?? ''));

            if ($group === 'QRIS' || $code === 'QRIS' || str_contains($name, 'QRIS')) {
                return ['code' => $channel['code'], 'name' => $channel['name']];
            }
        }

        abort(422, 'Metode pembayaran QRIS tidak tersedia saat ini.');
    }
}
