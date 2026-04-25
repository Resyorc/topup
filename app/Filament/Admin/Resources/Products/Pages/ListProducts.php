<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Services\TopupPriceService;
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
            Action::make('sync_providers')
                ->label('Sync Harga Digiflazz')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Sync Harga dari Digiflazz')
                ->modalDescription('Proses ini akan mengambil harga terbaru dari Digiflazz dan memperbarui seluruh price_cost produk. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Sync Sekarang')
                ->action(function () {
                    try {
                        $result = app(TopupPriceService::class)->syncPrices();

                        Notification::make()
                            ->title('Sync Berhasil!')
                            ->body("✅ {$result['updated']} produk diperbarui, ⏭ {$result['skipped']} dilewati.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Sync Gagal')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => auth()->user()->hasAnyRole(['Super Admin', 'Staff'])),

            CreateAction::make(),
        ];
    }
}
