<?php

namespace App\Livewire\Admin;

use App\Services\BusinessAgentService;
use Livewire\Component;

class BusinessAgentFloat extends Component
{
    public bool $isOpen = false;

    public array $apiMessages = [];

    public array $displayMessages = [];

    public string $userInput = '';

    public bool $isProcessing = false;

    public ?array $pendingAction = null;

    public function mount(): void
    {
        $service = app(BusinessAgentService::class);
        $this->apiMessages = [
            ['role' => 'system', 'content' => $service->getSystemPrompt()],
        ];
    }

    public function toggle(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function sendMessage(): void
    {
        $input = trim($this->userInput);

        if (empty($input) || $this->isProcessing) {
            return;
        }

        $this->isProcessing = true;
        $this->userInput = '';
        $this->pendingAction = null;

        $this->displayMessages[] = ['role' => 'user', 'content' => $input];
        $this->apiMessages[] = ['role' => 'user', 'content' => $input];

        try {
            $result = app(BusinessAgentService::class)->chat($this->apiMessages);
            $this->handleResult($result);
        } catch (\Throwable $e) {
            $this->displayMessages[] = ['role' => 'assistant', 'content' => '⚠️ '.$e->getMessage(), 'error' => true];
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
        $action = $this->pendingAction;
        $this->pendingAction = null;

        $this->displayMessages[] = ['role' => 'user', 'content' => '✅ Dikonfirmasi'];

        try {
            $result = app(BusinessAgentService::class)->executeConfirmedAction(
                $this->apiMessages,
                $action['tool_call_id'],
                $action['tool'],
                $action['args'],
            );
            $this->handleResult($result);
        } catch (\Throwable $e) {
            $this->displayMessages[] = ['role' => 'assistant', 'content' => '⚠️ '.$e->getMessage(), 'error' => true];
        } finally {
            $this->isProcessing = false;
        }
    }

    public function cancelAction(): void
    {
        if (! $this->pendingAction) {
            return;
        }

        $action = $this->pendingAction;
        $this->pendingAction = null;

        $this->apiMessages[] = [
            'role' => 'tool',
            'tool_call_id' => $action['tool_call_id'],
            'name' => $action['tool'],
            'content' => json_encode(['cancelled' => true]),
        ];

        $this->displayMessages[] = ['role' => 'user', 'content' => '❌ Dibatalkan'];

        try {
            $this->isProcessing = true;
            $result = app(BusinessAgentService::class)->chat($this->apiMessages);
            $this->handleResult($result);
        } catch (\Throwable $e) {
            $this->displayMessages[] = ['role' => 'assistant', 'content' => '⚠️ '.$e->getMessage(), 'error' => true];
        } finally {
            $this->isProcessing = false;
        }
    }

    public function clearConversation(): void
    {
        $service = app(BusinessAgentService::class);
        $this->apiMessages = [['role' => 'system', 'content' => $service->getSystemPrompt()]];
        $this->displayMessages = [];
        $this->pendingAction = null;
        $this->userInput = '';
    }

    public function getGreeting(): string
    {
        $hour = now('Asia/Jakarta')->hour;
        $name = auth()->user()?->name ?? 'Admin';
        $firstName = explode(' ', $name)[0];

        $time = match (true) {
            $hour < 12 => 'Selamat Pagi',
            $hour < 15 => 'Selamat Siang',
            $hour < 18 => 'Selamat Sore',
            default    => 'Selamat Malam',
        };

        return "$time, $firstName";
    }

    public function getProviderInfo(): string
    {
        return app(BusinessAgentService::class)->getProviderInfo();
    }

    private function handleResult(array $result): void
    {
        $this->apiMessages = $result['messages'];

        if ($result['type'] === 'message') {
            $this->displayMessages[] = ['role' => 'assistant', 'content' => $result['content']];
        } elseif ($result['type'] === 'action') {
            $this->pendingAction = $result;
            $this->displayMessages[] = [
                'role' => 'assistant',
                'content' => '🔔 Saya akan: **'.$result['description'].'**',
                'is_action_preview' => true,
            ];
        } elseif ($result['type'] === 'error') {
            $this->displayMessages[] = ['role' => 'assistant', 'content' => '⚠️ '.$result['content'], 'error' => true];
        }
    }

    public function render()
    {
        return view('livewire.admin.business-agent-float');
    }
}
