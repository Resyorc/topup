<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'input_tokens'  => 'integer',
        'output_tokens' => 'integer',
        'total_tokens'  => 'integer',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
