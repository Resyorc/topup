<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'grouping_rules' => 'array',
        'icon_rules' => 'array',
        'region_map' => 'array',
        'input_fields' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Resolve icon URL untuk sebuah produk berdasarkan icon_rules game.
     * Logic ini adalah PHP equivalent dari resolveProductIcon() di game-detail.tsx.
     */
    public function resolveProductIcon(Product $product): ?string
    {
        // Filament Repeater dengan ->reorderable() menyimpan sebagai {uuid: item}.
        // array_values() normalisasi ke sequential array agar foreach aman.
        $rules = array_values($this->icon_rules ?? []);
        if (empty($rules)) {
            return null;
        }

        $name = $product->name;
        $cleanName = str_contains($name, '(')
            ? trim(substr($name, 0, strpos($name, '(')))
            : $name;

        // Tentukan group dari grouping_rules
        $group = '';
        foreach ($this->grouping_rules ?? [] as $rule) {
            $groupLabel = $rule['group'] ?? '';
            $keywords = array_map('trim', explode(',', $rule['keywords'] ?? ''));
            foreach ($keywords as $kw) {
                if ($kw !== '' && str_contains(strtolower($name), strtolower($kw))) {
                    $group = $groupLabel;
                    break 2;
                }
            }
        }

        foreach ($rules as $rule) {
            $type = $rule['type'] ?? '';
            $icon = $rule['icon'] ?? null;

            if ($type === 'group') {
                $matchGroup = $rule['match_group'] ?? '';
                if ($matchGroup !== '' && str_contains(strtolower($group), strtolower($matchGroup))) {
                    return $icon ? '/storage/'.$icon : null;
                }
            } elseif ($type === 'keyword') {
                $matchKeyword = $rule['match_keyword'] ?? '';
                if ($matchKeyword !== '') {
                    $keywords = array_map('trim', explode(',', $matchKeyword));
                    foreach ($keywords as $kw) {
                        if ($kw !== '' && str_contains(strtolower($cleanName), strtolower($kw))) {
                            return $icon ? '/storage/'.$icon : null;
                        }
                    }
                }
            } elseif ($type === 'range') {
                preg_match('/\d+/', $cleanName, $matches);
                if (! empty($matches)) {
                    $amount = (int) $matches[0];
                    $min = isset($rule['amount_min']) && $rule['amount_min'] !== '' ? (int) $rule['amount_min'] : null;
                    $max = isset($rule['amount_max']) && $rule['amount_max'] !== '' ? (int) $rule['amount_max'] : null;
                    if (($min === null || $amount >= $min) && ($max === null || $amount <= $max)) {
                        return $icon ? '/storage/'.$icon : null;
                    }
                }
            }
        }

        return null;
    }
}
