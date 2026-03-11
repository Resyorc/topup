<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Transaction;

class InvoiceController extends Controller
{
    public function show(Request $request)
    {
        $invoiceId = $request->query('invoice_id');
        $invoiceData = null;

        if ($invoiceId) {
            $transaction = Transaction::with(['product.game', 'user'])
                ->where('invoice_id', $invoiceId)
                ->first();


            if ($transaction) {
                // Map the database format to what the frontend expects
                $invoiceData = [
                    'invoice_no' => $transaction->invoice_id,
                    'whatsapp' => maskPhoneNumber($transaction->customer_whatsapp),
                    'status' => $transaction->status, // pending, paid, processing, success, failed
                    'payment_status' => $transaction->payment_status,
                    'method' => $transaction->payment_method ? 'Gateway / Payment URL' : 'Manual',
                    'created_at' => $transaction->created_at->format('d M Y H:i:s'),
                    'paid_at' => $transaction->updated_at->format('Y/m/d H:i:s T'),
                    'game' => [
                        'name' => $transaction->product->game->name,
                        'publisher' => $transaction->product->game->publisher ?? 'Nebu Publisher',
                        'image' => $transaction->product->game->image ?? '/storage/games/dummy-ml.jpg',
                        'slug' => $transaction->product->game->slug ?? '',
                    ],
                    'account' => [
                        'username' => $transaction->customer_name ?? $transaction->user?->name ?? 'Guest User',
                        'id' => $transaction->customer_game_id,
                        'server' => $transaction->customer_zone_id ?? '-'
                    ],
                    'product' => [
                        'name' => $transaction->product->name,
                        'extra' => ''
                    ],
                    'payment_url' => $transaction->payment_url,
                    'price' => (float)$transaction->amount,
                    'qty' => 1,
                    'fee' => 0, // No extra fee stored yet locally
                    'total' => (float)$transaction->amount
                ];
            }
        }
        // dd($invoiceData);


        return Inertia::render('invoice', [
            'initialInvoiceData' => $invoiceData,
            'searchedInvoiceId' => $invoiceId
        ]);
    }
}
