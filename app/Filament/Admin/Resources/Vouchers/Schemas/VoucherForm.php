<?php

namespace App\Filament\Admin\Resources\Vouchers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Informasi Voucher')->schema([
                TextInput::make('code')
                    ->label('Kode Voucher')
                    ->placeholder('Contoh: PROMO10')
                    ->required()
                    ->maxLength(50)
                    ->alphaDash()
                    ->afterStateUpdated(fn ($set, $state) => $set('code', strtoupper($state)))
                    ->live(onBlur: true),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->inline(false),

                Toggle::make('is_public')
                    ->label('Tampilkan di Nova (CS AI)')
                    ->helperText('Jika aktif, Nova akan memberitahu user tentang kode ini saat ditanya promo.')
                    ->default(false)
                    ->inline(false),
            ])->columns(2),

            Section::make('Konfigurasi Diskon')->schema([
                Select::make('type')
                    ->label('Tipe Diskon')
                    ->options([
                        'flat'    => 'Nominal (Rp)',
                        'percent' => 'Persentase (%)',
                    ])
                    ->required()
                    ->live(),

                TextInput::make('value')
                    ->label(fn ($get) => $get('type') === 'percent' ? 'Nilai (%)' : 'Nilai (Rp)')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->suffix(fn ($get) => $get('type') === 'percent' ? '%' : null)
                    ->prefix(fn ($get) => $get('type') !== 'percent' ? 'Rp' : null),

                TextInput::make('min_amount')
                    ->label('Minimum Transaksi (Rp)')
                    ->helperText('Biarkan 0 jika tidak ada minimum.')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),

                TextInput::make('max_discount')
                    ->label('Maks. Diskon (Rp)')
                    ->helperText('Hanya berlaku untuk tipe persentase. Kosongkan jika tidak ada batas.')
                    ->numeric()
                    ->minValue(1)
                    ->nullable()
                    ->prefix('Rp')
                    ->visible(fn ($get) => $get('type') === 'percent'),
            ])->columns(2),

            Section::make('Batas Penggunaan & Masa Berlaku')->schema([
                TextInput::make('usage_limit')
                    ->label('Batas Penggunaan')
                    ->helperText('Kosongkan untuk unlimited.')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),

                DateTimePicker::make('valid_from')
                    ->label('Berlaku Mulai')
                    ->nullable()
                    ->displayFormat('d M Y, H:i'),

                DateTimePicker::make('valid_until')
                    ->label('Berlaku Sampai')
                    ->nullable()
                    ->displayFormat('d M Y, H:i'),
            ])->columns(3),

        ]);
    }
}
