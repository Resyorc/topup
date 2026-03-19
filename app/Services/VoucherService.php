<?php

namespace App\Services;

use App\Models\Voucher;

class VoucherService
{
    /**
     * Validasi kode voucher terhadap amount tertentu.
     * Kembalikan ['valid' => bool, 'discount' => int, 'message' => string].
     */
    public function validate(string $code, int $amount): array
    {
        $voucher = Voucher::where('code', strtoupper(trim($code)))
            ->where('is_active', true)
            ->first();

        if (!$voucher) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Kode voucher tidak ditemukan.'];
        }

        if ($voucher->valid_from && $voucher->valid_from->isFuture()) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Voucher belum aktif.'];
        }

        if ($voucher->valid_until && $voucher->valid_until->isPast()) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Voucher sudah kadaluarsa.'];
        }

        if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Voucher sudah habis digunakan.'];
        }

        if ($amount < $voucher->min_amount) {
            return [
                'valid'    => false,
                'discount' => 0,
                'message'  => 'Minimum pembelian Rp ' . number_format($voucher->min_amount, 0, ',', '.') . ' untuk voucher ini.',
            ];
        }

        $discount = $voucher->calculateDiscount($amount);

        $label = $voucher->type === 'percent'
            ? "{$voucher->value}%"
            : 'Rp ' . number_format($voucher->value, 0, ',', '.');

        return [
            'valid'    => true,
            'discount' => $discount,
            'message'  => "Voucher {$label} berhasil diterapkan! Hemat Rp " . number_format($discount, 0, ',', '.'),
            'voucher'  => $voucher,
        ];
    }

    /**
     * Tandai voucher terpakai (increment used_count).
     */
    public function markUsed(string $code): void
    {
        Voucher::where('code', strtoupper(trim($code)))
            ->increment('used_count');
    }
}
