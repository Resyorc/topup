<?php

namespace App\Filament\Admin\Resources\AiKnowledgeSources\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AiKnowledgeSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Select::make('type')
                        ->label('Tipe')
                        ->required()
                        ->options([
                            'policy'   => 'Kebijakan / Policy',
                            'sop'      => 'SOP Operasional',
                            'template' => 'Template Balasan',
                            'faq'      => 'FAQ',
                            'guide'    => 'Panduan',
                        ]),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),

                    TextInput::make('source_url')
                        ->label('URL Sumber (opsional)')
                        ->url()
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Konten')
                ->schema([
                    Textarea::make('content')
                        ->label('Isi Knowledge')
                        ->required()
                        ->rows(15)
                        ->helperText('Tulis dalam format Markdown atau teks biasa. Konten ini akan dimasukkan ke system prompt AI.')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
