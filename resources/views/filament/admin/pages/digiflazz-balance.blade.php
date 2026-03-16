<x-filament-panels::page>
    <div class="space-y-6">

        @if ($errorMessage)
            <x-filament::section>
                <div class="flex items-center gap-3 text-danger-600 dark:text-danger-400">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6 shrink-0" />
                    <div>
                        <p class="font-semibold">Gagal mengambil saldo</p>
                        <p class="text-sm opacity-75">{{ $errorMessage }}</p>
                    </div>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo Deposit Digiflazz</p>
                        <p class="mt-1 text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
                            @if ($balance !== null)
                                Rp {{ number_format($balance, 0, ',', '.') }}
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </p>
                        <p class="mt-2 text-xs text-gray-400">Terakhir diperbarui: {{ now()->format('d M Y, H:i:s') }} WIB</p>
                    </div>
                    <div class="rounded-full bg-primary-50 p-3 dark:bg-primary-950">
                        <x-heroicon-o-banknotes class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section heading="Informasi Akun">
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Username</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ config('services.digiflazz.username') ?: '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Base URL</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ config('services.digiflazz.base_url') ?: 'https://api.digiflazz.com/v1' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-950 dark:text-success-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>
                                Terhubung
                            </span>
                        </dd>
                    </div>
                </dl>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
