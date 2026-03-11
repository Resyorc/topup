<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\TripayService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * Handle the incoming checkout request.
     */
    public function store(Request $request, TripayService $tripayService)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'customer_game_id' => 'required|string',
            'customer_zone_id' => 'nullable|string',
            'customer_whatsapp' => 'required|string|regex:/^\+?[0-9]{8,15}$/',
            'payment_method' => 'required|string',
            'customer_name' => 'nullable|string|max:100',
            'customer_email' => 'nullable|email',
            'qty' => 'nullable|integer|min:1|max:100',
        ]);

        $qty = $validated['qty'] ?? 1;
        $customerName = $validated['customer_name'] ?? 'Guest';
        $customerEmail = $validated['customer_email'] ?? 'guest@nebustore.com';

        $product = Product::findOrFail($validated['product_id']);

        if (!$product->is_available) {
            return response()->json(['error' => 'Product is currently unavailable.'], 400);
        }

        $merchantRef = 'INV-' . strtoupper(Str::ulid());
        
        $amount = (int) $product->price_sell * $qty;

        // Order items formatted for Tripay
        $orderItems = [
            [
                'sku'       => $product->provider_sku,
                'name'      => $product->game->name . ' - ' . $product->name,
                'price'     => (int) $product->price_sell,
                'quantity'  => $qty,
            ]
        ];

        try {
            $result = DB::transaction(function () use ($validated, $product, $qty, $merchantRef, $amount, $orderItems, $customerName, $customerEmail, $tripayService) {
                // Request transaction creation to Tripay
                $paymentResponse = $tripayService->createTransaction(
                    $validated['payment_method'],
                    $merchantRef,
                    $amount,
                    $customerName,
                    $customerEmail,
                    $validated['customer_whatsapp'],
                    $orderItems
                );

                // Record transaction locally as UNPAID
                $transaction = Transaction::create([
                    'invoice_id' => $merchantRef,
                    'user_id' => auth('web')->id() ?? auth()->id() ?? null,
                    'product_id' => $product->id,
                    'customer_game_id' => $validated['customer_game_id'],
                    'customer_zone_id' => $validated['customer_zone_id'] ?? null,
                    'customer_whatsapp' => $validated['customer_whatsapp'],
                    'customer_name' => $customerName,
                    'amount' => $amount,
                    'profit' => ($product->price_sell - $product->price_cost) * $qty,
                    'status' => 'pending', // Menunggu Pembayaran
                    'sn' => null,
                    'payment_url' => $paymentResponse['checkout_url'] ?? null,
                    'reference_id_provider' => $paymentResponse['reference'] ?? null,
                ]);

                return [
                    'transaction' => $transaction,
                    'paymentResponse' => $paymentResponse
                ];
            });


            return response()->json([
                'success' => true,
                'message' => 'Checkout successful.',
                'data' => [
                    'transaction' => $result['transaction'],
                    'payment' => $result['paymentResponse'], // Contains checkout_url, pay_code, etc.
                    'pay_code' => $result['paymentResponse']['pay_code'] ?? null,
                    'amount' => $amount,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Checkout Error: ' . $e->getMessage(), ['request' => $validated]);
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed.',
                'error' => $e->getMessage(),
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
            $feeCalc = $tripayService->calculateFee((int) $validated['amount'], $methodParam);
            
            // If requesting a specific method (method was provided)
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
                    'data' => [
                        'fee' => $customerFee,
                        'total' => $validated['amount'] + $customerFee
                    ]
                ]);
            }

            // If BULK requesting for all methods
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
                'data' => $bulkFees
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate fee: ' . $e->getMessage(),
            ], 500);
        }
    }
}
