<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Clusters\CatalogCluster;
use App\Models\ProviderProduct;
use App\Models\Game;
use App\Models\Product;
use App\Services\AiSkuAnalyzerService;
use App\Services\AutoPilotService;
use App\Services\TopupPriceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class AiSkuSuggestionsPage extends Page
{
    protected string $view = 'filament.admin.pages.ai-sku-suggestions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Saran AI';

    protected static ?string $title = 'Saran Produk dari AI';

    protected static ?int $navigationSort = 11;

    protected static ?string $cluster = CatalogCluster::class;

    public array $suggestions = [];

    public array $gameNames = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff']);
    }

    public function mount(): void
    {
        $this->suggestions = app(AiSkuAnalyzerService::class)->getSuggestions();
        $this->gameNames   = Game::pluck('name', 'id')->toArray();
    }

    public function approve(string $skuCode): void
    {
        $suggestion = collect($this->suggestions)->firstWhere('sku_code', $skuCode);
        if (! $suggestion) {
            return;
        }

        if (! $suggestion['game_id']) {
            Notification::make()
                ->title('Tidak bisa dibuat')
                ->body("SKU {$skuCode} tidak memiliki game yang cocok. Petakan manual dari halaman Digiflazz SKU.")
                ->warning()
                ->send();
            return;
        }

        $sku = ProviderProduct::where('provider_name', 'digiflazz')
            ->where('provider_sku', $skuCode)
            ->first();

        if (! $sku) {
            return;
        }

        if ($sku->product_id !== null) {
            Notification::make()
                ->title('SKU sudah dipetakan')
                ->body("SKU {$skuCode} sudah digunakan oleh produk lain.")
                ->warning()
                ->send();
            app(AiSkuAnalyzerService::class)->removeSuggestion($skuCode);
            $this->suggestions = app(AiSkuAnalyzerService::class)->getSuggestions();
            return;
        }

        $cost         = (float) $sku->price;
        $priceService = app(TopupPriceService::class);
        $margins      = app(AutoPilotService::class)->calcTierMargins((int) ($suggestion['suggested_margin'] ?? 500));

        $product = Product::create([
            'game_id'              => $suggestion['game_id'],
            'name'                 => $suggestion['product_name'],
            'price_cost'           => $cost,
            'margin_guest_flat'    => $margins['guest'],
            'margin_bronze_flat'   => $margins['bronze'],
            'margin_silver_flat'   => $margins['silver'],
            'margin_gold_flat'     => $margins['gold'],
            'margin_platinum_flat' => $margins['platinum'],
            'price_guest'          => $priceService->calculateSellPrice($cost, $margins['guest']),
            'price_bronze'         => $priceService->calculateSellPrice($cost, $margins['bronze']),
            'price_silver'         => $priceService->calculateSellPrice($cost, $margins['silver']),
            'price_gold'           => $priceService->calculateSellPrice($cost, $margins['gold']),
            'price_platinum'       => $priceService->calculateSellPrice($cost, $margins['platinum']),
            'is_available'         => $sku->is_active,
        ]);

        $sku->update(['product_id' => $product->id]);

        app(AiSkuAnalyzerService::class)->removeSuggestion($skuCode);
        $this->suggestions = app(AiSkuAnalyzerService::class)->getSuggestions();

        Notification::make()
            ->title('Produk dibuat')
            ->body("{$suggestion['product_name']} berhasil ditambahkan dengan margin silver Rp " . number_format($margins['silver'], 0, ',', '.') . ".")
            ->success()
            ->send();
    }

    public function skip(string $skuCode): void
    {
        app(AiSkuAnalyzerService::class)->removeSuggestion($skuCode);
        $this->suggestions = app(AiSkuAnalyzerService::class)->getSuggestions();

        Notification::make()
            ->title('SKU dilewati')
            ->success()
            ->send();
    }

    public function approveAll(): void
    {
        $approved     = 0;
        $skipped      = 0;
        $priceService = app(TopupPriceService::class);
        $autoPilot    = app(AutoPilotService::class);

        foreach ($this->suggestions as $suggestion) {
            if (! $suggestion['recommended'] || ! $suggestion['game_id']) {
                $skipped++;
                continue;
            }

            $sku = ProviderProduct::where('provider_name', 'digiflazz')
                ->where('provider_sku', $suggestion['sku_code'])
                ->first();

            if (! $sku || $sku->product_id !== null) {
                $skipped++;
                continue;
            }

            $cost    = (float) $sku->price;
            $margins = $autoPilot->calcTierMargins((int) ($suggestion['suggested_margin'] ?? 500));

            $product = Product::create([
                'game_id'              => $suggestion['game_id'],
                'name'                 => $suggestion['product_name'],
                'price_cost'           => $cost,
                'margin_guest_flat'    => $margins['guest'],
                'margin_bronze_flat'   => $margins['bronze'],
                'margin_silver_flat'   => $margins['silver'],
                'margin_gold_flat'     => $margins['gold'],
                'margin_platinum_flat' => $margins['platinum'],
                'price_guest'          => $priceService->calculateSellPrice($cost, $margins['guest']),
                'price_bronze'         => $priceService->calculateSellPrice($cost, $margins['bronze']),
                'price_silver'         => $priceService->calculateSellPrice($cost, $margins['silver']),
                'price_gold'           => $priceService->calculateSellPrice($cost, $margins['gold']),
                'price_platinum'       => $priceService->calculateSellPrice($cost, $margins['platinum']),
                'is_available'         => $sku->is_active,
            ]);

            $sku->update(['product_id' => $product->id]);
            $approved++;
        }

        app(AiSkuAnalyzerService::class)->clearSuggestions();
        $this->suggestions = [];

        Notification::make()
            ->title("Selesai: {$approved} produk dibuat, {$skipped} dilewati")
            ->body('Margin per tier sudah diset otomatis oleh AI. Bisa disesuaikan manual di halaman Produk.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        $count = count(array_filter($this->suggestions, fn ($s) => $s['recommended'] && $s['game_id']));

        return [
            Action::make('approveAll')
                ->label("Buat Semua yang Direkomendasikan ({$count})")
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => ! empty($this->suggestions))
                ->requiresConfirmation()
                ->modalHeading('Buat Semua Produk yang Direkomendasikan')
                ->modalDescription("AI merekomendasikan {$count} produk. Semua akan dibuat dengan margin per tier yang sudah dihitung AI berdasarkan data penjualan.")
                ->modalSubmitActionLabel('Ya, Buat Semuanya')
                ->action(fn () => $this->approveAll()),

            Action::make('clearAll')
                ->label('Buang Semua')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => ! empty($this->suggestions))
                ->requiresConfirmation()
                ->action(function () {
                    app(AiSkuAnalyzerService::class)->clearSuggestions();
                    $this->suggestions = [];

                    Notification::make()->title('Semua saran dihapus')->success()->send();
                }),
        ];
    }
}
