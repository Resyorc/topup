<?php

namespace App\Filament\Admin\Resources\AiKnowledgeSources\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AiKnowledgeSourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'policy'   => 'info',
                        'sop'      => 'warning',
                        'template' => 'success',
                        'faq'      => 'gray',
                        'guide'    => 'primary',
                        default    => 'gray',
                    }),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'policy'   => 'Kebijakan',
                        'sop'      => 'SOP',
                        'template' => 'Template',
                        'faq'      => 'FAQ',
                        'guide'    => 'Panduan',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
