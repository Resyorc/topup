<?php

namespace App\Listeners;

use App\Models\BlockedIp;
use App\Models\FailedLoginLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LogFailedLogin
{
    // Kirim alert ke admin setelah X gagal dalam window waktu tertentu
    private const ALERT_THRESHOLD = 5;
    private const ALERT_WINDOW_MINUTES = 15;

    // Auto-block IP setelah X gagal dalam window yang lebih panjang
    private const BLOCK_THRESHOLD = 10;
    private const BLOCK_WINDOW_MINUTES = 30;
    private const BLOCK_DURATION_HOURS = 24;

    public function handle(Failed $event): void
    {
        $ip    = request()->ip();
        $email = $event->credentials['email'] ?? 'unknown';

        FailedLoginLog::create([
            'ip'               => $ip,
            'email_attempted'  => $email,
            'user_agent'       => request()->userAgent(),
            'attempted_at'     => now(),
        ]);

        $this->checkAndAlertBruteForce($ip, $email);
        $this->checkAndBlockIp($ip);
    }

    private function checkAndBlockIp(string $ip): void
    {
        $failures = FailedLoginLog::where('ip', $ip)
            ->where('attempted_at', '>=', now()->subMinutes(self::BLOCK_WINDOW_MINUTES))
            ->count();

        if ($failures >= self::BLOCK_THRESHOLD) {
            BlockedIp::block(
                ip: $ip,
                reason: "Auto-block: {$failures}x login gagal dalam " . self::BLOCK_WINDOW_MINUTES . " menit",
                until: now()->addHours(self::BLOCK_DURATION_HOURS),
                auto: true,
            );
        }
    }

    private function checkAndAlertBruteForce(string $ip, string $email): void
    {
        $recentFailures = FailedLoginLog::where('ip', $ip)
            ->where('attempted_at', '>=', now()->subMinutes(self::ALERT_WINDOW_MINUTES))
            ->count();

        // Kirim alert hanya tepat saat mencapai threshold (tidak spam setiap request)
        if ($recentFailures !== self::ALERT_THRESHOLD) {
            return;
        }

        $adminEmail = config('services.admin.email');

        if (empty($adminEmail)) {
            Log::warning("Brute force detected dari IP {$ip} tapi ADMIN_EMAIL belum dikonfigurasi.");
            return;
        }

        $time = now()->format('d/m/Y H:i');
        $body = "[Nuvelo] Percobaan Login Mencurigakan\n\n"
            . "Waktu: {$time}\n"
            . "IP: {$ip}\n"
            . "Email yang dicoba: {$email}\n"
            . "Jumlah gagal: {$recentFailures}x dalam " . self::ALERT_WINDOW_MINUTES . " menit terakhir\n\n"
            . "Segera cek dashboard untuk tindakan lebih lanjut.";

        Mail::raw($body, function ($message) use ($adminEmail, $time) {
            $message->to($adminEmail)
                ->subject("[Nuvelo] ⚠️ Brute Force Login Terdeteksi — {$time}");
        });
    }
}
