<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" icon="heroicon-o-sparkles" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generate">Generate Laporan</span>
                <span wire:loading wire:target="generate">Sedang membuat laporan...</span>
            </x-filament::button>
        </div>
    </form>

    @if($this->result)
        <div class="mt-6 rounded-xl border bg-white p-5 dark:bg-gray-800">
            <div class="mb-4 flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $this->result['title'] ?? '' }}</h2>
                    <p class="text-sm text-gray-500">Periode: {{ $this->result['period'] ?? '' }}</p>
                </div>
                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">
                    Laporan #{{ $this->result['id'] ?? '' }}
                </span>
            </div>

            @if(!empty($this->result['summary']))
                <div class="mb-5 rounded-lg bg-blue-50 p-4 dark:bg-blue-950">
                    <p class="mb-1 text-xs font-semibold uppercase text-blue-500">Ringkasan Eksekutif</p>
                    <p class="text-sm text-blue-800 dark:text-blue-200">{{ $this->result['summary'] }}</p>
                </div>
            @endif

            @if(!empty($this->result['content']))
                <div class="prose prose-sm dark:prose-invert max-w-none border-t pt-4 dark:border-gray-700">
                    {!! $this->result['content'] !!}
                </div>
            @endif

            <div class="mt-4 border-t pt-4 dark:border-gray-700">
                <p class="text-xs text-gray-400">Laporan ini tersimpan di database. ID: {{ $this->result['id'] ?? '' }}</p>
            </div>
        </div>
    @endif
</x-filament-panels::page>
