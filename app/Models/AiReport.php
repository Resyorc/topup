<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiReport extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
