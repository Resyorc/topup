<?php

namespace App\Filament\Admin\Resources\Transactions\Tables;

use App\Jobs\ProcessFulfilmentJob;
use App\Models\CoinTransaction;
use App\Models\Game;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CoinService;
use App\Services\DigiflazzService;
use App\Services\LoyaltyService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_id')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->default('Guest'),

                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),

                TextColumn::make('customer_game_id')
                    ->label('ID Game')
                    ->searchable(),

                TextColumn::make('customer_whatsapp')
                    ->label('WA')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('profit')
                    ->label('Profit')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'processing' => 'warning',
                        'pending' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('sn')
                    ->label('SN / Voucher')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Fulfilment')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'expired' => 'Expired',
                        'failed' => 'Failed',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('retry_fulfilment')
                        ->label('Hit Ulang Digiflazz')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn (Transaction $record): bool => $record->payment_status === 'paid'
                            && $record->fulfilment_status !== 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn (Transaction $record) => 'Hit ulang Digiflazz: '.$record->invoice_id)
                        ->modalDescription('Mengirim ulang transaksi ke Digiflazz memakai ref_id invoice yang sama. Jika request lama sebenarnya sudah masuk, Digiflazz akan mengembalikan status transaksi yang sudah ada.')
                        ->modalSubmitActionLabel('Hit Ulang')
                        ->action(function (Transaction $record): void {
                            try {
                                if ($record->payment_method === 'COIN') {
                                    $alreadyRefunded = CoinTransaction::where('reference_id', $record->invoice_id)
                                        ->where('type', 'credit')
                                        ->exists();

                                    if ($alreadyRefunded) {
                                        Notification::make()
                                            ->warning()
                                            ->title('Hit ulang dibatalkan')
                                            ->body('Coin untuk invoice ini sudah pernah dikembalikan. Buat pesanan baru atau debit saldo ulang sebelum fulfilment.')
                                            ->send();

                                        return;
                                    }
                                }

                                ProcessFulfilmentJob::dispatchSync($record->invoice_id, true);

                                $record->refresh();

                                Notification::make()
                                    ->success()
                                    ->title('Hit ulang selesai')
                                    ->body('Status sekarang: '.$record->status.' / '.$record->fulfilment_status)
                                    ->send();
                            } catch (\Throwable $e) {
                                Log::error('Manual Digiflazz retry failed', [
                                    'transaction_id' => $record->id,
                                    'invoice_id' => $record->invoice_id,
                                    'provider_sku' => $record->provider_sku,
                                    'message' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Hit ulang gagal')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    Action::make('sync_provider')
                        ->label('Sync Digiflazz')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (Transaction $record): bool => in_array($record->status, ['processing', 'pending']))
                        ->requiresConfirmation()
                        ->modalHeading(fn (Transaction $record) => 'Sync Status: '.$record->invoice_id)
                        ->modalDescription('Cek status terkini dari Digiflazz dan update otomatis jika sudah sukses atau gagal.')
                        ->modalSubmitActionLabel('Sync Sekarang')
                        ->action(function (Transaction $record): void {
                            try {
                                $record->load('product.game');
                                $customerNo = (string) $record->customer_game_id.(string) ($record->customer_zone_id ?? '');
                                $result = app(DigiflazzService::class)->checkTransactionStatus(
                                    (string) $record->provider_sku,
                                    $customerNo,
                                    $record->invoice_id
                                );

                                if (empty($result)) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Tidak ada data')
                                        ->body('Digiflazz tidak mengembalikan data untuk invoice ini.')
                                        ->send();

                                    return;
                                }

                                $status = strtolower((string) ($result['status'] ?? $result['transaction_status'] ?? ''));
                                $serialNumber = $result['sn'] ?? $result['serial_number'] ?? null;
                                $failureReason = $result['rc'] ?? $result['message'] ?? $result['note'] ?? 'Sync manual admin';

                                if (in_array($status, ['sukses', 'success', 'sandbox - sukses'], true)) {
                                    $record->update([
                                        'status' => 'success',
                                        'fulfilment_status' => 'success',
                                        'sn' => $serialNumber,
                                        'failure_reason' => null,
                                    ]);

                                    $gameId = $record->product?->game_id;
                                    if ($gameId) {
                                        Game::where('id', $gameId)->increment('total_sold');
                                    }

                                    app(LoyaltyService::class)->awardFromTransaction($record);

                                    Notification::make()
                                        ->success()
                                        ->title('Transaksi diupdate: SUCCESS')
                                        ->body('SN: '.($serialNumber ?: '-'))
                                        ->send();

                                } elseif (in_array($status, ['gagal', 'failed'], true)) {
                                    $record->update([
                                        'status' => 'failed',
                                        'fulfilment_status' => 'failed',
                                        'failure_reason' => $failureReason,
                                    ]);

                                    $coinRefunded = false;
                                    if ($record->payment_method === 'COIN' && $record->user_id) {
                                        $alreadyRefunded = CoinTransaction::where('reference_id', $record->invoice_id)
                                            ->where('type', 'credit')
                                            ->exists();

                                        if (! $alreadyRefunded) {
                                            $user = User::find($record->user_id);
                                            if ($user) {
                                                $refundAmount = (int) ($record->amount + $record->fee);
                                                app(CoinService::class)->credit(
                                                    $user,
                                                    $refundAmount,
                                                    'Refund otomatis (sync admin): '.$record->invoice_id,
                                                    $record->invoice_id,
                                                );
                                                $coinRefunded = true;
                                            }
                                        }
                                    }

                                    Notification::make()
                                        ->danger()
                                        ->title('Transaksi diupdate: FAILED')
                                        ->body('RC: '.($failureReason ?: '-')
                                            .($coinRefunded ? ' | Coin dikembalikan.' : ($record->payment_method === 'COIN' ? ' | Coin sudah dikembalikan sebelumnya.' : '')))
                                        ->send();

                                } else {
                                    Notification::make()
                                        ->info()
                                        ->title('Status Provider: '.strtoupper($status ?: 'unknown'))
                                        ->body('Transaksi belum selesai diproses Digiflazz. Coba sync ulang nanti.')
                                        ->send();
                                }

                            } catch (\Exception $e) {
                                Log::error('Manual Digiflazz sync failed', [
                                    'transaction_id' => $record->id,
                                    'invoice_id' => $record->invoice_id,
                                    'provider_sku' => $record->provider_sku,
                                    'message' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Sync gagal')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    Action::make('mark_failed')
                        ->label('Paksa Gagal')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Transaction $record): bool => in_array($record->status, ['processing', 'pending']))
                        ->form([
                            \Filament\Forms\Components\TextInput::make('failure_reason')
                                ->label('Alasan Gagal')
                                ->placeholder('Contoh: Digiflazz gagal, refund manual')
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading(fn (Transaction $record) => 'Paksa Gagal: '.$record->invoice_id)
                        ->modalDescription('Gunakan jika provider tidak bisa dihubungi. Jika bayar via Coin, coin dikembalikan otomatis.')
                        ->modalSubmitActionLabel('Tandai Gagal')
                        ->action(function (Transaction $record, array $data): void {
                            $record->update([
                                'status' => 'failed',
                                'fulfilment_status' => 'failed',
                                'failure_reason' => $data['failure_reason'],
                            ]);

                            $coinRefunded = false;
                            if ($record->payment_method === 'COIN' && $record->user_id) {
                                $alreadyRefunded = CoinTransaction::where('reference_id', $record->invoice_id)
                                    ->where('type', 'credit')
                                    ->exists();

                                if ($alreadyRefunded) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Refund dilewati — sudah pernah diproses')
                                        ->body('Coin untuk '.$record->invoice_id.' sudah dikembalikan sebelumnya.')
                                        ->send();
                                } else {
                                    try {
                                        $user = User::find($record->user_id);
                                        if ($user) {
                                            $refundAmount = (int) ($record->amount + $record->fee);
                                            app(CoinService::class)->credit(
                                                $user,
                                                $refundAmount,
                                                'Refund manual oleh admin: '.$record->invoice_id,
                                                $record->invoice_id,
                                            );
                                            $coinRefunded = true;
                                        }
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->warning()
                                            ->title('Status diupdate, tapi refund coin gagal')
                                            ->body($e->getMessage())
                                            ->send();

                                        return;
                                    }
                                }
                            }

                            Notification::make()
                                ->danger()
                                ->title('Transaksi ditandai FAILED')
                                ->body($record->invoice_id
                                    .($coinRefunded ? ' | Coin dikembalikan.' : ''))
                                ->send();
                        }),
                ])->label('Aksi')->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->toolbarActions([]);
    }
}
