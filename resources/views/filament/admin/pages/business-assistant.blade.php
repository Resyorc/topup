<x-filament-panels::page>
<style>
    .chat-prose h1, .chat-prose h2, .chat-prose h3 { font-weight: 600; margin-top: .75rem; margin-bottom: .25rem; }
    .chat-prose h1 { font-size: 1.1rem; }
    .chat-prose h2 { font-size: 1rem; }
    .chat-prose h3 { font-size: .9rem; }
    .chat-prose p { margin-bottom: .5rem; line-height: 1.6; }
    .chat-prose p:last-child { margin-bottom: 0; }
    .chat-prose ul { list-style: disc; padding-left: 1.25rem; margin-bottom: .5rem; }
    .chat-prose ol { list-style: decimal; padding-left: 1.25rem; margin-bottom: .5rem; }
    .chat-prose li { margin-bottom: .2rem; line-height: 1.5; }
    .chat-prose strong { font-weight: 600; }
    .chat-prose em { font-style: italic; }
    .chat-prose code { font-family: ui-monospace, monospace; font-size: .8rem; background: rgba(0,0,0,.08); padding: .1rem .3rem; border-radius: .25rem; }
    .dark .chat-prose code { background: rgba(255,255,255,.1); }
    .chat-prose blockquote { border-left: 3px solid #6366f1; padding-left: .75rem; opacity: .8; margin: .5rem 0; }
    .chat-prose hr { border-color: rgba(0,0,0,.1); margin: .75rem 0; }
    .dark .chat-prose hr { border-color: rgba(255,255,255,.1); }
    .chat-prose table { width: 100%; font-size: .8rem; border-collapse: collapse; margin: .5rem 0; }
    .chat-prose th { background: rgba(0,0,0,.06); font-weight: 600; padding: .4rem .6rem; text-align: left; border: 1px solid rgba(0,0,0,.1); }
    .chat-prose td { padding: .35rem .6rem; border: 1px solid rgba(0,0,0,.08); }
    .dark .chat-prose th { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.1); }
    .dark .chat-prose td { border-color: rgba(255,255,255,.08); }
    .chat-bubble-user .chat-prose strong { color: #fff; }
</style>

<div
    x-data="{
        autoScroll: true,
        scrollToBottom(force = false) {
            this.$nextTick(() => {
                const el = this.$refs.chatBox;
                if (!el) return;
                if (force || this.autoScroll) {
                    el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
                }
            });
        },
        onScroll() {
            const el = this.$refs.chatBox;
            if (!el) return;
            this.autoScroll = (el.scrollHeight - el.scrollTop - el.clientHeight) < 80;
        },
        autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        }
    }"
    x-init="scrollToBottom(true)"
    x-on:livewire:commit.document="scrollToBottom()"
