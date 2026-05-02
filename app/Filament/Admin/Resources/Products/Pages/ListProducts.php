<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Services\DigiflazzCatalogBootstrapService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bootstrap_digiflazz_catalog')
                ->label('Sync Katalog Digiflazz')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Sync & Generate Katalog Digiflazz')
                ->modalDescription('Sistem akan mengambil seluruh pricelist Digiflazz, membuat game dan produk baru dalam status tidak aktif untuk proses editing, memetakan SKU aktif, lalu refresh harga. Produk/SKU yang sudah ada akan dilewati.')
                ->modalSubmitActionLabel('Mulai Sync')
                ->action(function (): void {
                    try {
                        $result = app(DigiflazzCatalogBootstrapService::class)->bootstrap();

                        Notification::make()
                            ->title('Sync katalog berhasil')
                            ->body("{$result['provider_synced']} SKU disync, {$result['games_created']} game baru nonaktif, {$result['products_created']} produk baru nonaktif, {$result['products_reused']} produk existing dipakai, {$result['sku_mapped']} SKU dipetakan, {$result['skipped']} dilewati.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Sync katalog gagal')
                            ->body('Error: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => auth()->user()->hasAnyRole(['Super Admin', 'Staff'])),

            CreateAction::make(),
        ];
    }
}
