<?php

namespace App\Filament\Admin\Pages\AI;

use App\Services\AI\AiClient;
use App\Services\AI\SeoAiService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SeoAssistant extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.admin.pages.ai.seo-assistant';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static UnitEnum|string|null $navigationGroup = 'AI Assistant';

    protected static ?string $navigationLabel = 'SEO Assistant';

    protected static ?string $title = 'AI SEO Assistant';

    protected static ?int $navigationSort = 42;

    public ?array $data = [];

    public ?array $result = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff']);
    }

    public function mount(): void
    {
        $this->form->fill(['target_type' => 'game']);
    }

    public function form(Schema $schema): Schema
    {
        $gameOptions = \App\Models\Game::where('is_active', true)
            ->orderBy('name')->pluck('name', 'id')->toArray();

        $articleOptions = \App\Models\Article::orderByDesc('created_at')
            ->limit(50)->pluck('title', 'id')->toArray();

        return $schema
            ->components([
                Select::make('target_type')
                    ->label('Target Halaman')
                    ->required()
                    ->options([
                        'game'    => 'Game / Halaman Order',
                        'article' => 'Artikel / Blog',
                    ])
                    ->live()
                    ->default('game'),

                Select::make('game_id')
                    ->label('Pilih Game')
                    ->options($gameOptions)
                    ->searchable()
                    ->visible(fn ($get) => $get('target_type') === 'game')
                    ->required(fn ($get) => $get('target_type') === 'game'),

                Select::make('article_id')
                    ->label('Pilih Artikel')
                    ->options($articleOptions)
                    ->searchable()
                    ->visible(fn ($get) => $get('target_type') === 'article')
                    ->required(fn ($get) => $get('target_type') === 'article'),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $data    = $this->form->getState();
        $service = new SeoAiService(AiClient::make());

        try {
            if ($data['target_type'] === 'game') {
                $this->result = $service->generateForGame((int) $data['game_id'], auth()->id());
            } else {
                $this->result = $service->generateForArticle((int) $data['article_id'], auth()->id());
            }

            Notification::make()->title('Metadata SEO berhasil digenerate')->success()->send();

        } catch (\Throwable $e) {
            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
        }
    }
}
