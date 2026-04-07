<?php

namespace App\Filament\Admin\Resources\Articles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Konten Artikel')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) =>
                            $set('slug', Str::slug($state))
                        )
                        ->columnSpanFull(),

                    TextInput::make('slug')
                        ->label('Slug URL')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Diisi otomatis dari judul. Contoh: cara-top-up-mobile-legends')
                        ->columnSpanFull(),

                    TextInput::make('excerpt')
                        ->label('Ringkasan (Excerpt)')
                        ->maxLength(300)
                        ->helperText('Ditampilkan di halaman daftar artikel.')
                        ->columnSpanFull(),

                    RichEditor::make('content')
                        ->label('Isi Artikel')
                        ->required()
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('articles/attachments')
                        ->columnSpanFull(),
                ]),

            Section::make('Gambar & Publikasi')
                ->schema([
                    FileUpload::make('thumbnail')
                        ->label('Thumbnail')
                        ->image()
                        ->disk('public')
                        ->directory('articles')
                        ->helperText('Rasio 16:9 disarankan.'),

                    DateTimePicker::make('published_at')
                        ->label('Tanggal Publikasi')
                        ->nullable()
                        ->helperText('Kosongkan untuk tidak mengatur waktu tertentu.'),

                    Toggle::make('is_published')
                        ->label('Publikasikan')
                        ->default(false),
                ])
                ->columns(2),
        ]);
    }
}
