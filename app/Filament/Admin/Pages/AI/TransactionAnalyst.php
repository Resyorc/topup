<?php

namespace App\Filament\Admin\Pages\AI;

use App\Services\AI\AiClient;
use App\Services\AI\TransactionAiService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TransactionAnalyst extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.admin.pages.ai.transaction-analyst';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static UnitEnum|string|null $navigationGroup = 'AI Assistant';

    protected static ?string $navigationLabel = 'Transaction Analyst';

    protected static ?string $title = 'AI Transaction Analyst';

    protected static ?int $navigationSort = 45;

    public ?array $data = [];

    public ?array $result = null;

    public ?array $stuckPending = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff']);
    }

    public function mount(): void
    {
        $this->form->fill([
            'date' => now()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Tanggal Analisis')
                    ->required()
                    ->default(now()),
            ])
            ->statePath('data');
    }

    public function analyze(): void
    {
        $data    = $this->form->getState();
        $service = new TransactionAiService(AiClient::make());

        try {
            $this->result = $service->dailySummary($data['date'], auth()->id());
            Notification::make()->title('Analisis transaksi selesai')->success()->send();

        } catch (\Throwable $e) {
            Notification::make()->title('Gagal menganalisis transaksi')->body($e->getMessage())->danger()->send();
        }
    }

    public function detectPending(): void
    {
        $service            = new TransactionAiService(AiClient::make());
        $this->stuckPending = $service->detectStuckPending();

        $count = count($this->stuckPending);
        Notification::make()
            ->title("Ditemukan {$count} transaksi pending bermasalah")
            ->color($count > 0 ? 'warning' : 'success')
            ->send();
    }
}
