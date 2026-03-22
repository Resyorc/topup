<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Services\TopupPriceService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncDigiflazz')
                ->label('Sync Digiflazz')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sync Produk dari Digiflazz')
                ->modalDescription('Proses ini akan mengupdate harga dan status ketersediaan semua produk berdasarkan data terbaru dari Digiflazz. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Sync Sekarang')
                ->action(function () {
                    try {
                        $result = app(TopupPriceService::class)->syncPrices();

                        Notification::make()
                            ->title('Sync berhasil')
                            ->body("{$result['updated']} produk diupdate, {$result['skipped']} dilewati.")
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Sync gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            CreateAction::make(),
        ];
    }
}
