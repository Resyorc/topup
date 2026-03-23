<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

class TierService
{
    // Urutan tier dari terendah ke tertinggi
    public const ORDER = ['bronze', 'silver', 'gold', 'platinum'];

    public function thresholds(): array
    {
        return [
            'platinum' => (int) Setting::get('tier_threshold_platinum', 10_000_000),
            'gold'     => (int) Setting::get('tier_threshold_gold', 2_000_000),
            'silver'   => (int) Setting::get('tier_threshold_silver', 500_000),
            'bronze'   => 0,
        ];
    }

    public function multipliers(): array
    {
        return [
            'bronze'   => (float) Setting::get('tier_multiplier_bronze', 1.0),
            'silver'   => (float) Setting::get('tier_multiplier_silver', 1.25),
            'gold'     => (float) Setting::get('tier_multiplier_gold', 1.5),
            'platinum' => (float) Setting::get('tier_multiplier_platinum', 2.0),
        ];
    }

    public function calculateTier(User $user): string
    {
        $total = $user->transactions()
            ->where('status', 'success')
            ->sum('amount');

        foreach ($this->thresholds() as $tier => $threshold) {
            if ($total >= $threshold) {
                return $tier;
            }
        }

        return 'bronze';
    }

    public function getMultiplier(string $tier): float
    {
        return $this->multipliers()[$tier] ?? 1.0;
    }

    public function meetsMinTier(string $userTier, string $minTier): bool
    {
        $userRank = array_search($userTier, self::ORDER);
        $minRank  = array_search($minTier, self::ORDER);

        return $userRank !== false && $minRank !== false && $userRank >= $minRank;
    }

    public function recalculate(User $user): void
    {
        $tier = $this->calculateTier($user);

        if ($user->tier !== $tier) {
            $user->update(['tier' => $tier]);
        }
    }

    public static function label(string $tier): string
    {
        return match ($tier) {
            'silver'   => 'Silver',
            'gold'     => 'Gold',
            'platinum' => 'Platinum',
            default    => 'Bronze',
        };
    }
}
