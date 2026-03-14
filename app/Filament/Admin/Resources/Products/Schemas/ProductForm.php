<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Services\TopupPriceService;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('game_id')
                    ->relationship('game', 'name')
                    ->required(),
                TextInput::make('provider_sku')
                    ->required(),
                TextInput::make('name')
                    ->required(),
TextInput::make('price_cost')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $cost = (float) $get('price_cost');
                        $flat = (float) $get('margin_flat');
                        $percent = (float) $get('margin_percent');
                        $sell = app(TopupPriceService::class)->calculateSellPrice($cost, $flat, $percent);
                        $set('price_sell', $sell);
                    }),
                TextInput::make('margin_flat')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $cost = (float) $get('price_cost');
                        $flat = (float) $get('margin_flat');
                        $percent = (float) $get('margin_percent');
                        $sell = app(TopupPriceService::class)->calculateSellPrice($cost, $flat, $percent);
                        $set('price_sell', $sell);
                    }),
                TextInput::make('margin_percent')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->suffix('%')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $cost = (float) $get('price_cost');
                        $flat = (float) $get('margin_flat');
                        $percent = (float) $get('margin_percent');
                        $sell = app(TopupPriceService::class)->calculateSellPrice($cost, $flat, $percent);
                        $set('price_sell', $sell);
                    }),
                TextInput::make('price_sell')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('Rp')
                    ->readOnly(),
                Toggle::make('is_available')
                    ->default(true)
                    ->required(),
            ])->columns(2);
    }
}
