<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center justify-between gap-3">
            {{-- Tombol Apply Global Pricing (kiri) --}}
            <x-filament::button
                wire:click.prevent="applyGlobalPricing"
                wire:loading.attr="disabled"
                color="warning"
                icon="heroicon-o-currency-dollar"
            >
                <span wire:loading.remove wire:target="applyGlobalPricing">Terapkan Global Pricing ke Semua Produk</span>
                <span wire:loading wire:target="applyGlobalPricing">Sedang memproses...</span>
            </x-filament::button>

            {{-- Tombol Simpan Konfigurasi (kanan) --}}
            <x-filament::button type="submit" icon="heroicon-o-check">
                Simpan Konfigurasi
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
