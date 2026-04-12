<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipOrder extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id',
        'user_id',
        'from_tier',
        'to_tier',
        'amount',
        'status',
        'payment_method',
        'payment_name',
        'payment_url',
        'pay_code',
        'qr_url',
        'pay_url',
        'reference',
        'api_logs',
        'paid_at',
        'expired_at',
    ];

    protected $casts = [
        'api_logs'   => 'array',
        'paid_at'    => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
