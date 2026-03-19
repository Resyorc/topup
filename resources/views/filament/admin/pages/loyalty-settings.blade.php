<x-filament-panels::page>
    <div class="space-y-6">

        <x-filament::section
            icon="heroicon-o-gift"
            icon-color="warning"
            heading="Program Loyalitas Krysta Coin"
        >
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Setiap transaksi berhasil oleh user yang login (pembayaran bukan Krysta Coin) akan
                mendapat reward berupa saldo Krysta Coin secara otomatis.
            </p>
        </x-filament::section>

        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Simpan Perubahan
                </x-filament::button>
            </div>
        </form>

        <x-filament::section heading="Simulasi Reward">
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Contoh reward berdasarkan konfigurasi saat ini:
            </p>
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @php
                    $rate = (float) \App\Models\Setting::get('loyalty_rate_percent', config('services.loyalty.rate_percent', 1));
                    $min  = (int)   \App\Models\Setting::get('loyalty_min_amount',   config('services.loyalty.min_amount', 5000));
                    $examples = [5000, 10000, 50000, 100000];
                @endphp
                @foreach ($examples as $amount)
                    @php $coins = $amount >= $min ? (int) floor($amount * $rate / 100) : 0; @endphp
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Rp {{ number_format($amount, 0, ',', '.') }}</dt>
                        <dd class="mt-1 text-sm font-bold {{ $coins > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-400' }}">
                            {{ $coins > 0 ? '+' . number_format($coins, 0, ',', '.') . ' Coin' : '— (di bawah minimum)' }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::section>

    </div>
</x-filament-panels::page>
