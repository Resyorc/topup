import axios from 'axios';
import React, { useState, useRef, useEffect } from 'react';

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

interface ChatContext {
    page?: string;
    invoice_id?: string;
    game_slug?: string;
}

interface LiveChatProps {
    context?: ChatContext;
}

/** Render teks dengan **bold** dan baris baru */
function ChatBubble({
    content,
    role,
}: {
    content: string;
    role: 'user' | 'assistant';
}) {
    const parts = content.split(/(\*\*[^*]+\*\*)/g);
    const rendered = parts.map((part, i) => {
        if (part.startsWith('**') && part.endsWith('**')) {
            return <strong key={i}>{part.slice(2, -2)}</strong>;
        }
        return part.split('\n').map((line, j, arr) => (
            <React.Fragment key={`${i}-${j}`}>
                {line}
                {j < arr.length - 1 && <br />}
            </React.Fragment>
        ));
    });

    return (
        <div
            className={`max-w-[85%] rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed ${
                role === 'user'
                    ? 'ml-auto rounded-br-sm bg-primary text-white'
                    : 'mr-auto rounded-bl-sm bg-[var(--color-bg-secondary)] text-gray-200'
            }`}
        >
            {rendered}
        </div>
    );
}

export default function LiveChat({ context = {} }: LiveChatProps) {
    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState<Message[]>([
        {
            role: 'assistant',
            content:
                'Halo! 👋 Aku **Nova**, asisten virtual Nuvelo. Ada yang bisa aku bantu?',
        },
    ]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const bottomRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (open) {
            setTimeout(
                () => bottomRef.current?.scrollIntoView({ behavior: 'smooth' }),
                50,
            );
            setTimeout(() => inputRef.current?.focus(), 100);
        }
    }, [open, messages]);

    const sendMessage = async () => {
        const text = input.trim();
        if (!text || loading) return;

        const userMsg: Message = { role: 'user', content: text };
        const nextMessages = [...messages, userMsg];
        setMessages(nextMessages);
        setInput('');
        setLoading(true);

        // Kirim 10 pesan terakhir saja (exclude greeting), hindari token membengkak
        const HISTORY_LIMIT = 10;
        const history = nextMessages.slice(1, -1).slice(-HISTORY_LIMIT);

        try {
            const response = await axios.post('/api/chat', {
                message: text,
                history,
                context,
            });

            if (response.data.success) {
                setMessages([
                    ...nextMessages,
                    { role: 'assistant', content: response.data.reply },
                ]);
            } else {
                setMessages([
                    ...nextMessages,
                    {
                        role: 'assistant',
                        content: '😔 Maaf, terjadi kesalahan. Coba lagi ya.',
                    },
                ]);
            }
        } catch {
            setMessages([
                ...nextMessages,
                {
                    role: 'assistant',
                    content: '😔 Koneksi bermasalah. Coba lagi sebentar.',
                },
            ]);
        } finally {
            setLoading(false);
        }
    };

    const handleKey = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    const isOrderPage = context.page?.startsWith('/order/') ?? false;
    const chatWindowPosition = isOrderPage
        ? 'bottom-[calc(7.5rem+env(safe-area-inset-bottom))] sm:bottom-[calc(6rem+env(safe-area-inset-bottom))] md:bottom-32'
        : 'bottom-0 md:bottom-24';
    const chatFabPosition = isOrderPage
        ? 'bottom-[calc(7.75rem+env(safe-area-inset-bottom))] sm:bottom-[calc(6.25rem+env(safe-area-inset-bottom))] md:bottom-28'
        : 'bottom-[calc(1rem+env(safe-area-inset-bottom))] md:bottom-6';

    return (
        <>
            {/* Chat Window */}
            {open && (
                <div
                    className={`fixed inset-x-0 z-[300] flex flex-col overflow-hidden border-t border-[var(--color-border-light)] bg-[var(--color-bg-card)] shadow-2xl md:inset-x-auto md:right-6 md:w-[340px] md:rounded-2xl md:border ${chatWindowPosition}`}
                >
                    {/* Header */}
                    <div className="flex items-center justify-between bg-[var(--color-bg-secondary)] px-4 py-3">
                        <div className="flex items-center gap-2.5">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--color-accent-soft)]">
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="text-[var(--color-accent)]"
                                >
                                    <path d="M12 8V4H8" />
                                    <rect
                                        width="16"
                                        height="12"
                                        x="4"
                                        y="8"
                                        rx="2"
                                    />
                                    <path d="M2 14h2" />
                                    <path d="M20 14h2" />
                                    <path d="M15 13v2" />
                                    <path d="M9 13v2" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-white">
                                    Nova
                                </p>
                                <p className="text-[10px] text-green-400">
                                    ● Online
                                </p>
                            </div>
                        </div>
                        <button
                            onClick={() => setOpen(false)}
                            className="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-white/10 hover:text-white"
                        >
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                            >
                                <path d="M18 6L6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {/* Messages */}
                    <div
                        className="flex flex-1 flex-col gap-2.5 overflow-y-auto p-4"
                        style={{
                            height: 'min(50vh, 340px)',
                            minHeight: '180px',
                        }}
                    >
                        {messages.map((msg, i) => (
                            <ChatBubble
                                key={i}
                                role={msg.role}
                                content={msg.content}
                            />
                        ))}

                        {loading && (
                            <div className="mr-auto flex items-center gap-1 rounded-2xl rounded-bl-sm bg-[var(--color-bg-secondary)] px-4 py-3">
                                <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:0ms]" />
                                <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:150ms]" />
                                <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:300ms]" />
                            </div>
                        )}
                        <div ref={bottomRef} />
                    </div>

                    {/* Input */}
                    <div className="flex items-center gap-2 border-t border-[var(--color-border-light)] px-3 pt-2.5 pb-[calc(0.625rem+env(safe-area-inset-bottom))] md:py-2.5">
                        <input
                            ref={inputRef}
                            type="text"
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            onKeyDown={handleKey}
                            placeholder="Ketik pesan..."
                            maxLength={1000}
                            disabled={loading}
                            className="flex-1 rounded-lg bg-[var(--color-bg-secondary)] px-3 py-2 text-sm text-white placeholder-gray-500 outline-none focus:ring-1 focus:ring-[var(--color-accent)] disabled:opacity-50"
                        />
                        <button
                            onClick={sendMessage}
                            disabled={loading || !input.trim()}
                            className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[var(--color-accent)] text-[var(--color-accent-foreground)] transition hover:bg-[var(--color-accent-hover)] disabled:opacity-40"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="m22 2-7 20-4-9-9-4z" />
                                <path d="M22 2 11 13" />
                            </svg>
                        </button>
                    </div>
                </div>
            )}

            {/* FAB Toggle Button — hidden on mobile when chat is open (close via header) */}
            <button
                onClick={() => setOpen((o) => !o)}
                className={`fixed right-4 z-[300] h-[52px] w-[52px] items-center justify-center rounded-full bg-[var(--color-accent)] shadow-[var(--shadow-accent-glow)] transition hover:scale-105 hover:bg-[var(--color-accent-hover)] md:right-6 md:flex ${chatFabPosition} ${open ? 'hidden' : 'flex'}`}
                aria-label="Buka live chat"
            >
                <svg
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="white"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                >
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
            </button>
        </>
    );
}
