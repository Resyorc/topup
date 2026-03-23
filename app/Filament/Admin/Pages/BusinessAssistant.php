<?php

namespace App\Filament\Admin\Pages;

use App\Services\BusinessAgentService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class BusinessAssistant extends Page
{
    protected string $view = 'filament.admin.pages.business-assistant';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = 'Business Assistant';

    protected static ?string $title = 'Business Assistant';

    protected static ?int $navigationSort = 10;

    // ── State ─────────────────────────────────────────────────────────────────

    /** Pesan untuk API (termasuk tool calls/results) */
    public array $apiMessages = [];

    /** Pesan untuk tampilan UI */
    public array $displayMessages = [];

    /** Input pengguna */
    public string $userInput = '';

    /** Apakah sedang proses */
    public bool $isProcessing = false;

    /** Tindakan write yang menunggu konfirmasi */
    public ?array $pendingAction = null;

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Super Admin') ?? false;
    }

    public function mount(): void
    {
        $service = app(BusinessAgentService::class);
        $this->apiMessages = [
            ['role' => 'system', 'content' => $service->getSystemPrompt()],
        ];
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function sendMessage(): void
    {
        $input = trim($this->userInput);

        if (empty($input) || $this->isProcessing) {
            return;
        }

        $this->isProcessing = true;
        $this->userInput = '';
        $this->pendingAction = null;

        // Tampilkan pesan user di UI
        $this->displayMessages[] = [
            'role'    => 'user',
            'content' => $input,
        ];

        // Tambahkan ke API messages
        $this->apiMessages[] = ['role' => 'user', 'content' => $input];

        try {
            $service = app(BusinessAgentService::class);
            $result = $service->chat($this->apiMessages);

            $this->handleLLMResult($result);
        } catch (\Throwable $e) {
            $this->displayMessages[] = [
                'role'    => 'assistant',
                'content' => '⚠️ Terjadi kesalahan: '.$e->getMessage(),
                'error'   => true,
            ];
        } finally {
            $this->isProcessing = false;
        }
    }

    public function confirmAction(): void
    {
        if (! $this->pendingAction) {
            return;
        }

        $this->isProcessing = true;

        // Tampilkan konfirmasi di UI
        $this->displayMessages[] = [
            'role'    => 'user',
            'content' => '✅ Dikonfirmasi: '.$this->pendingAction['description'],
        ];

        try {
            $service = app(BusinessAgentService::class);
            $result = $service->executeConfirmedAction(
                $this->apiMessages,
                $this->pendingAction['tool_call_id'],
                $this->pendingAction['tool'],
                $this->pendingAction['args'],
            );

            $this->pendingAction = null;
            $this->handleLLMResult($result);

            Notification::make()
                ->title('Tindakan berhasil dijalankan')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->pendingAction = null;
            $this->displayMessages[] = [
                'role'    => 'assistant',
                'content' => '⚠️ Gagal menjalankan tindakan: '.$e->getMessage(),
                'error'   => true,
            ];
        } finally {
            $this->isProcessing = false;
        }
    }

    public function cancelAction(): void
    {
        if (! $this->pendingAction) {
            return;
        }

        $cancelledAction = $this->pendingAction;
        $this->pendingAction = null;

        // Beritahu AI bahwa tindakan dibatalkan
        $this->apiMessages[] = [
            'role'         => 'tool',
            'tool_call_id' => $cancelledAction['tool_call_id'],
            'name'         => $cancelledAction['tool'],
            'content'      => json_encode(['cancelled' => true, 'reason' => 'Dibatalkan oleh pengguna.']),
        ];

        $this->displayMessages[] = [
            'role'    => 'user',
            'content' => '❌ Dibatalkan: '.$cancelledAction['description'],
        ];

        try {
            $this->isProcessing = true;
            $service = app(BusinessAgentService::class);
            $result = $service->chat($this->apiMessages);
            $this->handleLLMResult($result);
        } catch (\Throwable $e) {
            $this->displayMessages[] = [
                'role'    => 'assistant',
                'content' => '⚠️ '.$e->getMessage(),
                'error'   => true,
            ];
        } finally {
            $this->isProcessing = false;
        }
    }

    public function clearConversation(): void
    {
        $service = app(BusinessAgentService::class);
        $this->apiMessages = [
            ['role' => 'system', 'content' => $service->getSystemPrompt()],
        ];
        $this->displayMessages = [];
        $this->pendingAction = null;
        $this->userInput = '';
        $this->isProcessing = false;
    }

    public function getProviderInfo(): string
    {
        return app(BusinessAgentService::class)->getProviderInfo();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function handleLLMResult(array $result): void
    {
        $this->apiMessages = $result['messages'];

        if ($result['type'] === 'message') {
            $this->displayMessages[] = [
                'role'    => 'assistant',
                'content' => $result['content'],
            ];
        } elseif ($result['type'] === 'action') {
            $this->pendingAction = $result;
            $this->displayMessages[] = [
                'role'    => 'assistant',
                'content' => '🔔 Saya akan: **'.$result['description'].'**',
                'is_action_preview' => true,
            ];
        } elseif ($result['type'] === 'error') {
            $this->displayMessages[] = [
                'role'    => 'assistant',
                'content' => '⚠️ '.$result['content'],
                'error'   => true,
            ];
        }
    }
}
