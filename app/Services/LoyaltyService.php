<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyService
{
    /**
     * Hitung dan berikan reward Krysta Coin untuk transaksi yang berhasil.
     * Dipanggil setelah Digiflazz mengkonfirmasi status 'sukses'.
     *
     * Aturan:
     * - Hanya untuk user yang login (user_id tidak null)
     * - Tidak berlaku untuk transaksi yang dibayar dengan Krysta Coin (avoid loop)
     * - Minimum amount harus terpenuhi
     * - Hanya award sekali (loyalty_coins masih 0)
     */
    public function awardFromTransaction(Transaction $transaction): int
    {
        // Guard: hanya user login
        if (empty($transaction->user_id)) {
            return 0;
        }

        // Guard: tidak berlaku untuk pembayaran Coin
        if ($transaction->payment_method === 'COIN') {
            return 0;
        }

        // Guard: jangan double award
        if ($transaction->loyalty_coins > 0) {
            return 0;
        }

        $rate        = (float) Setting::get('loyalty_rate_percent', config('services.loyalty.rate_percent', 1));
        $minAmount   = (int)   Setting::get('loyalty_min_amount',   config('services.loyalty.min_amount', 5000));

        // Guard: minimum amount
        if ((int) $transaction->amount < $minAmount) {
            return 0;
        }

        $coins = (int) floor($transaction->amount * $rate / 100);

        if ($coins <= 0) {
            return 0;
        }

        try {
            return DB::transaction(function () use ($transaction, $coins) {
                // Kunci row transaksi — cegah race condition jika callback ganda
                $locked = Transaction::where('id', $transaction->id)
                    ->lockForUpdate()
                    ->first();

                // Re-check setelah dikunci (callback kedua akan berhenti di sini)
                if (!$locked || $locked->loyalty_coins > 0) {
                    return 0;
                }

                $user = User::find($locked->user_id);

                if (!$user) {
                    return 0;
                }

                app(CoinService::class)->credit(
                    $user,
                    $coins,
                    'Reward loyalitas — ' . ($transaction->product->game->name ?? 'Topup') . ' ' . ($transaction->product->name ?? ''),
                    $locked->invoice_id,
                );

                $locked->update(['loyalty_coins' => $coins]);

                Log::info("Loyalty reward: {$coins} coins → user #{$user->id} (invoice: {$locked->invoice_id})");

                return $coins;
            });

        } catch (\Exception $e) {
            Log::error("Loyalty reward gagal untuk {$transaction->invoice_id}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Hitung preview reward tanpa menyimpan — dipakai untuk tampilan di frontend.
     */
    public function calculatePreview(int $amount): int
    {
        $rate      = (float) Setting::get('loyalty_rate_percent', config('services.loyalty.rate_percent', 1));
        $minAmount = (int)   Setting::get('loyalty_min_amount',   config('services.loyalty.min_amount', 5000));

        if ($amount < $minAmount) {
            return 0;
        }

        return (int) floor($amount * $rate / 100);
    }
}
