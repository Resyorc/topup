<?php

namespace App\Filament\Admin\Resources\Transactions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_id')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('customer_game_id')
                    ->required(),
                TextInput::make('customer_zone_id'),
                TextInput::make('customer_whatsapp'),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('profit')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'paid' => 'Paid',
            'processing' => 'Processing',
            'success' => 'Success',
            'failed' => 'Failed',
        ])
                    ->default('pending')
                    ->required(),
                TextInput::make('sn'),
                Textarea::make('payment_url')
                    ->columnSpanFull(),
                TextInput::make('reference_id_provider'),
                TextInput::make('api_logs'),
            ]);
    }
}
