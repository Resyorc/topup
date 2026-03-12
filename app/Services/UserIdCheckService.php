<?php

namespace App\Services;

use App\Models\Game;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserIdCheckService
{
    public function check(string $gameSlug, string $userId, ?string $zoneId = null): array
    {
        $game = Game::where('slug', $gameSlug)->where('is_active', true)->first();

        if (!$game) {
            return ['success' => false, 'message' => 'Game tidak ditemukan atau sedang nonaktif.'];
        }

        [$payloadConfig, $requiresZone] = $this->resolveConfig($gameSlug, $game);

        if ($this->shouldAutoDetectMihoyoZone($gameSlug)) {
            $server = $this->resolveMihoyoServer($userId);
            if (!$server) {
                return ['success' => false, 'message' => 'UID tidak valid.'];
            }

            $zoneId = $server['zone'];
        }

        if ($requiresZone && !$zoneId) {
            return ['success' => false, 'message' => 'Server / Zone ID wajib diisi untuk game ini.'];
        }

        if (
            empty($payloadConfig['voucher_id']) ||
            empty($payloadConfig['price']) ||
            empty($payloadConfig['voucher_type'])
        ) {
            return [
                'success' => false,
                'message' => 'Konfigurasi User ID Check belum lengkap untuk game ini.',
            ];
        }

        $cacheSeconds = (int) config('services.user_id_check.cache_seconds', 60);
        $cacheKey = sprintf('user-id-check:%s:%s:%s', $gameSlug, $userId, $zoneId ?? '-');

        return Cache::remember($cacheKey, now()->addSeconds(max($cacheSeconds, 0)), function () use ($gameSlug, $userId, $zoneId, $payloadConfig) {
            $payload = [
                'voucherPricePoint.id' => $payloadConfig['voucher_id'],
                'voucherPricePoint.price' => $payloadConfig['price'],
                'voucherPricePoint.variablePrice' => '0',
                'voucherTypeName' => $payloadConfig['voucher_type'],
                'shopLang' => 'id_ID',
                'user.userId' => $userId,
            ];

            if ($zoneId) {
                $payload['user.zoneId'] = $zoneId;
            }

            $timeout = (int) config('services.user_id_check.timeout', 5);
            $endpoint = (string) config('services.user_id_check.endpoint', 'https://order-sg.codashop.com/initPayment.action');

            $response = Http::asForm()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0',
                    'Origin' => 'https://www.codashop.com',
                    'Referer' => 'https://www.codashop.com/',
                    'Accept-Language' => 'id-ID',
                ])
                ->timeout($timeout)
                ->post($endpoint, $payload);

            if (!$response->ok()) {
                Log::warning('User ID Check failed: provider is not reachable', [
                    'game' => $gameSlug,
                    'status' => $response->status(),
                ]);

                return ['success' => false, 'message' => 'Provider tidak merespon, coba lagi sebentar.'];
            }

            $json = $response->json();

            if (!empty($json['errorCode'])) {
                return [
                    'success' => false,
                    'message' => $json['errorMsg'] ?? 'User ID tidak valid.',
                ];
            }

            return [
                'success' => true,
                'nickname' => $json['confirmationFields']['username'] ?? null,
                'user_id' => $json['confirmationFields']['userId'] ?? $userId,
                'server_id' => $json['confirmationFields']['zoneId'] ?? null,
                'country' => $json['confirmationFields']['country'] ?? null,
            ];
        });
    }

    private function resolveConfig(string $gameSlug, Game $game): array
    {
        $config = (array) config("services.user_id_check.games.{$gameSlug}", []);

        $payloadConfig = [
            'voucher_id' => $config['voucher_id'] ?? $game->codashop_voucher_id,
            'price' => $config['price'] ?? $game->codashop_price,
            'voucher_type' => $config['voucher_type'] ?? $game->codashop_voucher_type,
        ];

        $requiresZone = (bool) ($config['need_zone'] ?? $game->codashop_need_zone ?? false);

        return [$payloadConfig, $requiresZone];
    }

    private function shouldAutoDetectMihoyoZone(string $gameSlug): bool
    {
        return in_array($gameSlug, ['hsr', 'genshin', 'zzz'], true);
    }

    private function resolveMihoyoServer(string $uid): ?array
    {
        if ($uid === '') {
            return null;
        }

        return match ($uid[0]) {
            '6' => ['name' => 'America', 'zone' => 'prod_official_usa'],
            '7' => ['name' => 'Europe', 'zone' => 'prod_official_eur'],
            '8' => ['name' => 'Asia', 'zone' => 'prod_official_asia'],
            '9' => ['name' => 'SAR', 'zone' => 'prod_official_cht'],
            default => null,
        };
    }
}
