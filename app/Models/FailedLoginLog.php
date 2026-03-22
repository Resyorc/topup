<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedLoginLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];
}
