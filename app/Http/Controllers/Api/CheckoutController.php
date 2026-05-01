<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessFulfilmentJob;
use App\Jobs\SendWhatsAppNotification;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\AuditLogger;
use App\Services\CoinService;
use App\Services\OperationalLogger;
use App\Services\TripayService;
use App\Services\UserIdCheckService;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Handle the incoming checkout request.
     */
    public function store(
        Request $request,
        TripayService $tripayService,
        CoinService $coinService,
        VoucherService $voucherService,
        UserIdCheckService $userIdCheckService
    ) {
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
        $customerEmail = $validated['customer_email']
            ?? $authenticatedUser?->email
            ?? 'guest@nuvelo.com';

        $product = Product::with([
            'game',
            'providerProducts' => function ($q) {
                $q->where('is_active', true)->orderBy('price', 'asc');
            },
        ])->findOrFail($validated['product_id']);

        if (! $product->is_available) {
            return response()->json(['error' => 'Product is currently unavailable.'], 400);
        }

        $selectedProvider = $product->providerProducts->first();
        $providerSku = $selectedProvider?->provider_sku;
        $providerName = $selectedProvider?->provider_name ?? 'digiflazz';

        if (! $providerSku) {
            return response()->json(['error' => 'Produk tidak memiliki SKU provider aktif. Silakan hubungi admin.'], 400);
        }

        $accountCheck = $this->validateCustomerAccount($product, $validated, $userIdCheckService);
        if (! $accountCheck['success']) {
            return response()->json([
                'success' => false,
                'message' => $accountCheck['message'],
            ], 422);
        }

        $customerName = $accountCheck['nickname']
            ?? $validated['customer_name']
            ?? $authenticatedUser?->name
            ?? 'Guest';

        $basePrice = (int) ($product->price_sell ?? 0);

        $isFlashSale = $product->flash_sale_price !== null
            && $product->flash_sale_ends_at !== null
            && $product->flash_sale_ends_at->gt(now());
        $effectivePrice = $isFlashSale ? (int) ceil($product->flash_sale_price) : $basePrice;

        if ($isFlashSale && $product->flash_sale_stock !== null) {
            if ($product->flash_sale_purchased >= $product->flash_sale_stock) {
                return response()->json(['success' => false, 'message' => 'Stok flash sale sudah habis.'], 422);
            }
        }

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

        $chargeAmount = $amount - $discount;
        if ($chargeAmount <= 0) {
            return response()->json(['success' => false, 'message' => 'Total pembayaran tidak valid.'], 422);
        }

        if ($validated['payment_method'] === 'COIN') {
            return $this->checkoutWithCoin(
                $validated,
                $product,
                $qty,
                $merchantRef,
                $amount,
                $chargeAmount,
                $discount,
                $voucherCode,
                $customerName,
                $authenticatedUserId,
                $coinService,
                $request,
                $effectivePrice,
                $providerSku,
                $providerName,
                $isFlashSale
            );
        }

        $expiredAt = now()->addHour();
        $expiredTime = $expiredAt->timestamp;
        $idempotencyKey = $this->makeIdempotencyKey($product->id, $validated, $authenticatedUserId);
        $duplicateInfo = null;
        $paymentResponse = null;

        $orderItems = [
            [
                'sku' => $providerSku,
                'name' => $product->game->name.' - '.$product->name,
                'price' => $chargeAmount,
                'quantity' => 1,
            ],
        ];

        try {
            $transaction = Cache::lock('checkout:'.$idempotencyKey, 10)->block(5, function () use ($validated, $product, $qty, $merchantRef, $amount, $discount, $voucherCode, $customerName, $customerEmail, $authenticatedUserId, $expiredAt, $voucherService, $effectivePrice, $isFlashSale, $providerSku, $providerName, $idempotencyKey, &$duplicateInfo, $request) {
                return DB::transaction(function () use ($validated, $product, $qty, $merchantRef, $amount, $discount, $voucherCode, $customerName, $customerEmail, $authenticatedUserId, $expiredAt, $voucherService, $effectivePrice, $isFlashSale, $providerSku, $providerName, $idempotencyKey, &$duplicateInfo, $request) {
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

                    $guestToken = $authenticatedUserId ? null : Str::random(48);

                    $transaction = Transaction::create([
                        'invoice_id' => $merchantRef,
                        'user_id' => $authenticatedUserId,
                        'guest_token' => $guestToken,
                        'idempotency_key' => $idempotencyKey,
                        'product_id' => $product->id,
                        'provider_sku' => $providerSku,
                        'provider_name' => $providerName,
                        'customer_game_id' => $validated['customer_game_id'],
                        'customer_zone_id' => $validated['customer_zone_id'] ?? null,
                        'customer_whatsapp' => $validated['customer_whatsapp'],
                        'customer_name' => $customerName,
                        'customer_email' => $customerEmail,
                        'amount' => $amount,
                        'fee' => 0,
                        'discount' => $discount,
                        'voucher_code' => $voucherCode,
                        'profit' => ($effectivePrice - $product->price_cost) * $qty - $discount,
                        'status' => 'pending',
                        'payment_status' => 'unpaid',
                        'fulfilment_status' => 'pending',
                        'sn' => null,
                        'expired_at' => $expiredAt,
                    ]);

                    if ($voucherCode) {
                        $voucherService->validateAndClaim($voucherCode, $amount, $request->user());
                    }

                    $this->claimFlashSaleStock($product, $qty, $isFlashSale);

                    return $transaction;
                });
            });

            if ($duplicateInfo !== null || ! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu masih memiliki pesanan aktif untuk produk ini. Selesaikan atau tunggu pesanan sebelumnya expired.',
                    'data' => $duplicateInfo,
                ], 409);
            }

            try {
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

                $transaction->update([
                    'fee' => (int) ($paymentResponse['fee_customer'] ?? 0),
                    'payment_url' => $paymentResponse['checkout_url'] ?? null,
                    'reference_id_provider' => $paymentResponse['reference'] ?? null,
                    'payment_method' => $paymentResponse['payment_method'] ?? $validated['payment_method'],
                    'payment_name' => $paymentResponse['payment_name'] ?? null,
                    'pay_code' => $paymentResponse['pay_code'] ?? null,
                    'qr_url' => $paymentResponse['qr_url'] ?? null,
                    'pay_url' => $paymentResponse['pay_url'] ?? null,
                    'api_logs' => array_merge($paymentResponse, ['tripay' => $paymentResponse]),
                    'failure_reason' => null,
                ]);
                $transaction->refresh();
            } catch (\Exception $e) {
                $transaction->update([
                    'status' => 'failed',
                    'payment_status' => 'unpaid',
                    'fulfilment_status' => 'failed',
                    'failure_reason' => 'Invoice Tripay belum berhasil dibuat: '.$e->getMessage(),
                ]);

                throw $e;
            }

            AuditLogger::log(
                event: 'checkout',
                description: 'Checkout '.$product->name.' - '.$merchantRef,
                subjectType: 'Transaction',
                subjectId: $merchantRef,
                request: $request,
            );

            dispatch(SendWhatsAppNotification::orderPending($transaction->load('product.game')))
                ->delay(now()->addSeconds(3));

            return response()->json([
                'success' => true,
                'message' => 'Checkout successful.',
                'data' => [
                    'transaction' => $transaction,
                    'payment' => $paymentResponse,
                    'pay_code' => $paymentResponse['pay_code'] ?? null,
                    'amount' => $amount,
                    'expired_at' => $expiredAt->toDateTimeString(),
                    'guest_token' => $transaction->guest_token,
                ],
            ]);

        } catch (\Exception $e) {
            OperationalLogger::error('Checkout Tripay gagal', [
                'request' => $validated,
                'product_id' => $validated['product_id'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
            ], $e, $request, 'payments');

            if ($e->getMessage() === 'FLASH_SALE_SOLD_OUT') {
                return response()->json(['success' => false, 'message' => 'Stok flash sale sudah habis.'], 422);
            }

            if (str_starts_with($e->getMessage(), 'Voucher')
                || str_starts_with($e->getMessage(), 'Kode voucher')
                || str_starts_with($e->getMessage(), 'Minimum pembelian')) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Checkout failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle checkout menggunakan Krysta Coin.
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
        Request $request,
        int $effectivePrice,
        string $providerSku,
        string $providerName,
        bool $isFlashSale
    ) {
        if (! $authenticatedUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu harus login untuk menggunakan Krysta Coin.',
            ], 401);
        }

        $user = \App\Models\User::findOrFail($authenticatedUserId);

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu harus verifikasi email terlebih dahulu untuk menggunakan Krysta Coin.',
            ], 403);
        }

        if (! $coinService->hasSufficientBalance($user, $chargeAmount)) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo Krysta Coin tidak cukup. Saldo kamu: '.$user->coin_balance.' Coins.',
            ], 400);
        }

        $idempotencyKey = $this->makeIdempotencyKey($product->id, $validated, $authenticatedUserId);

        try {
            $transaction = Cache::lock('checkout:'.$idempotencyKey, 10)->block(5, function () use ($validated, $product, $qty, $merchantRef, $amount, $chargeAmount, $discount, $voucherCode, $customerName, $authenticatedUserId, $user, $coinService, $effectivePrice, $request, $providerSku, $providerName, $isFlashSale, $idempotencyKey) {
                return DB::transaction(function () use ($validated, $product, $qty, $merchantRef, $amount, $chargeAmount, $discount, $voucherCode, $customerName, $authenticatedUserId, $user, $coinService, $effectivePrice, $request, $providerSku, $providerName, $isFlashSale, $idempotencyKey) {
                    $duplicate = $this->findActiveDuplicateTransaction($product->id, $validated, $authenticatedUserId)
                        ->lockForUpdate()
                        ->first();

                    if ($duplicate) {
                        throw new \Exception('DUPLICATE_ORDER');
                    }

                    $coinService->debit(
                        $user,
                        $chargeAmount,
                        'Topup '.$product->game->name.' - '.$product->name,
                        $merchantRef
                    );

                    $transaction = Transaction::create([
                        'invoice_id' => $merchantRef,
                        'user_id' => $authenticatedUserId,
                        'idempotency_key' => $idempotencyKey,
                        'product_id' => $product->id,
                        'provider_sku' => $providerSku,
                        'provider_name' => $providerName,
                        'customer_game_id' => $validated['customer_game_id'],
                        'customer_zone_id' => $validated['customer_zone_id'] ?? null,
                        'customer_whatsapp' => $validated['customer_whatsapp'],
                        'customer_name' => $customerName,
                        'customer_email' => $user->email,
                        'amount' => $amount,
                        'fee' => 0,
                        'discount' => $discount,
                        'voucher_code' => $voucherCode,
                        'profit' => ($effectivePrice - $product->price_cost) * $qty - $discount,
                        'status' => 'processing',
                        'payment_status' => 'paid',
                        'fulfilment_status' => 'pending',
                        'payment_method' => 'COIN',
                        'payment_name' => 'Krysta Coin',
                        'sn' => null,
                    ]);

                    if ($voucherCode) {
                        app(VoucherService::class)->validateAndClaim($voucherCode, $amount, $request->user());
                    }

                    $this->claimFlashSaleStock($product, $qty, $isFlashSale);

                    return $transaction;
                });
            });

            AuditLogger::log(
                event: 'checkout',
                description: 'Checkout COIN '.$product->name.' - '.$merchantRef,
                subjectType: 'Transaction',
                subjectId: $merchantRef,
                request: $request,
            );

            ProcessFulfilmentJob::dispatch($transaction->invoice_id)->afterCommit();

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
            OperationalLogger::critical('Checkout COIN gagal', [
                'request' => $validated,
                'product_id' => $validated['product_id'] ?? null,
                'user_id' => $authenticatedUserId,
            ], $e, $request, 'payments');

            if ($e->getMessage() === 'DUPLICATE_ORDER') {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu masih memiliki pesanan aktif untuk produk ini. Selesaikan atau tunggu pesanan sebelumnya expired.',
                ], 409);
            }

            if ($e->getMessage() === 'FLASH_SALE_SOLD_OUT') {
                return response()->json(['success' => false, 'message' => 'Stok flash sale sudah habis.'], 422);
            }

            if ($e->getMessage() === 'Saldo coin tidak cukup.') {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }

            if (str_starts_with($e->getMessage(), 'Voucher')
                || str_starts_with($e->getMessage(), 'Kode voucher')
                || str_starts_with($e->getMessage(), 'Minimum pembelian')) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

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
            OperationalLogger::error('Calculate Fee Error', [
                'amount' => $validated['amount'] ?? null,
                'method' => $validated['method'] ?? null,
            ], $e, $request, 'payments');

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
            ->where(function ($query) use ($validated) {
                $zoneId = $validated['customer_zone_id'] ?? null;

                if ($zoneId !== null && $zoneId !== '') {
                    $query->where('customer_zone_id', $zoneId);

                    return;
                }

                $query->whereNull('customer_zone_id')
                    ->orWhere('customer_zone_id', '');
            })
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

    private function validateCustomerAccount(Product $product, array $validated, UserIdCheckService $userIdCheckService): array
    {
        $product->loadMissing('game');
        $game = $product->game;

        if (! $game) {
            return ['success' => false, 'message' => 'Game produk tidak ditemukan.'];
        }

        $config = (array) config("services.user_id_check.games.{$game->slug}", []);
        $requiresZone = (bool) ($config['need_zone'] ?? false);

        if ($requiresZone && empty($validated['customer_zone_id'])) {
            return ['success' => false, 'message' => 'Server / Zone ID wajib diisi untuk game ini.'];
        }

        if ($config === []) {
            return [
                'success' => true,
                'nickname' => $validated['customer_name'] ?? null,
            ];
        }

        $result = $userIdCheckService->check(
            $game->slug,
            $validated['customer_game_id'],
            $validated['customer_zone_id'] ?? null,
        );

        if (! ($result['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'User ID tidak valid.',
            ];
        }

        return [
            'success' => true,
            'nickname' => $result['nickname'] ?? $validated['customer_name'] ?? null,
        ];
    }

    private function makeIdempotencyKey(int $productId, array $validated, ?int $authenticatedUserId): string
    {
        $actor = $authenticatedUserId
            ? 'user:'.$authenticatedUserId
            : 'guest:'.preg_replace('/[^0-9]/', '', $validated['customer_whatsapp']);

        return hash('sha256', implode('|', [
            'checkout',
            $actor,
            $productId,
            $validated['customer_game_id'],
            $validated['customer_zone_id'] ?? '',
            $validated['payment_method'],
        ]));
    }

    private function claimFlashSaleStock(Product $product, int $qty, bool $isFlashSale): void
    {
        if (! $isFlashSale) {
            return;
        }

        $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

        if ($lockedProduct->flash_sale_stock !== null
            && ($lockedProduct->flash_sale_purchased + $qty) > $lockedProduct->flash_sale_stock) {
            throw new \Exception('FLASH_SALE_SOLD_OUT');
        }

        $lockedProduct->increment('flash_sale_purchased', $qty);
    }
}
