<?php

namespace App\Filament\Admin\Resources\ProviderProducts\Pages;

use App\Filament\Admin\Pages\AiSkuSuggestionsPage;
use App\Filament\Admin\Resources\ProviderProducts\ProviderProductResource;
use App\Models\ProviderProduct;
use App\Services\AiSkuAnalyzerService;
use App\Services\AutoPilotService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListProviderProducts extends ListRecords
{
    protected static string $resource = ProviderProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cleanupInactive')
                ->label('Cleanup SKU Tidak Aktif')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus SKU Tidak Aktif')
                ->modalDescription('Tindakan ini akan menghapus semua SKU yang statusnya tidak aktif dan belum dipetakan ke Produk Nuvelo. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Bersihkan Sampah')
                ->action(function () {
                    $deleted = ProviderProduct::where('is_active', false)->whereNull('product_id')->delete();
                    Notification::make()
                        ->title('Cleanup Selesai')
                        ->body("Berhasil menghapus {$deleted} SKU tidak aktif.")
                        ->success()
                        ->send();
                }),

            Action::make('autoPilot')
                ->label('Auto-Pilot (Sync + AI)')
                ->icon('heroicon-o-rocket-launch')
                ->color('primary')
                ->visible(fn () => auth()->user()->hasAnyRole(['Super Admin', 'Staff']))
                ->requiresConfirmation()
                ->modalHeading('Jalankan Auto-Pilot')
                ->modalDescription('Auto-Pilot akan menjalankan 3 langkah sekaligus: (1) sync harga terbaru dari Digiflazz, (2) analisis SKU baru dengan AI + prediksi margin berdasarkan data penjualan, (3) buat produk baru secara otomatis. SKU yang tidak direkomendasikan AI akan diarahkan ke halaman Saran AI untuk review manual.')
                ->modalSubmitActionLabel('Jalankan Sekarang')
                ->action(function () {
                    try {
                        $result = app(AutoPilotService::class)->run();

                        $body = "{$result['synced']} produk diupdate harganya. {$result['created']} produk baru dibuat.";
                        if ($result['skipped'] > 0) {
                            $body .= " {$result['skipped']} SKU dilewati.";
                        }
                        if ($result['ai_error']) {
                            $body .= " ⚠ AI: {$result['ai_error']}";
                        }

                        Notification::make()
                            ->title('Auto-Pilot selesai')
                            ->body($body)
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Auto-Pilot gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('analyzeWithAi')
                ->label('Analisis dengan AI')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->visible(fn () => auth()->user()->hasAnyRole(['Super Admin', 'Staff']))
                ->requiresConfirmation()
                ->modalHeading('Analisis SKU dengan AI')
                ->modalDescription('AI akan menganalisis SKU yang belum dipetakan, menyarankan nama produk, game, dan margin harga. Hasilnya bisa di-review satu per satu sebelum dibuat. Cocok jika ingin kontrol lebih sebelum produk masuk katalog.')
                ->modalSubmitActionLabel('Mulai Analisis')
                ->action(function () {
                    try {
                        $result = app(AiSkuAnalyzerService::class)->analyze();

                        if ($result['error']) {
                            Notification::make()
                                ->title('Analisis gagal')
                                ->body($result['error'])
                                ->danger()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Analisis selesai')
                            ->body("{$result['count']} saran produk siap direview.")
                            ->success()
                            ->send();

                        $this->redirect(AiSkuSuggestionsPage::getUrl());
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Terjadi kesalahan')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
