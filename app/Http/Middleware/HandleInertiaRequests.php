<?php

namespace App\Http\Middleware;

use App\Models\BroadcastMessage;
use App\Services\TripayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Throwable;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'avatar_url' => $request->user()->avatar_url,
                    'api_access_enabled' => (bool) $request->user()->api_access_enabled,
                ]) : null,
            ],
            'flash' => [
                'status' => $request->session()->get('status'),
            ],
            'broadcastMessages' => Cache::remember('broadcast_messages', 60, fn () => BroadcastMessage::where('is_active', true)->pluck('message')->toArray()
            ),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'appUrl' => rtrim(config('app.url'), '/'),
            'webSetting' => fn () => [
                'logo' => \App\Models\Setting::get('web_logo') ? asset('storage/'.\App\Models\Setting::get('web_logo')) : null,
                'themeColor' => \App\Models\Setting::get('web_theme_color', '#10b981'),
                'waBubble' => [
                    'enabled' => (bool) \App\Models\Setting::get('wa_bubble_enabled', false),
                    'number' => \App\Models\Setting::get('wa_bubble_number'),
                    'message' => \App\Models\Setting::get('wa_bubble_message'),
                ],
                'sosmedLinks' => json_decode(\App\Models\Setting::get('sosmed_links', '[]'), true) ?: [],
                'turnstile' => [
                    'enabled' => (bool) \App\Models\Setting::get('enable_turnstile', false),
                    'siteKey' => \App\Models\Setting::get('turnstile_site_key'),
                ],
            ],
            'footerPaymentChannels' => fn () => $this->footerPaymentChannels(),
        ];
    }

    private function footerPaymentChannels(): array
    {
        $cacheKey = 'tripay_footer_payment_channels';
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $channels = $this->resolveTripayFooterPaymentChannels();

            Cache::put($cacheKey, $channels, 1800);

            return $channels;
        } catch (Throwable $e) {
            report($e);
            Cache::put($cacheKey, [], 300);

            return [];
        }
    }

    private function resolveTripayFooterPaymentChannels(): array
    {
        if (! config('services.tripay.api_key')) {
            return [];
        }

        $channels = [];

        foreach ((new TripayService)->getPaymentChannels() as $channel) {
            if (! filter_var($channel['active'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $code = (string) ($channel['code'] ?? '');
            $name = (string) ($channel['name'] ?? $code);

            if ($name === '') {
                continue;
            }

            $group = str_contains(strtoupper($name), 'QRIS')
                ? 'QRIS'
                : (string) ($channel['group'] ?? 'Lainnya');

            $channels[] = [
                'code' => $code,
                'name' => $name,
                'group' => $group ?: 'Lainnya',
                'icon_url' => $channel['icon_url'] ?? null,
            ];
        }

        $order = array_flip(['QRIS', 'E-Wallet', 'Virtual Account', 'Convenience Store']);
        usort($channels, function (array $a, array $b) use ($order): int {
            $groupComparison = ($order[$a['group']] ?? PHP_INT_MAX) <=> ($order[$b['group']] ?? PHP_INT_MAX);

            return $groupComparison !== 0
                ? $groupComparison
                : strnatcasecmp((string) $a['name'], (string) $b['name']);
        });

        return $channels;
    }
}
