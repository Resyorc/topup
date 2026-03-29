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
        $game = Game::where('slug', $gameSlug)->where('is_active', true)->exists();

        if (! $game) {
            return ['success' => false, 'message' => 'Game tidak ditemukan atau sedang nonaktif.'];
        }

        [$payloadConfig, $requiresZone] = $this->resolveConfig($gameSlug);

        // Game yang tidak mendukung cek nickname
        if (! empty($payloadConfig['skip_check'])) {
            return ['success' => true, 'nickname' => $userId];
        }

        // Special API: GoPay (e.g. Free Fire)
        if ($payloadConfig['api'] === 'gopay') {
            return $this->checkViaGopay($userId, $payloadConfig['gopay_game'] ?? '');
        }

        // Special API: DancingIdol (e.g. AU2 Mobile)
        if ($payloadConfig['api'] === 'dancingidol') {
            return $this->checkViaDancingIdol($userId);
        }

        // Mihoyo: auto-detect zone dari digit pertama UID
        if ($this->shouldAutoDetectMihoyoZone($gameSlug)) {
            $server = $this->resolveMihoyoServer($userId, $payloadConfig['server_zones'] ?? []);
            if (! $server) {
                return ['success' => false, 'message' => 'UID tidak valid.'];
            }
            $zoneId = $server['zone'];
        }

        // Fixed zone (e.g. Sausage Man selalu 'global-release', BarbarQ selalu '1')
        if (! empty($payloadConfig['fixed_zone'])) {
            $zoneId = $payloadConfig['fixed_zone'];
        }

        if ($requiresZone && ! $zoneId) {
            return ['success' => false, 'message' => 'Server / Zone ID wajib diisi untuk game ini.'];
        }

        if (empty($payloadConfig['voucher_id']) || empty($payloadConfig['price']) || empty($payloadConfig['voucher_type'])) {
            return ['success' => false, 'message' => 'Konfigurasi User ID Check belum lengkap untuk game ini.'];
        }

        $cacheSeconds = (int) config('services.user_id_check.cache_seconds', 60);
        $cacheKey = sprintf('user-id-check:%s:%s:%s', $gameSlug, $userId, $zoneId ?? '-');

        return Cache::remember($cacheKey, now()->addSeconds(max($cacheSeconds, 0)), function () use ($userId, $zoneId, $payloadConfig) {
            return $this->checkViaCodashop($userId, $zoneId, $payloadConfig);
        });
    }

    private function checkViaCodashop(string $userId, ?string $zoneId, array $config): array
    {
        $payload = [
            'voucherPricePoint.id' => $config['voucher_id'],
            'voucherPricePoint.price' => $config['price'],
            'voucherPricePoint.variablePrice' => '0',
            'user.userId' => $userId,
            'user.zoneId' => $zoneId ?? '',
            'voucherTypeName' => $config['voucher_type'],
            'lvtId' => $config['lvt_id'] ?? '',
            'shopLang' => 'id_ID',
            'dynamicSkuToken' => $config['dynamic_sku_token'] ?? '',
            'pricePointDynamicSkuToken' => $config['price_point_dynamic_sku_token'] ?? '',
            'voucherTypeId' => $config['voucher_type_id'] ?? '',
            'gvtId' => $config['gvt_id'] ?? '',
        ];

        $timeout = (int) config('services.user_id_check.timeout', 5);
        $endpoint = (string) config('services.user_id_check.endpoint', 'https://order-sg.codashop.com/initPayment.action');

        try {
            $response = Http::asForm()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.5005.63 Mobile Safari/537.36',
                    'Origin' => 'https://www.codashop.com',
                    'Referer' => 'https://www.codashop.com/',
                    'Accept-Language' => 'id-ID',
                ])
                ->timeout($timeout)
                ->post($endpoint, $payload);
        } catch (\Exception $e) {
            Log::warning('UserIdCheck: request exception', ['game' => $payload['voucherTypeName'], 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Provider tidak merespon, coba lagi sebentar.'];
        }

        if (! $response->ok()) {
            Log::warning('UserIdCheck: non-200 response', ['status' => $response->status()]);

            return ['success' => false, 'message' => 'Provider tidak merespon, coba lagi sebentar.'];
        }

        $json = $response->json();

        if (isset($json['RESULT_CODE']) && $json['RESULT_CODE'] === '10001') {
            return ['success' => false, 'message' => 'Terlalu banyak percobaan, coba lagi beberapa detik.'];
        }

        // errorCode -200: valid user tapi nickname tidak tersedia, gunakan ID sebagai nama
        if (isset($json['errorCode']) && $json['errorCode'] === -200) {
            return ['success' => true, 'nickname' => (string) $userId];
        }

        if (! ($json['success'] ?? false) || ! empty($json['errorMsg'])) {
            return ['success' => false, 'message' => $json['errorMsg'] ?? 'User ID tidak valid.'];
        }

        $nickname = $this->extractNickname($json, $config['nickname_field'] ?? 'username');

        $rawCountry = $json['confirmationFields']['country'] ?? null;

        return [
            'success' => true,
            'nickname' => $nickname ? urldecode($nickname) : null,
            'user_id' => $json['confirmationFields']['userId'] ?? $userId,
            'server_id' => $json['confirmationFields']['zoneId'] ?? null,
            'country' => $rawCountry ? $this->normalizeCountry($rawCountry) : null,
        ];
    }

    private function checkViaGopay(string $userId, string $game): array
    {
        try {
            $response = Http::timeout(5)
                ->get("https://gopay.co.id/games/v1/order/prepare/{$game}", ['userId' => $userId]);

            $json = $response->json();

            if (! empty($json['data'])) {
                return ['success' => true, 'nickname' => $json['data']];
            }

            return ['success' => false, 'message' => 'User ID tidak valid.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Provider tidak merespon, coba lagi sebentar.'];
        }
    }

    private function checkViaDancingIdol(string $userId): array
    {
        try {
            $response = Http::withHeaders([
                'Host' => 'dancingidol.uniuhk.com',
                'Referer' => 'http://dancingidol.uniuhk.com/payment',
            ])->timeout(5)->get('http://dancingidol.uniuhk.com/api/role/info', ['roleId' => $userId]);

            $json = $response->json();

            if (! empty($json['data']['rolename'])) {
                return ['success' => true, 'nickname' => $json['data']['rolename']];
            }

            return ['success' => false, 'message' => 'User ID tidak valid.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Provider tidak merespon, coba lagi sebentar.'];
        }
    }

    private function extractNickname(array $json, string $field): ?string
    {
        return match ($field) {
            'roles' => $json['confirmationFields']['roles'][0]['role'] ?? null,
            'result' => $json['result'] ?? null,
            default => $json['confirmationFields']['username'] ?? null,
        };
    }

    private function normalizeCountry(string $country): string
    {
        $map = [
            'indonesia'   => 'ID',
            'malaysia'    => 'MY',
            'philippines' => 'PH',
            'singapore'   => 'SG',
            'thailand'    => 'TH',
            'vietnam'     => 'VN',
            'myanmar'     => 'MM',
            'cambodia'    => 'KH',
            'laos'        => 'LA',
            'brunei'      => 'BN',
        ];

        $lower = strtolower(trim($country));

        return $map[$lower] ?? strtoupper($country);
    }

    private function resolveConfig(string $gameSlug): array
    {
        $config = (array) config("services.user_id_check.games.{$gameSlug}", []);

        $payloadConfig = [
            'voucher_id' => $config['voucher_id'] ?? null,
            'price' => $config['price'] ?? null,
            'voucher_type' => $config['voucher_type'] ?? null,
            'skip_check' => $config['skip_check'] ?? false,
            'api' => $config['api'] ?? 'codashop',
            'gopay_game' => $config['gopay_game'] ?? '',
            'nickname_field' => $config['nickname_field'] ?? 'username',
            'fixed_zone' => $config['fixed_zone'] ?? null,
            'lvt_id' => $config['lvt_id'] ?? '',
            'dynamic_sku_token' => $config['dynamic_sku_token'] ?? '',
            'price_point_dynamic_sku_token' => $config['price_point_dynamic_sku_token'] ?? '',
            'voucher_type_id' => $config['voucher_type_id'] ?? '',
            'gvt_id' => $config['gvt_id'] ?? '',
            'server_zones' => $config['server_zones'] ?? [],
        ];

        $requiresZone = (bool) ($config['need_zone'] ?? false);

        return [$payloadConfig, $requiresZone];
    }

    private function shouldAutoDetectMihoyoZone(string $gameSlug): bool
    {
        return in_array($gameSlug, ['hsr', 'genshin-impact', 'zzz'], true);
    }

    private function resolveMihoyoServer(string $uid, array $serverZones = []): ?array
    {
        if ($uid === '') {
            return null;
        }

        // Default zone map (Genshin format)
        $defaultZones = [
            '6' => ['name' => 'America', 'zone' => 'os_usa'],
            '7' => ['name' => 'Europe',  'zone' => 'os_euro'],
            '8' => ['name' => 'Asia',    'zone' => 'os_asia'],
            '9' => ['name' => 'SAR',     'zone' => 'os_cht'],
        ];

        // UID awalan '18' → Asia
        $key = str_starts_with($uid, '18') ? '8' : $uid[0];

        if (! isset($defaultZones[$key])) {
            return null;
        }

        // Jika game punya zone map sendiri (e.g. HSR pakai prod_official_*)
        if (! empty($serverZones[$key])) {
            return ['name' => $defaultZones[$key]['name'], 'zone' => $serverZones[$key]];
        }

        return $defaultZones[$key];
    }
}
