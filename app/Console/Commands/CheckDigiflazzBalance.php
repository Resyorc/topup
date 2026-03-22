<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\DigiflazzService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckDigiflazzBalance extends Command
{
    protected $signature = 'digiflazz:check-balance';

    protected $description = 'Check Digiflazz balance and send alert if below threshold';

    // Cache key untuk mencegah spam alert — reset jika saldo kembali normal
    private const CACHE_KEY = 'digiflazz_low_balance_alerted';
    private const CACHE_TTL_HOURS = 6;

    public function handle(DigiflazzService $digiflazz): void
    {
        try {
            $data    = $digiflazz->checkBalance();
            $balance = (float) ($data['deposit'] ?? 0);
            $threshold = (float) Setting::get('digiflazz_low_balance_threshold', 100000);

            $this->info("Saldo: Rp " . number_format($balance, 0, ',', '.'));
            $this->info("Threshold: Rp " . number_format($threshold, 0, ',', '.'));

            if ($balance < $threshold) {
                // Jika sudah pernah alert dalam 6 jam terakhir, skip
                if (Cache::has(self::CACHE_KEY)) {
                    $this->warn('Saldo rendah, tapi alert sudah dikirim dalam 6 jam terakhir. Skip.');
                    return;
                }

                $this->sendLowBalanceAlert($balance, $threshold);
                Cache::put(self::CACHE_KEY, true, now()->addHours(self::CACHE_TTL_HOURS));
                $this->warn('Alert saldo rendah dikirim ke admin.');
            } else {
                // Saldo normal — reset cache agar alert bisa dikirim lagi jika nanti turun
                Cache::forget(self::CACHE_KEY);
                $this->info('Saldo cukup. Tidak ada alert.');
            }
        } catch (\Exception $e) {
            Log::error('CheckDigiflazzBalance gagal: ' . $e->getMessage());
            $this->error('Gagal cek saldo: ' . $e->getMessage());
        }
    }

    private function sendLowBalanceAlert(float $balance, float $threshold): void
    {
        $adminEmail = config('services.admin.email');

        if (empty($adminEmail)) {
            Log::warning('Saldo Digiflazz rendah tapi ADMIN_EMAIL belum dikonfigurasi.');
            return;
        }

        $time       = now()->format('d/m/Y H:i');
        $balanceFmt = 'Rp ' . number_format($balance, 0, ',', '.');
        $threshFmt  = 'Rp ' . number_format($threshold, 0, ',', '.');

        $body = "[Nuvelo] Saldo Digiflazz Rendah\n\n"
            . "Waktu: {$time}\n"
            . "Saldo saat ini: {$balanceFmt}\n"
            . "Batas minimum: {$threshFmt}\n\n"
            . "Segera lakukan top up deposit Digiflazz agar transaksi pelanggan tidak terganggu.";

        Mail::raw($body, function ($message) use ($adminEmail, $time) {
            $message->to($adminEmail)
                ->subject("[Nuvelo] ⚠️ Saldo Digiflazz Rendah — {$time}");
        });
    }
}
