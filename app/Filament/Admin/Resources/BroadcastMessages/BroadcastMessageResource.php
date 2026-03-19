<?php

namespace App\Filament\Admin\Resources\BroadcastMessages;

use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Filament\Admin\Resources\BroadcastMessages\Pages\CreateBroadcastMessage;
use App\Filament\Admin\Resources\BroadcastMessages\Pages\EditBroadcastMessage;
use App\Filament\Admin\Resources\BroadcastMessages\Pages\ListBroadcastMessages;
use App\Filament\Admin\Resources\BroadcastMessages\Schemas\BroadcastMessageForm;
use App\Filament\Admin\Resources\BroadcastMessages\Tables\BroadcastMessagesTable;
use App\Models\BroadcastMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BroadcastMessageResource extends Resource
{
    protected static ?string $model = BroadcastMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $recordTitleAttribute = 'message';

    public static function form(Schema $schema): Schema
    {
        return BroadcastMessageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BroadcastMessagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBroadcastMessages::route('/'),
            'create' => CreateBroadcastMessage::route('/create'),
            'edit' => EditBroadcastMessage::route('/{record}/edit'),
        ];
    }
}
