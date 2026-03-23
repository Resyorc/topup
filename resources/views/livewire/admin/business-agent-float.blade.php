<div x-data="{ scrollToBottom() { this.$nextTick(() => { const el = this.$refs.chatBox; if (el) el.scrollTop = el.scrollHeight; }); } }">

    {{-- ── Floating Trigger Button ─────────────────────────────────── --}}
    <button
        wire:click="toggle"
        class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full shadow-2xl transition-all duration-300 hover:scale-110 focus:outline-none"
        style="background: linear-gradient(135deg, #7c3aed, #4f46e5);"
        title="Business Agent"
    >
        @if ($isOpen)
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        @else
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
            </svg>
        @endif

        {{-- Processing pulse --}}
        @if ($isProcessing)
            <span class="absolute -right-0.5 -top-0.5 flex h-3.5 w-3.5">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-violet-400 opacity-75"></span>
                <span class="relative inline-flex h-3.5 w-3.5 rounded-full bg-violet-500"></span>
            </span>
        @endif
    </button>

    {{-- ── Full-Screen Overlay ──────────────────────────────────────── --}}
    @if ($isOpen)
        <div
            class="fixed inset-0 z-40 flex flex-col"
            style="background: radial-gradient(ellipse at 20% 50%, rgba(76, 29, 149, 0.15) 0%, transparent 60%), radial-gradient(ellipse at 80% 20%, rgba(49, 46, 129, 0.12) 0%, transparent 60%), #080d1a;"
            x-init="scrollToBottom()"
            x-on:livewire:commit.document="scrollToBottom()"
        >

            {{-- Top bar --}}
            <div class="flex items-center justify-between border-b border-white/5 px-6 py-3.5">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg" style="background: linear-gradient(135deg, #7c3aed, #4f46e5);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Business Agent</p>
                        <div class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            <p class="text-[11px] text-white/40">{{ $this->getProviderInfo() }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if (count($displayMessages) > 0)
                        <button
                            wire:click="clearConversation"
                            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs text-white/40 transition hover:bg-white/5 hover:text-white/70"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            Bersihkan
                        </button>
                    @endif
                    <button
                        wire:click="toggle"
                        class="rounded-lg p-2 text-white/40 transition hover:bg-white/5 hover:text-white/70"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Chat / Empty State --}}
            <div
                x-ref="chatBox"
                class="flex flex-1 flex-col overflow-y-auto px-4 py-6 sm:px-8 md:px-16 lg:px-32 xl:px-48 2xl:px-72"
            >

                {{-- Empty State: Greeting --}}
                @if (count($displayMessages) === 0)
                    <div class="flex flex-1 flex-col items-center justify-center gap-8 py-8 text-center">
                        <div>
                            <h1 class="text-3xl font-bold text-white sm:text-4xl">{{ $this->getGreeting() }}</h1>
                            <p class="mt-2 text-lg text-white/40">Ada yang bisa saya bantu hari ini?</p>
                        </div>
                        <div class="flex flex-wrap justify-center gap-2">
                            @foreach ([
                                'Laporan penjualan bulan ini',
                                'Produk dengan margin terendah',
                                'Pelanggan tidak aktif 45 hari',
                                'Analisis promo aktif',
                                'Revenue hari ini vs kemarin',
                            ] as $s)
                                <button
                                    wire:click="$set('userInput', '{{ $s }}')"
                                    class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm text-white/60 backdrop-blur-sm transition hover:border-violet-500/50 hover:bg-violet-500/10 hover:text-white/90"
                                >
                                    {{ $s }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Messages --}}
                @foreach ($displayMessages as $msg)
                    @if ($msg['role'] === 'user')
                        <div class="mb-6 flex justify-end">
                            <div class="max-w-[70%] rounded-2xl rounded-tr-sm px-4 py-3 text-sm leading-relaxed text-white" style="background: linear-gradient(135deg, #7c3aed, #4f46e5);">
                                {{ $msg['content'] }}
                            </div>
                        </div>
                    @else
                        <div class="mb-6 flex items-start gap-3">
                            <div class="mt-0.5 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg" style="background: linear-gradient(135deg, #7c3aed44, #4f46e544); border: 1px solid rgba(124,58,237,0.3);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                                </svg>
                            </div>
                            <div
                                @class([
                                    'max-w-[80%] rounded-2xl rounded-tl-sm px-4 py-3 text-sm leading-relaxed',
                                    'bg-white/5 text-white/85 border border-white/5' => empty($msg['error']) && empty($msg['is_action_preview']),
                                    'border border-amber-500/30 bg-amber-500/10 text-amber-200' => !empty($msg['is_action_preview']),
                                    'border border-red-500/30 bg-red-500/10 text-red-300' => !empty($msg['error']),
                                ])
                            >
                                @php
                                    $html = \Illuminate\Support\Str::markdown(
                                        e($msg['content']),
                                        ['html_input' => 'strip', 'allow_unsafe_links' => false]
                                    );
                                @endphp
                                <div class="agent-prose">{!! $html !!}</div>
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Typing Indicator --}}
                @if ($isProcessing)
                    <div class="mb-6 flex items-start gap-3">
                        <div class="mt-0.5 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg" style="background: linear-gradient(135deg, #7c3aed44, #4f46e544); border: 1px solid rgba(124,58,237,0.3);">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                            </svg>
                        </div>
                        <div class="rounded-2xl rounded-tl-sm border border-white/5 bg-white/5 px-4 py-3.5">
                            <div class="flex items-center gap-1">
                                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-violet-400" style="animation-delay:0ms"></span>
                                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-violet-400" style="animation-delay:150ms"></span>
                                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-violet-400" style="animation-delay:300ms"></span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pending Action Bar --}}
            @if ($pendingAction && !$isProcessing)
                <div class="mx-4 mb-3 rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 sm:mx-8 md:mx-16 lg:mx-32 xl:mx-48 2xl:mx-72">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            <p class="text-sm text-amber-200">{{ $pendingAction['description'] }}</p>
                        </div>
                        <div class="flex flex-shrink-0 gap-2">
                            <button wire:click="cancelAction" class="rounded-lg px-3 py-1.5 text-xs text-white/50 transition hover:bg-white/5 hover:text-white/80">
                                Batal
                            </button>
                            <button wire:click="confirmAction" class="rounded-lg px-3 py-1.5 text-xs font-medium text-white transition" style="background: linear-gradient(135deg, #7c3aed, #4f46e5);">
                                Ya, Terapkan
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Input Area --}}
            <div class="border-t border-white/5 bg-white/2 px-4 py-4 sm:px-8 md:px-16 lg:px-32 xl:px-48 2xl:px-72">
                <div class="flex items-end gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur-sm focus-within:border-violet-500/50 transition-colors">
                    <textarea
                        wire:model="userInput"
                        x-data="{}"
                        x-on:input="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight,120)+'px';"
                        x-on:keydown.enter.prevent="if(!$event.shiftKey && !{{ $isProcessing ? 'true' : 'false' }}) { $wire.sendMessage(); $nextTick(()=>{ $el.style.height='auto'; }); }"
                        placeholder="{{ $isProcessing ? 'Sedang memproses...' : 'Tanyakan sesuatu tentang bisnis Anda...' }}"
                        rows="1"
                        @disabled($isProcessing)
                        class="flex-1 resize-none bg-transparent text-sm text-white placeholder-white/30 focus:outline-none disabled:opacity-40"
                        style="max-height:120px;"
                    ></textarea>
                    <button
                        wire:click="sendMessage"
                        @disabled($isProcessing)
                        class="mb-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl transition hover:opacity-90 disabled:opacity-30"
                        style="background: linear-gradient(135deg, #7c3aed, #4f46e5);"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                    </button>
                </div>
                <p class="mt-2 text-center text-[11px] text-white/20">Enter kirim &middot; Shift+Enter baris baru &middot; Write action memerlukan konfirmasi</p>
            </div>
        </div>

        <style>
            .agent-prose p { margin-bottom: .45rem; line-height: 1.65; }
            .agent-prose p:last-child { margin-bottom: 0; }
            .agent-prose ul { list-style: disc; padding-left: 1.2rem; margin-bottom: .45rem; }
            .agent-prose ol { list-style: decimal; padding-left: 1.2rem; margin-bottom: .45rem; }
            .agent-prose li { margin-bottom: .2rem; }
            .agent-prose strong { font-weight: 600; color: #c4b5fd; }
            .agent-prose h1,.agent-prose h2,.agent-prose h3 { font-weight: 600; margin: .6rem 0 .3rem; color: #e9d5ff; }
            .agent-prose h1 { font-size: 1.05rem; }
            .agent-prose h2 { font-size: .95rem; }
            .agent-prose h3 { font-size: .875rem; }
            .agent-prose code { font-family: ui-monospace,monospace; font-size: .8rem; background: rgba(124,58,237,.2); padding: .1rem .35rem; border-radius: .3rem; color: #c4b5fd; }
            .agent-prose blockquote { border-left: 2px solid rgba(124,58,237,.5); padding-left: .75rem; color: rgba(255,255,255,.5); margin: .4rem 0; }
            .agent-prose table { width: 100%; font-size: .8rem; border-collapse: collapse; margin: .5rem 0; }
            .agent-prose th { background: rgba(124,58,237,.15); font-weight: 600; padding: .4rem .6rem; text-align: left; border: 1px solid rgba(255,255,255,.08); color: #ddd6fe; }
            .agent-prose td { padding: .35rem .6rem; border: 1px solid rgba(255,255,255,.06); }
            .agent-prose hr { border-color: rgba(255,255,255,.08); margin: .6rem 0; }
        </style>
    @endif
</div>
