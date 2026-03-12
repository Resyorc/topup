<?php
 
declare(strict_types=1);
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
 
class CoinTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'description',
        'reference_id',
    ];
 
    protected $casts = [
        'amount' => 'integer',
    ];
 
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
 