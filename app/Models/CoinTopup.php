<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinTopup extends Model
{
    protected $fillable = [
        'user_id',
        'invoice_id',
        'amount',
        'status',
        'failure_reason',
        'customer_whatsapp',
        'payment_method',
        'payment_name',
        'payment_url',
        'pay_code',
        'qr_url',
        'pay_url',
        'reference_id_provider',
        'expired_at',
        'paid_at',
        'api_logs',
    ];

    protected $casts = [
        'amount' => 'integer',
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
        'api_logs' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
