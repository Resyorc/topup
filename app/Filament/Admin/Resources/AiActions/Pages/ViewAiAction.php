<?php

namespace App\Filament\Admin\Resources\AiActions\Pages;

use App\Filament\Admin\Resources\AiActions\AiActionResource;
use App\Models\AiAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;

class ViewAiAction extends ViewRecord
{
    protected static string $resource = AiActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve Draft')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['draft', 'pending']))
                ->requiresConfirmation()
                ->modalDescription('Draft AI ini akan ditandai sebagai approved. Penerapan perubahan tetap dilakukan manual oleh admin.')
                ->action(function () {
                    $this->record->update([
                        'status'      => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                    Notification::make()->title('Aksi disetujui')->success()->send();
                    $this->refreshFormData(['status', 'approved_by', 'approved_at']);
                }),

            Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, ['draft', 'pending']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'rejected']);
                    Notification::make()->title('Aksi ditolak')->warning()->send();
                    $this->refreshFormData(['status']);
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Aksi')
                ->schema([
                    TextInput::make('module')->label('Modul')->disabled(),
                    TextInput::make('action_type')->label('Tipe Aksi')->disabled(),
                    TextInput::make('target_type')->label('Target Type')->disabled(),
                    TextInput::make('target_id')->label('Target ID')->disabled(),
                    TextInput::make('status')->label('Status')->disabled(),
                    TextInput::make('admin.name')->label('Dibuat Oleh')->disabled(),
                ])->columns(3),

            Section::make('Alasan')
                ->schema([
                    Textarea::make('reason')->label('Alasan AI')->disabled()->rows(3)->columnSpanFull(),
                ])
                ->visible(fn ($record) => ! empty($record->reason)),

            Section::make('Data Sebelum (Before)')
                ->schema([
                    KeyValue::make('before_data')->label('Before Data')->disabled()->columnSpanFull(),
                ])
                ->visible(fn ($record) => ! empty($record->before_data)),

            Section::make('Data Sesudah (After) — Rekomendasi AI')
                ->schema([
                    KeyValue::make('after_data')->label('After Data')->disabled()->columnSpanFull(),
                ]),
        ]);
    }
}
