<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" icon="heroicon-o-sparkles" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generate">Generate Metadata SEO</span>
                <span wire:loading wire:target="generate">Sedang generate...</span>
            </x-filament::button>
        </div>
    </form>

    @if($this->result)
        <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-950">
            <h2 class="mb-4 text-lg font-bold text-blue-800 dark:text-blue-200">
                🔍 Hasil Metadata SEO
            </h2>

            <div class="space-y-4 text-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-lg border bg-white p-4 dark:bg-gray-800">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Meta Title</p>
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $this->result['meta_title'] ?? '-' }}</p>
                        <p class="mt-1 text-xs text-gray-400">{{ mb_strlen($this->result['meta_title'] ?? '') }}/60 karakter</p>
                    </div>
                    <div class="rounded-lg border bg-white p-4 dark:bg-gray-800">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Slug</p>
                        <code class="text-sm text-blue-600 dark:text-blue-400">{{ $this->result['slug'] ?? '-' }}</code>
                    </div>
                </div>

                <div class="rounded-lg border bg-white p-4 dark:bg-gray-800">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Meta Description</p>
                    <p class="text-gray-900 dark:text-gray-100">{{ $this->result['meta_description'] ?? '-' }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ mb_strlen($this->result['meta_description'] ?? '') }}/160 karakter</p>
                </div>

                <div class="rounded-lg border bg-white p-4 dark:bg-gray-800">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Keywords</p>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach(explode(',', $this->result['keywords'] ?? '') as $kw)
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs text-blue-700 dark:bg-blue-900 dark:text-blue-300">{{ trim($kw) }}</span>
                        @endforeach
                    </div>
                </div>

                @if(!empty($this->result['faq']))
                    <div class="rounded-lg border bg-white p-4 dark:bg-gray-800">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">FAQ Suggestions</p>
                        <div class="space-y-2">
                            @foreach($this->result['faq'] as $item)
                                <div>
                                    <p class="font-medium text-gray-800 dark:text-gray-200">Q: {{ $item['q'] ?? '' }}</p>
                                    <p class="text-gray-600 dark:text-gray-400">A: {{ $item['a'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
