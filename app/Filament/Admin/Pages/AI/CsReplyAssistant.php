<?php

namespace App\Filament\Admin\Pages\AI;

use App\Services\AI\AiClient;
use App\Services\AI\CsAiService;
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

class CsReplyAssistant extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.admin.pages.ai.cs-reply-assistant';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static UnitEnum|string|null $navigationGroup = 'AI Assistant';

    protected static ?string $navigationLabel = 'CS Reply Assistant';

    protected static ?string $title = 'AI CS Reply Assistant';

    protected static ?int $navigationSort = 44;

    public ?array $data = [];

    public ?array $result = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['Super Admin', 'Staff', 'CS']);
    }

    public function mount(): void
    {
        $this->form->fill(['tone' => 'friendly']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('customer_message')
                    ->label('Pesan Customer')
                    ->required()
                    ->rows(4)
                    ->placeholder('Salin pesan customer di sini...')
                    ->columnSpanFull(),

                TextInput::make('invoice_id')
                    ->label('Invoice ID (opsional)')
                    ->placeholder('INV-XXXXXX')
                    ->nullable(),

                Select::make('tone')
                    ->label('Tone Balasan')
                    ->options([
                        'friendly' => 'Ramah & Hangat',
                        'formal'   => 'Formal & Profesional',
                        'concise'  => 'Singkat & Langsung',
                        'firm'     => 'Tegas Namun Sopan',
                        'polite'   => 'Sangat Sopan',
                    ])
                    ->default('friendly'),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function generate(): void
    {
        $data    = $this->form->getState();
        $service = new CsAiService(AiClient::make());

        try {
            $this->result = $service->generateReply(
                customerMessage: $data['customer_message'],
                invoiceId: $data['invoice_id'] ?? null,
                tone: $data['tone'] ?? 'friendly',
                adminId: auth()->id(),
            );

            Notification::make()->title('Draft balasan CS siap')->success()->send();

        } catch (\Throwable $e) {
            Notification::make()->title('Gagal generate balasan')->body($e->getMessage())->danger()->send();
        }
    }
}
