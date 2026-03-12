<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionExpiryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserTransactionController extends Controller
{
    public function index(Request $request, TransactionExpiryService $transactionExpiryService)
    {
        $user = $request->user();

        $transactionExpiryService->expireOverdue(userId: (int) $user->id);

        $filters = [
            'status' => $request->string('status')->toString(),
            'payment_status' => $request->string('payment_status')->toString(),
            'start_date' => $request->string('start_date')->toString(),
            'end_date' => $request->string('end_date')->toString(),
            'search' => $request->string('search')->toString(),
        ];

        $transactions = Transaction::query()
            ->where('user_id', $user->id)
            ->with(['product:id,game_id,name', 'product.game:id,name'])
            ->when($filters['status'] !== '', function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['payment_status'] !== '', function ($query) use ($filters) {
                $query->where('payment_status', $filters['payment_status']);
            })
            ->when($filters['start_date'] !== '', function ($query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            })
            ->when($filters['end_date'] !== '', function ($query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            })
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('invoice_id', 'like', '%' . $search . '%')
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhereHas('game', function ($gameQuery) use ($search) {
                                    $gameQuery->where('name', 'like', '%' . $search . '%');
                                });
                        });
                });
            })
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (Transaction $transaction) {
                return [
                    'id' => $transaction->id,
                    'invoice_id' => $transaction->invoice_id,
                    'game_name' => $transaction->product?->game?->name ?? '-',
                    'product_name' => $transaction->product?->name ?? '-',
                    'amount' => (float) $transaction->amount,
                    'created_at' => $transaction->created_at?->toISOString(),
                    'status' => $transaction->status,
                    'payment_status' => $transaction->payment_status,
                ];
            })
            ->values();

        return Inertia::render('user/transactions', [
            'filters' => $filters,
            'transactions' => $transactions,
        ]);
    }
}
