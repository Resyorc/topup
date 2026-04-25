<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" icon="heroicon-o-sparkles" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generate">Generate Draft Artikel</span>
                <span wire:loading wire:target="generate">Sedang membuat draft...</span>
            </x-filament::button>
        </div>
    </form>

    @if($this->result)
        <div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-6 dark:border-green-800 dark:bg-green-950">
            <h2 class="mb-4 text-lg font-bold text-green-800 dark:text-green-200">
                Draft Artikel AI Berhasil Dibuat
            </h2>

            <div class="space-y-3 text-sm">
                <div>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Judul:</span>
                    <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $this->result['title'] ?? '-' }}</p>
                </div>
                <div>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Slug:</span>
                    <code class="rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">{{ $this->result['slug'] ?? '-' }}</code>
                </div>
                <div>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Excerpt:</span>
                    <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $this->result['excerpt'] ?? '-' }}</p>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Meta Title:</span>
                        <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $this->result['meta_title'] ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Meta Description:</span>
                        <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $this->result['meta_description'] ?? '-' }}</p>
                    </div>
                </div>
                @if(!empty($this->result['internal_links']))
                    <div>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Saran Internal Link:</span>
                        <ul class="mt-1 list-disc pl-4 text-gray-700 dark:text-gray-300">
                            @foreach($this->result['internal_links'] as $link)
                                <li>{{ $link }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="mt-4">
                <a href="{{ route('filament.admin.resources.ai-actions.view', $this->result['ai_action_id']) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    Lihat Draft di Approval Center
                </a>
            </div>
        </div>
    @endif
</x-filament-panels::page>
