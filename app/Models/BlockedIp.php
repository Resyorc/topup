<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'blocked_until' => 'datetime',
        'is_auto'       => 'boolean',
        'created_at'    => 'datetime',
    ];

    /**
     * Cek apakah IP sedang aktif diblokir.
     */
    public static function isBlocked(string $ip): bool
    {
        return static::where('ip', $ip)
            ->where(function ($q) {
                $q->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            })
            ->exists();
    }

    /**
     * Blokir IP untuk durasi tertentu (atau permanent jika null).
     */
    public static function block(string $ip, string $reason, ?\DateTimeInterface $until = null, bool $auto = true): void
    {
        static::updateOrCreate(
            ['ip' => $ip],
            [
                'reason'        => $reason,
                'is_auto'       => $auto,
                'blocked_until' => $until,
                'created_at'    => now(),
            ]
        );
    }

    /**
     * Hapus blokir IP.
     */
    public static function unblock(string $ip): void
    {
        static::where('ip', $ip)->delete();
    }
}
