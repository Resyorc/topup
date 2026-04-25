<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAction extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'before_data' => 'array',
        'after_data'  => 'array',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
