<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameReview extends Model
{
    protected $fillable = [
        'game_id',
        'transaction_id',
        'user_id',
        'rating',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
