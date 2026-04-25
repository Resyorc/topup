<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppNotification;
use App\Models\ErrorLog;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\AuditLogger;
use App\Services\CoinService;
use App\Services\TripayService;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Handle the incoming checkout request.
     */
    public function store(Request $request, TripayService $tripayService, CoinService $coinService, VoucherService $voucherService)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'customer_game_id' => 'required|string|max:50|regex:/^[a-zA-Z0-9._\-]+$/',
            'customer_zone_id' => 'nullable|string|max:20|regex:/^[a-zA-Z0-9_]+$/',
            'customer_whatsapp' => 'required|string|regex:/^\+?[0-9]{8,15}$/',
            'payment_method' => 'required|string',
            'customer_name' => 'nullable|string|max:100',
            'customer_email' => 'nullable|email',
            'qty' => 'nullable|integer|min:1|max:100',
            'promo_code' => 'nullable|string|max:50',
        ]);

        $qty = $validated['qty'] ?? 1;
        $authenticatedUser = auth()->user();
        $authenticatedUserId = $authenticatedUser?->id;
        $customerName = $validated['customer_name'] ?? 'Guest';
        $customerEmail = $validated['customer_email']
            ?? $authenticatedUser?->email
            ?? 'guest@nuvelo.com';

        $product = Product::with(['providerProducts' => function ($q) {
            $q->where('is_active', true)->orderBy('price', 'asc');
        }])->findOrFail($validated['product_id']);

        if (! $product->is_available) {
            return response()->json(['error' => 'Product is currently unavailable.'], 400);
        }

        $providerSku = $product->providerProducts->first()?->provider_sku;
        if (! $providerSku) {
            return response()->json(['error' => 'Produk tidak memiliki SKU provider aktif. Silakan hubungi admin.'], 400);
        }

        $basePrice = (int) ($product->price_sell ?? 0);

        // Harga efektif: flash_sale_price jika ada flash sale aktif, selain itu harga normal
        $isFlashSale = $product->flash_sale_price !== null
            && $product->flash_sale_ends_at !== null
            && $product->flash_sale_ends_at->gt(now());
        $effectivePrice = $isFlashSale ? (int) ceil($product->flash_sale_price) : $basePrice;

        // Cek stok flash sale
        if ($isFlashSale && $product->flash_sale_stock !== null) {
            if ($product->flash_sale_purchased >= $product->flash_sale_stock) {
                return response()->json(['success' => false, 'message' => 'Stok flash sale sudah habis.'], 422);
            }
        }

        // Cegah double order: early check di luar transaction untuk UX yang cepat.
        // Re-check dengan lockForUpdate dilakukan di dalam DB::transaction (lihat bawah).
        $existingTransaction = $this->findActiveDuplicateTransaction($product->id, $validated, $authenticatedUserId)
            ->latest()
            ->first();

        if ($existingTransaction) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu masih memiliki pesanan aktif untuk produk ini. Selesaikan atau tunggu pesanan sebelumnya expired.',
                'data' => [
                    'invoice_id' => $existingTransaction->invoice_id,
                    'status' => $existingTransaction->status,
                    'expired_at' => $existingTransaction->expired_at?->toDateTimeString(),
                ],
            ], 409);
        }

        $merchantRef = 'INV-'.strtoupper(Str::ulid());
        $amount = $effectivePrice * $qty;

        // ===== VOUCHER =====
        $discount = 0;
        $voucherCode = null;

        if (! empty($validated['promo_code'])) {
            $voucherResult = $voucherService->validate($validated['promo_code'], $amount);
            if (! $voucherResult['valid']) {
                return response()->json(['success' => false, 'message' => $voucherResult['message']], 422);
            }
            $discount = $voucherResult['discount'];
            $voucherCode = strtoupper(trim($validated['promo_code']));
        }

        $chargeAmount = $amount - $discount; // amount yang benar-benar ditagihkan

        // ===== COIN PAYMENT =====
        if ($validated['payment_method'] === 'COIN') {
            return $this->checkoutWithCoin(
                $validated, $product, $qty, $merchantRef, $amount, $chargeAmount,
                $discount, $voucherCode, $customerName, $authenticatedUserId, $coinService, $request, $effectivePrice, $providerSku
            );
        }

        // ===== TRIPAY PAYMENT =====
        $expiredAt = now()->addHour();
        $expiredTime = $expiredAt->timestamp; // Unix timestamp untuk Tripay API

        $orderItems = [
            [
                'sku' => $providerSku,
                'name' => $product->game->name.' - '.$product->name,
                'price' => $effectivePrice,
                'quantity' => $qty,
            ],
        ];

        $duplicateInfo = null;

        try {
            $result = DB::transaction(function () use ($validated, $product, $qty, $merchantRef, $amount, $chargeAmount, $discount, $voucherCode, $orderItems, $customerName, $customerEmail, $tripayService, $authenticatedUserId, $expiredTime, $expiredAt, $voucherService, $effectivePrice, $isFlashSale, $providerSku, &$duplicateInfo) {

                // Re-check double order di dalam transaction dengan lockForUpdate — cegah race condition.
                $duplicate = $this->findActiveDuplicateTransaction($product->id, $validated, $authenticatedUserId)
                    ->lockForUpdate()
                    ->first();

                if ($duplicate) {
                    $duplicateInfo = [
                        'invoice_id' => $duplicate->invoice_id,
                        'status' => $duplicate->status,
                        'expired_at' => $duplicate->expired_at?->toDateTimeString(),
                    ];

                    return null;
                }

                $paymentResponse = $tripayService->createTransaction(
                    $validated['payment_method'],
                    $merchantRef,
                    $chargeAmount,
                    $customerName,
                    $customerEmail,
                    $validated['customer_whatsapp'],
                    $orderItems,
                    $expiredTime
                );

                $fee = (int) ($paymentResponse['fee_customer'] ?? 0);

                $guestToken = $authenticatedUserId ? null : Str::random(48);

                $transaction = Transaction::create([
                    'invoice_id' => $merchantRef,
                    'user_id' => $authenticatedUserId,
                    'guest_token' => $guestToken,
                    'product_id' => $product->id,
                    'provider_sku' => $providerSku,
                    'customer_game_id' => $validated['customer_game_id'],
                    'customer_zone_id' => $validated['customer_zone_id'] ?? null,
                    'customer_whatsapp' => $validated['customer_whatsapp'],
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'amount' => $amount,
                    'fee' => $fee,
                    'discount' => $discount,
                    'voucher_code' => $voucherCode,
                    'profit' => ($effectivePrice - $product->price_cost) * $qty - $discount,
                    'status' => 'pending',
                    'sn' => null,
                    'payment_url' => $paymentResponse['checkout_url'] ?? null,
                    'reference_id_provider' => $paymentResponse['reference'] ?? null,
                    'expired_at' => $expiredAt,
                    'payment_method' => $paymentResponse['payment_method'] ?? null,
                    'payment_name' => $paymentResponse['payment_name'] ?? null,
                    'pay_code' => $paymentResponse['pay_code'] ?? null,
                    'qr_url' => $paymentResponse['qr_url'] ?? null,
                    'pay_url' => $paymentResponse['pay_url'] ?? null,
                    'api_logs' => $paymentResponse,
                ]);

                // Atomic: re-validasi + increment used_count dalam satu lock — cegah race condition voucher.
                if ($voucherCode) {
                    $voucherService->validateAndClaim($voucherCode, $amount, $request->user());
                }

                // Increment flash sale purchased counter
                if ($isFlashSale) {
                    $product->increment('flash_sale_purchased', $qty);
                }

                return [
                    'transaction' => $transaction,
                    'paymentResponse' => $paymentResponse,
                ];
            });

            if ($duplicateInfo !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu masih memiliki pesanan aktif untuk produk ini. Selesaikan atau tunggu pesanan sebelumnya expired.',
                    'data' => $duplicateInfo,
                ], 409);
            }

            AuditLogger::log(
                event: 'checkout',
                description: 'Checkout '.$product->name.' — '.$merchantRef,
                subjectType: 'Transaction',
                subjectId: $merchantRef,
                request: $request,
            );

            dispatch(SendWhatsAppNotification::orderPending($result['transaction']->load('product.game')))
                ->delay(now()->addSeconds(3));

            return response()->json([
                'success' => true,
                'message' => 'Checkout successful.',
                'data' => [
                    'transaction' => $result['transaction'],
                    'payment' => $result['paymentResponse'],
                    'pay_code' => $result['paymentResponse']['pay_code'] ?? null,
                    'amount' => $amount,
                    'expired_at' => $expiredAt->toDateTimeString(),
                    'guest_token' => $result['transaction']->guest_token,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Checkout Error: '.$e->getMessage(), ['request' => $validated]);

            // Voucher errors dari validateAndClaim — aman dikembalikan ke user
            if (str_starts_with($e->getMessage(), 'Voucher')
                || str_starts_with($e->getMessage(), 'Kode voucher')
                || str_starts_with($e->getMessage(), 'Minimum pembelian')) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            ErrorLog::create([
                'level'       => 'error',
                'message'     => 'Checkout Tripay gagal: '.$e->getMessage(),
                'exception'   => get_class($e),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'trace'       => mb_substr($e->getTraceAsString(), 0, 65535),
                'url'         => request()->fullUrl(),
                'method'      => 'POST',
                'ip'          => request()->ip(),
                'user_id'     => auth()->id(),
                'occurred_at' => now(),
            ]);

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
        int $chargeAmount,
        int $discount,
        ?string $voucherCode,
        string $customerName,
        ?int $authenticatedUserId,
        CoinService $coinService,
        \Illuminate\Http\Request $request,
        int $effectivePrice,
        string $providerSku
    ) {
        // Harus login untuk pakai coin
        if (! $authenticatedUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu harus login untuk menggunakan Krysta Coin.',
            ], 401);
        }

        $user = \App\Models\User::findOrFail($authenticatedUserId);

        // Harus verifikasi email untuk pakai coin
        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu harus verifikasi email terlebih dahulu untuk menggunakan Krysta Coin.',
            ], 403);
        }

        // Cek saldo sebelum masuk transaksi
        if (! $coinService->hasSufficientBalance($user, $chargeAmount)) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo Krysta Coin tidak cukup. Saldo kamu: '.$user->coin_balance.' Coins.',
            ], 400);
        }

        try {
            $transaction = DB::transaction(function () use ($validated, $product, $qty, $merchantRef, $amount, $chargeAmount, $discount, $voucherCode, $customerName, $authenticatedUserId, $user, $coinService, $effectivePrice, $request, $providerSku) {

                // Re-check double order di dalam transaction dengan lockForUpdate — cegah race condition.
                $duplicate = $this->findActiveDuplicateTransaction($product->id, $validated, $authenticatedUserId)
                    ->lockForUpdate()
                    ->first();

                if ($duplicate) {
                    throw new \Exception('DUPLICATE_ORDER');
                }

                // 1. Potong saldo coin — otomatis rollback kalau gagal
                $coinService->debit(
                    $user,
                    $chargeAmount,
                    'Topup '.$product->game->name.' - '.$product->name,
                    $merchantRef
                );

                // 2. Buat transaksi — langsung paid & processing karena sudah bayar
                $transaction = Transaction::create([
                    'invoice_id' => $merchantRef,
                    'user_id' => $authenticatedUserId,
                    'product_id' => $product->id,
                    'provider_sku' => $providerSku,
                    'customer_game_id' => $validated['customer_game_id'],
                    'customer_zone_id' => $validated['customer_zone_id'] ?? null,
                    'customer_whatsapp' => $validated['customer_whatsapp'],
                    'customer_name' => $customerName,
                    'customer_email' => $user->email,
                    'amount' => $amount,
                    'discount' => $discount,
                    'voucher_code' => $voucherCode,
                    'profit' => ($effectivePrice - $product->price_cost) * $qty - $discount,
                    'status' => 'processing', // langsung processing
                    'payment_status' => 'paid',       // langsung paid
                    'payment_method' => 'COIN',
                    'payment_name' => 'Krysta Coin',
                    'sn' => null,
                ]);

                // Atomic: re-validasi + increment used_count dalam satu lock — cegah race condition voucher.
                if ($voucherCode) {
                    app(VoucherService::class)->validateAndClaim($voucherCode, $amount, $request->user());
                }

                // Increment flash sale purchased counter
                $isFs = $product->flash_sale_price !== null
                    && $product->flash_sale_ends_at !== null
                    && $product->flash_sale_ends_at->gt(now());
                if ($isFs) {
                    $product->increment('flash_sale_purchased', $qty);
                }

                // 3. Kirim ke Digiflazz langsung
                $digiflazzService = app(\App\Services\DigiflazzService::class);
                $digiflazzService->createTransaction(
                    $providerSku,
                    $transaction->customer_game_id.($transaction->customer_zone_id ?? ''),
                    $transaction->invoice_id,
                );

                return $transaction;
            });

            AuditLogger::log(
                event: 'checkout',
                description: 'Checkout COIN '.$product->name.' — '.$merchantRef,
                subjectType: 'Transaction',
                subjectId: $merchantRef,
                request: $request,
            );

            dispatch(SendWhatsAppNotification::paymentReceived($transaction->load('product.game')))
                ->delay(now()->addSeconds(3));

            return response()->json([
                'success' => true,
                'message' => 'Checkout berhasil! Topup sedang diproses.',
                'data' => [
                    'transaction' => $transaction,
                    'amount' => $amount,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Coin Checkout Error: '.$e->getMessage(), ['request' => $validated]);

            if ($e->getMessage() === 'DUPLICATE_ORDER') {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu masih memiliki pesanan aktif untuk produk ini. Selesaikan atau tunggu pesanan sebelumnya expired.',
                ], 409);
            }

            if ($e->getMessage() === 'Saldo coin tidak cukup.') {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }

            // Voucher errors dari validateAndClaim — aman dikembalikan ke user
            if (str_starts_with($e->getMessage(), 'Voucher')
                || str_starts_with($e->getMessage(), 'Kode voucher')
                || str_starts_with($e->getMessage(), 'Minimum pembelian')) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            ErrorLog::create([
                'level'       => 'critical',
                'message'     => 'Checkout COIN gagal: '.$e->getMessage(),
                'exception'   => get_class($e),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'trace'       => mb_substr($e->getTraceAsString(), 0, 65535),
                'url'         => request()->fullUrl(),
                'method'      => 'POST',
                'ip'          => request()->ip(),
                'user_id'     => $authenticatedUserId,
                'occurred_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Checkout gagal. Silakan coba lagi.',
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
            'method' => 'nullable|string',
        ]);

        try {
            $methodParam = $validated['method'] ?? null;
            $feeCalc = $tripayService->calculateFee((int) $validated['amount'], $methodParam);

            if ($methodParam) {
                $feeData = $feeCalc[0] ?? null;

                if (! $feeData) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot calculate fee from Tripay.',
                    ], 400);
                }

                $customerFee = $feeData['total_fee']['customer'] ?? 0;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'fee' => $customerFee,
                        'total' => $validated['amount'] + $customerFee,
                    ],
                ]);
            }

            $bulkFees = [];
            foreach ($feeCalc as $feeData) {
                $code = $feeData['code'] ?? null;
                $customerFee = $feeData['total_fee']['customer'] ?? 0;
                if ($code) {
                    $bulkFees[$code] = $customerFee;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $bulkFees,
            ]);

        } catch (\Exception $e) {
            Log::error('Calculate Fee Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate fee. Please try again.',
            ], 500);
        }
    }

    private function findActiveDuplicateTransaction(int $productId, array $validated, ?int $authenticatedUserId)
    {
        return Transaction::where('product_id', $productId)
            ->where('customer_game_id', $validated['customer_game_id'])
            ->when(
                $authenticatedUserId,
                fn ($query) => $query->where('user_id', $authenticatedUserId),
                fn ($query) => $query
                    ->whereNull('user_id')
                    ->where('customer_whatsapp', $validated['customer_whatsapp'])
            )
            ->whereIn('status', ['pending', 'processing'])
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
            });
    }
}
