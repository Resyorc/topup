<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKnowledgeSource extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getForAi(): string
    {
        return static::active()
            ->orderBy('type')
            ->get()
            ->map(fn ($k) => "## [{$k->type}] {$k->title}\n\n{$k->content}")
            ->implode("\n\n---\n\n");
    }
}