>
    {{-- ── Top Bar ─────────────────────────────────────────────────────── --}}
    <div class="mb-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-2 w-2 rounded-full bg-success-500 ring-2 ring-success-200 dark:ring-success-900"></span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ $this->getProviderInfo() }}
            </span>
        </div>
        @if (count($displayMessages) > 0)
            <button
                wire:click="clearConversation"
                wire:confirm="Hapus seluruh percakapan?"
                class="flex items-center gap-1 rounded-lg px-2 py-1 text-xs text-gray-400 transition hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-950 dark:hover:text-danger-400"
            >
                <x-filament::icon icon="heroicon-o-trash" class="h-3.5 w-3.5" />
                Bersihkan
            </button>
        @endif
    </div>

    {{-- ── Chat Card ───────────────────────────────────────────────────── --}}
    <div class="flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">

        {{-- Scrollable Messages --}}
        <div
            x-ref="chatBox"
            @scroll="onScroll()"
            class="flex flex-col gap-5 overflow-y-auto p-5"
            style="height: 560px;"
        >
            {{-- ── Empty State ─────────────────────────────────────────── --}}
            @if (count($displayMessages) === 0)
                <div class="flex flex-1 flex-col items-center justify-center gap-4 py-12">
                    <div class="relative">
                        <div class="absolute inset-0 rounded-full bg-primary-400 opacity-20 blur-xl"></div>
                        <div class="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-500 to-violet-600 shadow-lg">
                            <x-filament::icon icon="heroicon-o-cpu-chip" class="h-8 w-8 text-white" />
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100">Nuvelo Business Agent</p>
                        <p class="mt-1 text-sm text-gray-400">Asisten bisnis berbasis AI untuk analisis & rekomendasi toko Anda.</p>
                    </div>
                    <div class="flex flex-wrap justify-center gap-2">
                        @foreach ([
                            ['icon' => 'heroicon-o-chart-bar', 'text' => 'Laporan penjualan bulan ini'],
                            ['icon' => 'heroicon-o-arrow-trending-down', 'text' => 'Produk dengan margin terendah'],
                            ['icon' => 'heroicon-o-users', 'text' => 'Pelanggan tidak aktif ≥45 hari'],
                            ['icon' => 'heroicon-o-tag', 'text' => 'Promo yang sedang aktif'],
                        ] as $s)
                            <button
                                wire:click="$set('userInput', '{{ $s['text'] }}')"
                                class="flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-600 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-primary-700 dark:hover:bg-primary-950 dark:hover:text-primary-400"
                            >
                                <x-filament::icon :icon="$s['icon']" class="h-3.5 w-3.5 opacity-70" />
                                {{ $s['text'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── Messages ─────────────────────────────────────────────── --}}
            @foreach ($displayMessages as $index => $msg)

                @if ($msg['role'] === 'user')
                    {{-- User --}}
                    <div class="flex justify-end">
                        <div class="chat-bubble-user max-w-[78%] rounded-2xl rounded-tr-none bg-gradient-to-br from-primary-500 to-violet-600 px-4 py-3 text-sm text-white shadow-md">
                            <div class="chat-prose">
                                {!! \Illuminate\Support\Str::markdown(e($msg['content']), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                            </div>
                        </div>
                    </div>

                @else
                    {{-- Assistant --}}
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex-shrink-0">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-violet-600 shadow-sm">
                                <x-filament::icon icon="heroicon-o-cpu-chip" class="h-4 w-4 text-white" />
                            </div>
                        </div>
                        <div
                            @class([
                                'max-w-[82%] rounded-2xl rounded-tl-none px-4 py-3 text-sm shadow-sm',
                                'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' => empty($msg['error']) && empty($msg['is_action_preview']),
                                'border border-warning-200 bg-warning-50 text-warning-800 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-200' => ! empty($msg['is_action_preview']),
                                'border border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-800 dark:bg-danger-950 dark:text-danger-300' => ! empty($msg['error']),
                            ])
                        >
                            <div class="chat-prose">
                                {!! \Illuminate\Support\Str::markdown(e($msg['content']), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                            </div>
                        </div>
                    </div>
                @endif

            @endforeach

            {{-- Processing / Typing Indicator --}}
            @if ($isProcessing)
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex-shrink-0">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-violet-600 shadow-sm">
                            <x-filament::icon icon="heroicon-o-cpu-chip" class="h-4 w-4 text-white" />
                        </div>
                    </div>
                    <div class="rounded-2xl rounded-tl-none bg-gray-100 px-4 py-3.5 shadow-sm dark:bg-gray-800" x-init="$dispatch('livewire-message-received')">
                        <div class="flex items-center gap-1">
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay:0ms"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay:160ms"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay:320ms"></span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Pending Action Bar ──────────────────────────────────────────── --}}
        @if ($pendingAction && ! $isProcessing)
            <div class="border-t border-warning-200 bg-warning-50 px-5 py-4 dark:border-warning-800/60 dark:bg-warning-950/60">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 rounded-full bg-warning-200 p-1.5 dark:bg-warning-800">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4 text-warning-700 dark:text-warning-300" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-warning-700 dark:text-warning-400">Menunggu Konfirmasi</p>
                            <p class="mt-0.5 text-sm text-warning-800 dark:text-warning-200">{{ $pendingAction['description'] }}</p>
                        </div>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-2">
                        <x-filament::button wire:click="cancelAction" size="sm" color="gray" icon="heroicon-o-x-mark">
                            Batal
                        </x-filament::button>
                        <x-filament::button wire:click="confirmAction" size="sm" color="success" icon="heroicon-o-check">
                            Ya, Terapkan
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Input Area ──────────────────────────────────────────────────── --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50">
            <div class="flex items-end gap-2">
                <textarea
                    wire:model="userInput"
                    x-ref="textarea"
                    x-on:input="autoResize($el)"
                    x-on:keydown.enter.prevent="
                        if (!$event.shiftKey && !{{ $isProcessing ? 'true' : 'false' }}) {
                            $wire.sendMessage();
                            $nextTick(() => { $el.style.height = 'auto'; });
                        } else if ($event.shiftKey) {
                            const pos = $el.selectionStart;
                            $el.value = $el.value.slice(0, pos) + '\n' + $el.value.slice(pos);
                            $el.selectionStart = $el.selectionEnd = pos + 1;
                            autoResize($el);
                        }
                    "
                    placeholder="{{ $isProcessing ? 'Agent sedang berpikir...' : 'Tanya sesuatu... (Enter kirim, Shift+Enter baris baru)' }}"
                    rows="1"
                    @disabled($isProcessing)
                    class="w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder-gray-400 shadow-sm transition-all focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:placeholder-gray-500 dark:focus:border-primary-500 dark:focus:ring-primary-900"
                ></textarea>
                <button
                    wire:click="sendMessage"
                    wire:loading.attr="disabled"
                    @disabled($isProcessing)
                    class="mb-0.5 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-violet-600 text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <x-filament::icon icon="heroicon-o-paper-airplane" class="h-5 w-5" />
                </button>
            </div>
            <p class="mt-1.5 text-center text-[10px] text-gray-400">
                Enter untuk kirim &middot; Shift+Enter untuk baris baru &middot; Tindakan data memerlukan konfirmasi Anda
            </p>
        </div>
    </div>
</div>
</x-filament-panels::page>
