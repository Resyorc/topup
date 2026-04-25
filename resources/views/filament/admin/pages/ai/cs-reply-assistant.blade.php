<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" icon="heroicon-o-sparkles" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generate">Generate Draft Balasan</span>
                <span wire:loading wire:target="generate">Sedang membuat balasan...</span>
            </x-filament::button>
        </div>
    </form>

    @if($this->result)
        <div class="mt-6 space-y-4">
            @if($this->result['escalation_needed'] ?? false)
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
                    <div class="flex items-center gap-2">
                        <span class="text-red-600 text-lg">🚨</span>
                        <span class="font-semibold text-red-700 dark:text-red-300">Kasus Ini Perlu Eskalasi</span>
                    </div>
                    @if($this->result['escalation_reason'])
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $this->result['escalation_reason'] }}</p>
                    @endif
                </div>
            @endif

            <div class="rounded-xl border bg-white p-5 dark:bg-gray-800">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800 dark:text-gray-200">💬 Draft Balasan</h3>
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                        Tone: {{ ucfirst($this->result['tone_used'] ?? '') }}
                    </span>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                    <p class="whitespace-pre-wrap text-sm text-gray-900 dark:text-gray-100">{{ $this->result['reply'] ?? '' }}</p>
                </div>

                <div class="mt-3 flex gap-2">
                    <button type="button"
                        onclick="navigator.clipboard.writeText('{{ addslashes($this->result['reply'] ?? '') }}')"
                        class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700">
                        📋 Salin Balasan
                    </button>
                </div>
            </div>

            @if(!empty($this->result['suggested_action']))
                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950">
                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">💡 Saran Tindakan Admin</p>
                    <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">{{ $this->result['suggested_action'] }}</p>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
