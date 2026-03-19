<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CoinTransaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class CoinService
{
    /**
     * Kredit coin ke user (tambah saldo)
     */
    public function credit(User $user, int $amount, string $description, ?string $referenceId = null): CoinTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $referenceId) {
            // Kunci row user — cegah race condition
            $user = User::where('id', $user->id)->lockForUpdate()->first();

            $user->increment('coin_balance', $amount);

            return CoinTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'credit',
                'description' => $description,
                'reference_id' => $referenceId,
            ]);
        });
    }

    /**
     * Debit coin dari user (potong saldo)
     */
    public function debit(User $user, int $amount, string $description, ?string $referenceId = null): CoinTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $referenceId) {
            // Kunci row user — cegah race condition
            $user = User::where('id', $user->id)->lockForUpdate()->first();

            // Cek saldo setelah dikunci
            if ($user->coin_balance < $amount) {
                throw new Exception('Saldo coin tidak cukup.');
            }

            $user->decrement('coin_balance', $amount);

            return CoinTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'debit',
                'description' => $description,
                'reference_id' => $referenceId,
            ]);
        });
    }

    /**
     * Cek apakah user punya saldo cukup
     */
    public function hasSufficientBalance(User $user, int $amount): bool
    {
        return $user->coin_balance >= $amount;
    }
}
