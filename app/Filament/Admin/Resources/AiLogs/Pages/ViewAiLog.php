<?php

namespace App\Filament\Admin\Resources\AiLogs\Pages;

use App\Filament\Admin\Resources\AiLogs\AiLogResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ViewAiLog extends ViewRecord
{
    protected static string $resource = AiLogResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Request')
                ->schema([
                    TextInput::make('module')->label('Modul')->disabled(),
                    TextInput::make('feature')->label('Fitur')->disabled(),
                    TextInput::make('model')->label('Model AI')->disabled(),
                    TextInput::make('status')->label('Status')->disabled(),
                    TextInput::make('input_tokens')->label('Input Tokens')->disabled(),
                    TextInput::make('output_tokens')->label('Output Tokens')->disabled(),
                    TextInput::make('total_tokens')->label('Total Tokens')->disabled(),
                ])->columns(3),

            Section::make('Prompt')
                ->schema([
                    Textarea::make('prompt')->label('Prompt')->disabled()->rows(6)->columnSpanFull(),
                ]),

            Section::make('Response AI')
                ->schema([
                    Textarea::make('response')->label('Response')->disabled()->rows(10)->columnSpanFull(),
                ]),

            Section::make('Error')
                ->schema([
                    Textarea::make('error_message')->label('Error Message')->disabled()->rows(4)->columnSpanFull(),
                ])
                ->visible(fn ($record) => ! empty($record->error_message)),
        ]);
    }
}
