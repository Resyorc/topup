<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id',
        'user_id',
        'product_id',
        'customer_game_id',
        'customer_zone_id',
        'customer_whatsapp',
        'customer_name',
        'customer_email',      // ✅ tambah
        'amount',
        'fee',
        'profit',
        'loyalty_coins',
        'voucher_code',
        'discount',
        'status',
        'sn',
        'failure_reason',      // ✅ tambah
        'payment_url',
        'payment_status',
        'payment_method',      // ✅ tambah
        'payment_name',        // ✅ tambah
        'pay_code',            // ✅ tambah
        'qr_url',              // ✅ tambah
        'pay_url',             // ✅ tambah
        'reference_id_provider',
        'expired_at',          // ✅ tambah
        'api_logs',
    ];

    protected $casts = [
        'api_logs'      => 'array',
        'expired_at'    => 'datetime',
        'loyalty_coins' => 'integer',
        'discount'      => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
