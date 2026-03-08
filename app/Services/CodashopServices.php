<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Game;

class CodashopServices
{
    public function check(string $gameSlug, string $userId, ?string $zoneId = null): array
    {
        $game = Game::where('slug', $gameSlug)->first();

        if ($gameSlug === 'hsr') {
            $server = $this->resolveMihoyoServer($userId);

            if (!$server) {
                return ['success' => false, 'message' => 'UID tidak valid'];
            }

            $zoneId = $server['zone'];
        }

        if (!$game || !$game->is_check_id) {
            return ['success' => false, 'message' => 'Game tidak didukung atau fitur Pengecekan ID dimatikan'];
        }

        if ($game->codashop_need_zone && !$zoneId) {
            return ['success' => false, 'message' => 'Server ID wajib dibutuhkan untuk pengecekan'];
        }


        $payload = [
            'voucherPricePoint.id'        => $game->codashop_voucher_id,
            'voucherPricePoint.price'     => $game->codashop_price,
            'voucherPricePoint.variablePrice' => '0',
            'voucherTypeName'             => $game->codashop_voucher_type,
            'shopLang'                    => 'id_ID',
            'user.userId'                 => $userId,
        ];

        if ($zoneId) {
            $payload['user.zoneId'] = $zoneId;
        }

        $response = Http::asForm()
            ->withHeaders([
                'User-Agent'      => 'Mozilla/5.0',
                'Origin'          => 'https://www.codashop.com',
                'Referer'         => 'https://www.codashop.com/',
                'Accept-Language' => 'id-ID',
            ])
            ->timeout(5)
            ->post('https://order-sg.codashop.com/initPayment.action', $payload);

        if (!$response->ok()) {
            return ['success' => false, 'message' => 'Codashop tidak merespon'];
        }

        $json = $response->json();

        if (!empty($json['errorCode'])) {
            return [
                'success' => false,
                'message' => $json['errorMsg'] ?? 'ID tidak valid'
            ];
        }

        return [
            'success'  => true,
            'nickname' => $json['confirmationFields']['username'] ?? null,
            'user_id'  => $json['confirmationFields']['userId'] ?? $userId,
            'server_id'=> $json['confirmationFields']['zoneId'] ?? null,
            'country'  => $json['confirmationFields']['country'] ?? null,
        ];
    }

    private function resolveMihoyoServer(string $uid): ?array
    {
        return match ($uid[0]) {
            '6' => ['name' => 'America', 'zone' => 'prod_official_usa'],
            '7' => ['name' => 'Europe',  'zone' => 'prod_official_eur'],
            '8' => ['name' => 'Asia',    'zone' => 'prod_official_asia'],
            '9' => ['name' => 'SAR (TW/HK/MO)', 'zone' => 'prod_official_cht'],
            default => null,
        };
    }
}