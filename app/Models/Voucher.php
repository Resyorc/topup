<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_amount',
        'max_discount',
        'usage_limit',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
        'is_public',
        'min_tier',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * Hitung nominal diskon untuk amount tertentu.
     */
    public function calculateDiscount(int $amount): int
    {
        if ($this->type === 'percent') {
            $discount = (int) floor($amount * $this->value / 100);
            if ($this->max_discount) {
                $discount = min($discount, $this->max_discount);
            }
        } else {
            $discount = min((int) $this->value, $amount);
        }

        return $discount;
    }
}
