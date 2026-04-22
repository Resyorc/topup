<?php

namespace App\Filament\Admin\Resources\Transactions\Tables;

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
                        'success'    => 'success',
                        'failed'     => 'danger',
                        'processing' => 'warning',
                        'pending'    => 'info',
                        default      => 'gray',
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
                    ->label('Status')
                    ->options([
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'success'    => 'Success',
                        'failed'     => 'Failed',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('sync_digiflazz')
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
                                $product = $record->load('product.game')->product;
                                $customerNo = $record->customer_game_id.($record->customer_zone_id ?? '');

                                $result = app(DigiflazzService::class)->checkTransactionStatus(
                                    $product->provider_sku,
                                    $customerNo,
                                    $record->invoice_id,
                                );

                                if (empty($result)) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Tidak ada data')
                                        ->body('Digiflazz tidak mengembalikan data untuk invoice ini.')
                                        ->send();

                                    return;
                                }

                                $status = strtolower($result['status'] ?? '');

                                if ($status === 'sukses') {
                                    $record->update([
                                        'status' => 'success',
                                        'sn'     => $result['sn'] ?? null,
                                    ]);

                                    $gameId = $product->game_id ?? null;
                                    if ($gameId) {
                                        Game::where('id', $gameId)->increment('total_sold');
                                    }

                                    app(LoyaltyService::class)->awardFromTransaction($record);

                                    Notification::make()
                                        ->success()
                                        ->title('Transaksi diupdate: SUCCESS')
                                        ->body('SN: '.($result['sn'] ?: '-'))
                                        ->send();

                                } elseif ($status === 'gagal') {
                                    $record->update([
                                        'status'         => 'failed',
                                        'failure_reason' => $result['rc'] ?? 'Sync manual admin',
                                    ]);

                                    if ($record->payment_method === 'COIN' && $record->user_id) {
                                        $user = User::find($record->user_id);
                                        if ($user) {
                                            $refundAmount = (int) ($record->amount + $record->fee);
                                            app(CoinService::class)->credit(
                                                $user,
                                                $refundAmount,
                                                'Refund otomatis (sync admin): '.$record->invoice_id,
                                                $record->invoice_id,
                                            );
                                        }
                                    }

                                    Notification::make()
                                        ->danger()
                                        ->title('Transaksi diupdate: FAILED')
                                        ->body('RC: '.($result['rc'] ?? '-')
                                            .($record->payment_method === 'COIN' ? ' | Coin dikembalikan.' : ''))
                                        ->send();

                                } else {
                                    Notification::make()
                                        ->info()
                                        ->title('Status Digiflazz: '.strtoupper($status ?: 'unknown'))
                                        ->body('Transaksi belum selesai diproses Digiflazz. Coba sync ulang nanti.')
                                        ->send();
                                }

                            } catch (\Exception $e) {
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
                        ->modalDescription('Gunakan jika Digiflazz tidak bisa dihubungi. Jika bayar via Coin, coin dikembalikan otomatis.')
                        ->modalSubmitActionLabel('Tandai Gagal')
                        ->action(function (Transaction $record, array $data): void {
                            $record->update([
                                'status'         => 'failed',
                                'failure_reason' => $data['failure_reason'],
                            ]);

                            if ($record->payment_method === 'COIN' && $record->user_id) {
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

                            Notification::make()
                                ->danger()
                                ->title('Transaksi ditandai FAILED')
                                ->body($record->invoice_id
                                    .($record->payment_method === 'COIN' ? ' | Coin dikembalikan.' : ''))
                                ->send();
                        }),
                ])->label('Aksi')->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->toolbarActions([]);
    }
}
