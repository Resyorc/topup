<?php

namespace App\Filament\Admin\Resources\Permissions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PermissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Permission')
                ->required()
                ->maxLength(100)
                ->placeholder('contoh: view transactions, manage products')
                ->helperText('Gunakan format huruf kecil dengan spasi, contoh: view transactions'),

            TextInput::make('guard_name')
                ->label('Guard')
                ->default('web')
                ->required()
                ->maxLength(50),
        ]);
    }
}
