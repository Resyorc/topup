<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\TripayService;
use App\Services\CoinService;
use App\Services\DigiflazzService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckoutController extends Controller
{
    /**
     * Handle the incoming checkout request.
     */
    public function store(Request $request, TripayService $tripayService, CoinService $coinService)
    {
        $validated = $request->validate([
            'product_id'       => 'required|exists:products,id',
            'customer_game_id' => 'required|string',
            'customer_zone_id' => 'nullable|string',
            'customer_whatsapp'=> 'required|string|regex:/^\+?[0-9]{8,15}$/',
            'payment_method'   => 'required|string',
            'customer_name'    => 'nullable|string|max:100',
            'customer_email'   => 'nullable|email',
            'qty'              => 'nullable|integer|min:1|max:100',
        ]);

        $qty             = $validated['qty'] ?? 1;
        $customerName    = $validated['customer_name'] ?? 'Guest';
        $customerEmail   = $validated['customer_email'] ?? 'guest@nuvelo.com';
        $authenticatedUserId = auth()->id();

        $product = Product::findOrFail($validated['product_id']);

        if (!$product->is_available) {
            return response()->json(['error' => 'Product is currently unavailable.'], 400);
        }

        // Cegah double order: hanya untuk user yang login
        if ($authenticatedUserId) {
            $existingTransaction = Transaction::where('product_id', $product->id)
                ->where('customer_game_id', $validated['customer_game_id'])
                ->where('user_id', $authenticatedUserId)
                ->whereIn('status', ['pending', 'processing'])
                ->where(function ($q) {
                    $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
                })
                ->latest()
                ->first();

            if ($existingTransaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu masih memiliki pesanan aktif untuk produk ini. Selesaikan atau tunggu pesanan sebelumnya expired.',
                    'data'    => [
                        'invoice_id' => $existingTransaction->invoice_id,
                        'status'     => $existingTransaction->status,
                        'expired_at' => $existingTransaction->expired_at?->toDateTimeString(),
                    ],
                ], 409);
            }
        }

        $merchantRef = 'INV-' . strtoupper(Str::ulid());
        $amount      = (int) $product->price_sell * $qty;

        // ===== COIN PAYMENT =====
        if ($validated['payment_method'] === 'COIN') {
            return $this->checkoutWithCoin(
                $validated, $product, $qty, $merchantRef, $amount,
                $customerName, $authenticatedUserId, $coinService
            );
        }

        // ===== TRIPAY PAYMENT =====
        $expiredTime = time() + (1 * 60 * 60);

        $orderItems = [
            [
                'sku'      => $product->provider_sku,
                'name'     => $product->game->name . ' - ' . $product->name,
                'price'    => (int) $product->price_sell,
                'quantity' => $qty,
            ]
        ];

        try {
            $result = DB::transaction(function () use ($validated, $product, $qty, $merchantRef, $amount, $orderItems, $customerName, $customerEmail, $tripayService, $authenticatedUserId, $expiredTime) {

                $paymentResponse = $tripayService->createTransaction(
                    $validated['payment_method'],
                    $merchantRef,
                    $amount,
                    $customerName,
                    $customerEmail,
                    $validated['customer_whatsapp'],
                    $orderItems,
                    $expiredTime
                );

                $fee = (int) ($paymentResponse['fee_customer'] ?? 0);

                $transaction = Transaction::create([
                    'invoice_id'             => $merchantRef,
                    'user_id'                => $authenticatedUserId,
                    'product_id'             => $product->id,
                    'customer_game_id'       => $validated['customer_game_id'],
                    'customer_zone_id'       => $validated['customer_zone_id'] ?? null,
                    'customer_whatsapp'      => maskPhoneNumber($validated['customer_whatsapp']),
                    'customer_name'          => $customerName,
                    'customer_email'         => $customerEmail,
                    'amount'                 => $amount,
                    'fee'                    => $fee,
                    'profit'                 => ($product->price_sell - $product->price_cost) * $qty,
                    'status'                 => 'pending',
                    'sn'                     => null,
                    'payment_url'            => $paymentResponse['checkout_url'] ?? null,
                    'reference_id_provider'  => $paymentResponse['reference'] ?? null,
                    'expired_at'             => Carbon::createFromTimestamp($expiredTime),
                    'payment_method'         => $paymentResponse['payment_method'] ?? null,
                    'payment_name'           => $paymentResponse['payment_name'] ?? null,
                    'pay_code'               => $paymentResponse['pay_code'] ?? null,
                    'qr_url'                 => $paymentResponse['qr_url'] ?? null,
                    'pay_url'                => $paymentResponse['pay_url'] ?? null,
                    'api_logs'               => $paymentResponse,
                ]);

                return [
                    'transaction'    => $transaction,
                    'paymentResponse'=> $paymentResponse
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Checkout successful.',
                'data'    => [
                    'transaction' => $result['transaction'],
                    'payment'     => $result['paymentResponse'],
                    'pay_code'    => $result['paymentResponse']['pay_code'] ?? null,
                    'amount'      => $amount,
                    'expired_at'  => Carbon::createFromTimestamp($expiredTime)->toDateTimeString(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Checkout Error: ' . $e->getMessage(), ['request' => $validated]);
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle checkout menggunakan Krysta Coin.
     * Coin 1:1 dengan Rupiah — langsung proses topup via Digiflazz.
     */
    private function checkoutWithCoin(
        array $validated,
        Product $product,
        int $qty,
        string $merchantRef,
        int $amount,
        string $customerName,
        ?int $authenticatedUserId,
        CoinService $coinService,
    ) {
        // Harus login untuk pakai coin
        if (!$authenticatedUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu harus login untuk menggunakan Krysta Coin.',
            ], 401);
        }

        $user = \App\Models\User::findOrFail($authenticatedUserId);

        // Cek saldo sebelum masuk transaksi
        if (!$coinService->hasSufficientBalance($user, $amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo Krysta Coin tidak cukup. Saldo kamu: ' . $user->coin_balance . ' Coins.',
            ], 400);
        }

        try {
            $transaction = DB::transaction(function () use ($validated, $product, $qty, $merchantRef, $amount, $customerName, $authenticatedUserId, $user, $coinService) {

                // 1. Potong saldo coin — otomatis rollback kalau gagal
                $coinService->debit(
                    $user,
                    $amount,
                    'Topup ' . $product->game->name . ' - ' . $product->name,
                    $merchantRef
                );

                // 2. Buat transaksi — langsung paid & processing karena sudah bayar
                $transaction = Transaction::create([
                    'invoice_id'        => $merchantRef,
                    'user_id'           => $authenticatedUserId,
                    'product_id'        => $product->id,
                    'customer_game_id'  => $validated['customer_game_id'],
                    'customer_zone_id'  => $validated['customer_zone_id'] ?? null,
                    'customer_whatsapp' => maskPhoneNumber($validated['customer_whatsapp']),
                    'customer_name'     => $customerName,
                    'customer_email'    => $user->email,
                    'amount'            => $amount,
                    'profit'            => ($product->price_sell - $product->price_cost) * $qty,
                    'status'            => 'processing', // langsung processing
                    'payment_status'    => 'paid',       // langsung paid
                    'payment_method'    => 'COIN',
                    'payment_name'      => 'Krysta Coin',
                    'sn'                => null,
                ]);

                // 3. Kirim ke Digiflazz langsung
                $digiflazzService = app(\App\Services\DigiflazzService::class);
                $digiflazzService->createTransaction(
                    $product->provider_sku,
                    $transaction->customer_game_id . ($transaction->customer_zone_id ?? ''),
                    $transaction->invoice_id,
                );

                return $transaction;
            });

            return response()->json([
                'success' => true,
                'message' => 'Checkout berhasil! Topup sedang diproses.',
                'data'    => [
                    'transaction' => $transaction,
                    'amount'      => $amount,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Coin Checkout Error: ' . $e->getMessage(), ['request' => $validated]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() === 'Saldo coin tidak cukup.'
                    ? $e->getMessage()
                    : 'Checkout gagal. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Calculate Tripay Fee dynamically.
     */
    public function calculateFee(Request $request, TripayService $tripayService)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'nullable|string'
        ]);

        try {
            $methodParam = $validated['method'] ?? null;
            $feeCalc     = $tripayService->calculateFee((int) $validated['amount'], $methodParam);

            if ($methodParam) {
                $feeData = $feeCalc[0] ?? null;

                if (!$feeData) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot calculate fee from Tripay.',
                    ], 400);
                }

                $customerFee = $feeData['total_fee']['customer'] ?? 0;

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'fee'   => $customerFee,
                        'total' => $validated['amount'] + $customerFee
                    ]
                ]);
            }

            $bulkFees = [];
            foreach ($feeCalc as $feeData) {
                $code        = $feeData['code'] ?? null;
                $customerFee = $feeData['total_fee']['customer'] ?? 0;
                if ($code) {
                    $bulkFees[$code] = $customerFee;
                }
            }

            return response()->json([
                'success' => true,
                'data'    => $bulkFees
            ]);

        } catch (\Exception $e) {
            Log::error('Calculate Fee Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate fee. Please try again.',
            ], 500);
        }
    }
}