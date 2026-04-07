<x-filament-panels::page>
    <div class="space-y-4">

        {{-- Empty state --}}
        @if (empty($suggestions))
            <x-filament::section>
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <x-filament::icon
                        icon="heroicon-o-sparkles"
                        class="mb-4 h-12 w-12 text-gray-300 dark:text-gray-600"
                    />
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">Belum ada saran AI</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Buka halaman <strong>Digiflazz SKU</strong> dan klik tombol
                        <strong>"Analisis dengan AI"</strong> untuk memulai.
                    </p>
                </div>
            </x-filament::section>
        @else
            {{-- Summary bar --}}
            @php
                $total       = count($suggestions);
                $recommended = count(array_filter($suggestions, fn($s) => $s['recommended'] && $s['game_id']));
                $noGame      = count(array_filter($suggestions, fn($s) => !$s['game_id']));
            @endphp

            <x-filament::section>
                <div class="flex flex-wrap gap-6">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Saran</p>
                        <p class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white">{{ $total }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Direkomendasikan</p>
                        <p class="mt-0.5 text-2xl font-bold text-success-600 dark:text-success-400">{{ $recommended }}</p>
                    </div>
                    @if ($noGame > 0)
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Tanpa Game (perlu mapping manual)</p>
                            <p class="mt-0.5 text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $noGame }}</p>
                        </div>
                    @endif
                </div>
            </x-filament::section>

            {{-- Suggestions table --}}
            <x-filament::section heading="Daftar Saran Produk">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="pb-3 pr-4 font-semibold text-gray-700 dark:text-gray-300">SKU Code</th>
                                <th class="pb-3 pr-4 font-semibold text-gray-700 dark:text-gray-300">Nama Asli (Digiflazz)</th>
                                <th class="pb-3 pr-4 font-semibold text-gray-700 dark:text-gray-300">Saran Nama</th>
                                <th class="pb-3 pr-4 font-semibold text-gray-700 dark:text-gray-300">Game</th>
                                <th class="pb-3 pr-4 font-semibold text-gray-700 dark:text-gray-300">Harga Modal</th>
                                <th class="pb-3 pr-4 font-semibold text-gray-700 dark:text-gray-300">Margin AI (per Tier)</th>
                                <th class="pb-3 pr-4 font-semibold text-gray-700 dark:text-gray-300">Status AI</th>
                                <th class="pb-3 font-semibold text-gray-700 dark:text-gray-300">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($suggestions as $suggestion)
                                @php
                                    $base     = max(150, (int) ($suggestion['suggested_margin'] ?? 500));
                                    $roundTo50 = fn(float $v) => (int)(round($v / 50) * 50);
                                    $tGuest    = max(150, $roundTo50($base * 1.5));
                                    $tBronze   = max(150, $roundTo50($base * 1.2));
                                    $tSilver   = $base;
                                    $tGold     = max(150, $roundTo50($base * 0.8));
                                    $tPlatinum = max(150, $roundTo50($base * 0.6));
                                @endphp
                                <tr class="group {{ $suggestion['recommended'] ? '' : 'opacity-60' }}">
                                    <td class="py-3 pr-4">
                                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                            {{ $suggestion['sku_code'] }}
                                        </code>
                                    </td>
                                    <td class="py-3 pr-4 text-xs text-gray-500 dark:text-gray-400 max-w-45">
                                        {{ $suggestion['original_name'] }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ $suggestion['product_name'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4">
                                        @if ($suggestion['game_id'])
                                            <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                                                {{ $gameNames[$suggestion['game_id']] ?? 'Game #' . $suggestion['game_id'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-950 dark:text-warning-300">
                                                ⚠ Tidak cocok
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 text-sm font-medium text-gray-900 dark:text-white">
                                        Rp {{ number_format($suggestion['price'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <div class="text-xs space-y-0.5">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-14 text-gray-400">Guest</span>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">Rp {{ number_format($tGuest, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-14 text-amber-600">Bronze</span>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">Rp {{ number_format($tBronze, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-14 text-blue-500">Silver</span>
                                                <span class="font-semibold text-blue-600 dark:text-blue-400">Rp {{ number_format($tSilver, 0, ',', '.') }}</span>
                                                <span class="text-gray-400 text-[10px]">(base)</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-14 text-yellow-500">Gold</span>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">Rp {{ number_format($tGold, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-14 text-purple-500">Platinum</span>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">Rp {{ number_format($tPlatinum, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 pr-4">
                                        @if ($suggestion['recommended'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-950 dark:text-success-300">
                                                ✓ Rekomendasikan
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                — Tidak disarankan
                                            </span>
                                        @endif
                                        @if ($suggestion['reason'])
                                            <p class="mt-0.5 text-xs text-gray-400">{{ $suggestion['reason'] }}</p>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <div class="flex items-center gap-2">
                                            @if ($suggestion['game_id'])
                                                <x-filament::button
                                                    wire:click="approve('{{ $suggestion['sku_code'] }}')"
                                                    wire:loading.attr="disabled"
                                                    size="xs"
                                                    color="success"
                                                    icon="heroicon-m-check"
                                                >
                                                    Buat
                                                </x-filament::button>
                                            @endif
                                            <x-filament::button
                                                wire:click="skip('{{ $suggestion['sku_code'] }}')"
                                                wire:loading.attr="disabled"
                                                size="xs"
                                                color="gray"
                                                icon="heroicon-m-x-mark"
                                            >
                                                Lewati
                                            </x-filament::button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
