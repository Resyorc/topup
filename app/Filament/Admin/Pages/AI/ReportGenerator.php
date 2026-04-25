<?php

namespace App\Filament\Admin\Pages\AI;

use App\Services\AI\AiClient;
use App\Services\AI\ReportAiService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ReportGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.admin.pages.ai.report-generator';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'AI Assistant';

    protected static ?string $navigationLabel = 'Report Generator';

    protected static ?string $title = 'AI Report Generator';

    protected static ?int $navigationSort = 47;

    public ?array $data = [];

    public ?array $result = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin']);
    }

    public function mount(): void
    {
        $this->form->fill([
            'report_type' => 'daily',
            'date'        => now()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('report_type')
                    ->label('Jenis Laporan')
                    ->options([
                        'daily'   => 'Harian',
                        'weekly'  => 'Mingguan',
                        'monthly' => 'Bulanan',
                    ])
                    ->required()
                    ->default('daily')
                    ->live(),

                DatePicker::make('date')
                    ->label(fn ($get) => match ($get('report_type')) {
                        'weekly'  => 'Tanggal Mulai Minggu',
                        'monthly' => 'Bulan (pilih hari 1)',
                        default   => 'Tanggal',
                    })
                    ->required()
                    ->default(now()),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function generate(): void
    {
        $data    = $this->form->getState();
        $service = new ReportAiService(AiClient::make());

        try {
            $report = match ($data['report_type']) {
                'weekly'  => $service->generateWeeklyReport($data['date'], auth()->id()),
                'monthly' => $service->generateMonthlyReport($data['date'], auth()->id()),
                default   => $service->generateDailyReport($data['date'], auth()->id()),
            };

            $this->result = [
                'id'      => $report->id,
                'title'   => $report->title,
                'summary' => $report->summary,
                'content' => $report->content,
                'period'  => $report->period_start.' – '.$report->period_end,
            ];

            Notification::make()
                ->title('Laporan berhasil dibuat')
                ->body($report->title)
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()->title('Gagal generate laporan')->body($e->getMessage())->danger()->send();
        }
    }
}
