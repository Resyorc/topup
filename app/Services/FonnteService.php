<?php

namespace App\Services;

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
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
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

                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('FonnteService: exception saat kirim WA — '.$e->getMessage());

            return false;
        }
    }
}
