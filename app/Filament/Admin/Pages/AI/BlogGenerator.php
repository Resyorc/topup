<?php

namespace App\Filament\Admin\Pages\AI;

use App\Services\AI\AiClient;
use App\Services\AI\BlogAiService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BlogGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.admin.pages.ai.blog-generator';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static UnitEnum|string|null $navigationGroup = 'AI Assistant';

    protected static ?string $navigationLabel = 'Blog Generator';

    protected static ?string $title = 'AI Blog Generator';

    protected static ?int $navigationSort = 41;

    public ?array $data = [];

    public ?array $result = null;

    public bool $loading = false;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff']);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $gameOptions = \App\Models\Game::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return $schema
            ->components([
                TextInput::make('topic')
                    ->label('Topik Artikel')
                    ->required()
                    ->maxLength(200)
                    ->placeholder('Contoh: Cara Top Up Mobile Legends Murah dan Cepat')
                    ->columnSpanFull(),

                TextInput::make('keyword')
                    ->label('Keyword Utama SEO')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Contoh: top up Mobile Legends'),

                Select::make('tone')
                    ->label('Tone')
                    ->options([
                        'friendly' => 'Ramah & Informatif',
                        'formal'   => 'Formal & Profesional',
                        'concise'  => 'Singkat & Padat',
                    ])
                    ->default('friendly'),

                Select::make('game_id')
                    ->label('Game Terkait (opsional)')
                    ->options($gameOptions)
                    ->nullable()
                    ->searchable()
                    ->columnSpanFull(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        $this->loading = true;

        try {
            $service      = new BlogAiService(AiClient::make());
            $this->result = $service->generateFromTopic(
                topic: $data['topic'],
                keyword: $data['keyword'],
                tone: $data['tone'] ?? 'friendly',
                gameId: $data['game_id'] ?? null,
                adminId: auth()->id(),
            );

            Notification::make()
                ->title('Draft artikel berhasil dibuat!')
                ->body("Draft AI untuk \"".($this->result['title'] ?? '')."\" tersimpan di Approval Center.")
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal generate artikel')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->loading = false;
        }
    }
}
