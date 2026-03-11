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
        'amount',
        'profit',
        'status',
        'sn',
        'payment_url',
        'payment_status',
        'reference_id_provider',
        'api_logs',
    ];

    protected $casts = [
        'api_logs' => 'array',
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
