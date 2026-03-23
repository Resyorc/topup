<x-filament-panels::page>
    <div
        x-data="{
            scrollToBottom() {
                this.$nextTick(() => {
                    const el = document.getElementById('chat-messages');
                    if (el) el.scrollTop = el.scrollHeight;
                });
            }
        }"
        x-init="scrollToBottom()"
        @message-added.window="scrollToBottom()"
    >
        {{-- Header Info --}}
        <div class="mb-4 flex items-center justify-between">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                AI Provider: <span class="font-medium text-gray-600 dark:text-gray-400">{{ $this->getProviderInfo() }}</span>
            </p>
            @if (count($displayMessages) > 0)
                <button
                    wire:click="clearConversation"
                    class="text-xs text-gray-400 hover:text-danger-500 dark:hover:text-danger-400 transition-colors"
                >
                    Bersihkan percakapan
                </button>
            @endif
        </div>

        {{-- Chat Container --}}
        <x-filament::section>
            {{-- Message List --}}
            <div
                id="chat-messages"
                class="flex flex-col gap-4 overflow-y-auto"
                style="min-height: 400px; max-height: 560px;"
                wire:poll.5000ms="$refresh"
            >
                {{-- Empty State --}}
                @if (count($displayMessages) === 0)
                    <div class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <div class="rounded-full bg-primary-50 p-4 dark:bg-primary-950">
                            <x-filament::icon
                                icon="heroicon-o-cpu-chip"
                                class="h-8 w-8 text-primary-500"
                            />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700 dark:text-gray-300">Nuvelo Business Agent</p>
                            <p class="mt-1 text-sm text-gray-400">Tanyakan apa saja tentang data bisnis Anda.</p>
                        </div>
                        <div class="mt-2 flex flex-wrap justify-center gap-2">
                            @foreach ([
                                'Buatkan laporan penjualan bulan ini',
                                'Produk mana yang marginnya turun?',
                                'Siapa pelanggan tidak aktif ≥45 hari?',
                                'Cek promo yang aktif sekarang',
                            ] as $suggestion)
                                <button
                                    wire:click="$set('userInput', '{{ $suggestion }}')"
                                    class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs text-gray-500 hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-primary-700 dark:hover:text-primary-400 transition-colors"
                                >
                                    {{ $suggestion }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Messages --}}
                @foreach ($displayMessages as $msg)
                    @if ($msg['role'] === 'user')
                        {{-- User bubble --}}
                        <div class="flex justify-end" x-init="$dispatch('message-added')">
                            <div class="max-w-[80%] rounded-2xl rounded-tr-sm bg-primary-600 px-4 py-3 text-sm text-white shadow-sm">
                                {{ $msg['content'] }}
                            </div>
                        </div>
                    @else
                        {{-- Assistant bubble --}}
                        <div class="flex items-start gap-3" x-init="$dispatch('message-added')">
                            <div class="mt-1 flex-shrink-0 rounded-full bg-gray-100 p-1.5 dark:bg-gray-800">
                                <x-filament::icon
                                    icon="heroicon-o-cpu-chip"
                                    class="h-4 w-4 text-primary-500"
                                />
                            </div>
                            <div
                                @class([
                                    'max-w-[85%] rounded-2xl rounded-tl-sm px-4 py-3 text-sm shadow-sm',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' => empty($msg['error']),
                                    'bg-danger-50 text-danger-700 dark:bg-danger-950 dark:text-danger-300' => ! empty($msg['error']),
                                ])
                            >
                                {!! nl2br(e($msg['content'])) !!}
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Processing Indicator --}}
                @if ($isProcessing)
                    <div class="flex items-start gap-3" x-init="$dispatch('message-added')">
                        <div class="mt-1 flex-shrink-0 rounded-full bg-gray-100 p-1.5 dark:bg-gray-800">
                            <x-filament::icon
                                icon="heroicon-o-cpu-chip"
                                class="h-4 w-4 text-primary-500"
                            />
                        </div>
                        <div class="rounded-2xl rounded-tl-sm bg-gray-100 px-4 py-3 text-sm dark:bg-gray-800">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:0ms]"></span>
                                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:150ms]"></span>
                                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:300ms]"></span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pending Action Confirmation --}}
            @if ($pendingAction && ! $isProcessing)
                <div class="mt-4 rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-800 dark:bg-warning-950">
                    <div class="flex items-start gap-3">
                        <x-filament::icon
                            icon="heroicon-o-exclamation-triangle"
                            class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning-600 dark:text-warning-400"
                        />
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-warning-800 dark:text-warning-200">Konfirmasi Tindakan</p>
                            <p class="mt-1 text-sm text-warning-700 dark:text-warning-300">
                                {{ $pendingAction['description'] }}
                            </p>
                            <div class="mt-3 flex gap-2">
                                <x-filament::button
                                    wire:click="confirmAction"
                                    size="sm"
                                    color="success"
                                    icon="heroicon-o-check"
                                >
                                    Ya, Terapkan
                                </x-filament::button>
                                <x-filament::button
                                    wire:click="cancelAction"
                                    size="sm"
                                    color="gray"
                                    icon="heroicon-o-x-mark"
                                >
                                    Batalkan
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Input Area --}}
            <div class="mt-4 flex items-end gap-2">
                <div class="flex-1">
                    <textarea
                        wire:model="userInput"
                        wire:keydown.enter.prevent="sendMessage"
                        placeholder="Tanya sesuatu tentang bisnis Anda... (Enter untuk kirim)"
                        rows="2"
                        @disabled($isProcessing)
                        class="w-full resize-none rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 placeholder-gray-400 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:placeholder-gray-500 dark:focus:border-primary-500"
                    ></textarea>
                </div>
                <x-filament::button
                    wire:click="sendMessage"
                    :disabled="$isProcessing || empty(trim($userInput))"
                    icon="heroicon-o-paper-airplane"
                    class="mb-0.5"
                >
                    Kirim
                </x-filament::button>
            </div>
            <p class="mt-2 text-xs text-gray-400">
                Enter untuk kirim · Tindakan write memerlukan konfirmasi Anda
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
