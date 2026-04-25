<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Form Analisis Harian --}}
        <form wire:submit="analyze">
            {{ $this->form }}
            <div class="mt-4 flex gap-3">
                <x-filament::button type="submit" icon="heroicon-o-sparkles" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="analyze">Analisis Transaksi</span>
                    <span wire:loading wire:target="analyze">Menganalisis...</span>
                </x-filament::button>

                <x-filament::button
                    type="button"
                    wire:click="detectPending"
                    color="warning"
                    icon="heroicon-o-clock"
                    wire:loading.attr="disabled"
                    wire:target="detectPending">
                    <span wire:loading.remove wire:target="detectPending">Deteksi Pending Bermasalah</span>
                    <span wire:loading wire:target="detectPending">Mendeteksi...</span>
                </x-filament::button>
            </div>
        </form>

        {{-- Hasil Analisis --}}
        @if($this->result)
            @php $stats = $this->result['stats'] ?? []; $insight = $this->result['insight'] ?? []; @endphp
            <div class="rounded-xl border bg-white p-5 dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-bold text-gray-800 dark:text-gray-200">
                    📊 Analisis Transaksi — {{ $this->result['date'] ?? '' }}
                </h2>

                {{-- Stats Grid --}}
                <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-700">
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $stats['total'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Total</p>
                    </div>
                    <div class="rounded-lg bg-green-50 p-3 text-center dark:bg-green-900">
                        <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $stats['success'] ?? 0 }}</p>
                        <p class="text-xs text-green-500">Sukses</p>
                    </div>
                    <div class="rounded-lg bg-red-50 p-3 text-center dark:bg-red-900">
                        <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $stats['failed'] ?? 0 }}</p>
                        <p class="text-xs text-red-500">Gagal</p>
                    </div>
                    <div class="rounded-lg bg-yellow-50 p-3 text-center dark:bg-yellow-900">
                        <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $stats['pending'] ?? 0 }}</p>
                        <p class="text-xs text-yellow-500">Pending</p>
                    </div>
                </div>

                <div class="mb-4 grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">Omzet</p>
                        <p class="text-lg font-bold text-gray-800 dark:text-gray-200">Rp {{ number_format($stats['revenue'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">Profit</p>
                        <p class="text-lg font-bold text-green-600 dark:text-green-400">Rp {{ number_format($stats['profit'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>

                @if(!empty($insight['summary']))
                    <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-950">
                        <p class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-1">🤖 Ringkasan AI</p>
                        <p class="text-sm text-blue-800 dark:text-blue-200">{{ $insight['summary'] }}</p>
                        @if(!empty($insight['health_score']))
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs text-blue-500">Health Score:</span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold
                                    {{ $insight['health_score'] >= 80 ? 'bg-green-100 text-green-700' : ($insight['health_score'] >= 60 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                    {{ $insight['health_score'] }}/100
                                </span>
                            </div>
                        @endif
                    </div>
                @endif

                @if(!empty($insight['recommendations']))
                    <div class="mt-4">
                        <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">💡 Rekomendasi</p>
                        <ul class="space-y-1 text-sm">
                            @foreach($insight['recommendations'] as $rec)
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 text-green-500">→</span>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $rec }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($insight['concerns']))
                    <div class="mt-4">
                        <p class="mb-2 text-sm font-semibold text-orange-600 dark:text-orange-400">⚠️ Perhatian</p>
                        <ul class="space-y-1 text-sm">
                            @foreach($insight['concerns'] as $concern)
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 text-orange-400">•</span>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $concern }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        {{-- Pending Bermasalah --}}
        @if($this->stuckPending !== null)
            <div class="rounded-xl border {{ count($this->stuckPending) > 0 ? 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-950' : 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950' }} p-5">
                <h3 class="mb-3 font-bold {{ count($this->stuckPending) > 0 ? 'text-yellow-800 dark:text-yellow-200' : 'text-green-700 dark:text-green-300' }}">
                    {{ count($this->stuckPending) > 0 ? '⚠️ Transaksi Pending Bermasalah ('.count($this->stuckPending).')' : '✅ Tidak Ada Transaksi Pending Bermasalah' }}
                </h3>

                @if(count($this->stuckPending) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-yellow-100 dark:bg-yellow-900">
                                <tr>
                                    <th class="px-3 py-2 text-left">Invoice</th>
                                    <th class="px-3 py-2 text-left">Game</th>
                                    <th class="px-3 py-2 text-left">Produk</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-right">Pending (menit)</th>
                                    <th class="px-3 py-2 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->stuckPending as $t)
                                    <tr class="border-t border-yellow-200 dark:border-yellow-800">
                                        <td class="px-3 py-2 font-mono">{{ $t['invoice_id'] }}</td>
                                        <td class="px-3 py-2">{{ $t['game'] }}</td>
                                        <td class="px-3 py-2">{{ $t['product'] }}</td>
                                        <td class="px-3 py-2">
                                            <span class="rounded px-1.5 py-0.5 text-xs
                                                {{ $t['status'] === 'processing' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700' }}">
                                                {{ $t['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right font-semibold {{ $t['minutes_pending'] > 30 ? 'text-red-600' : 'text-yellow-600' }}">
                                            {{ $t['minutes_pending'] }} mnt
                                        </td>
                                        <td class="px-3 py-2 text-right">Rp {{ number_format($t['amount'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
