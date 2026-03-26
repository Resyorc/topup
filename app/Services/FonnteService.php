<?php

namespace App\Services;

use App\Models\ErrorLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    public function send(string $target, string $message): bool
    {
        $token = config('services.fonnte.token');

        if (empty($token)) {
            Log::warning('FonnteService: token tidak dikonfigurasi, notifikasi WA dilewati.');

            return false;
        }

        // Normalisasi nomor: hilangkan '+' di depan jika ada
        $target = ltrim($target, '+');

        try {
            // Fonnte mengharapkan form-data (bukan JSON) — sesuai dokumentasi resmi
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->asForm()->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62',
            ]);

            $data = $response->json();

            if (! ($data['status'] ?? false)) {
                Log::warning('FonnteService: gagal mengirim pesan', [
                    'target' => substr($target, 0, 6).'****',
                    'response' => $data,
                ]);

                ErrorLog::create([
                    'level'       => 'warning',
                    'message'     => 'WA notification gagal terkirim ke '.substr($target, 0, 6).'****',
                    'exception'   => 'FonnteDeliveryFailed',
                    'file'        => __FILE__,
                    'line'        => __LINE__,
                    'trace'       => 'Response: '.json_encode($data),
                    'url'         => null,
                    'method'      => null,
                    'ip'          => null,
                    'occurred_at' => now(),
                ]);

                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('FonnteService: exception saat kirim WA — '.$e->getMessage());

            ErrorLog::create([
                'level'       => 'error',
                'message'     => 'FonnteService exception: '.$e->getMessage(),
                'exception'   => get_class($e),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'trace'       => mb_substr($e->getTraceAsString(), 0, 65535),
                'url'         => null,
                'method'      => null,
                'ip'          => null,
                'occurred_at' => now(),
            ]);

            return false;
        }
    }
}
