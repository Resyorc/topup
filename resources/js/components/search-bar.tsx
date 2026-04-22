import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface GameResult {
    name: string;
    slug: string;
    thumbnail: string | null;
}

interface SearchBarProps {
    inputClassName?: string;
    buttonClassName?: string;
    autoFocus?: boolean;
}

export default function SearchBar({
    inputClassName = '',
    buttonClassName = '',
    autoFocus = false,
}: SearchBarProps) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<GameResult[]>([]);
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);

    const containerRef = useRef<HTMLDivElement>(null);
    const abortRef = useRef<AbortController | null>(null);

    // Debounced fetch
    useEffect(() => {
        if (query.trim().length < 2) {
            setResults([]);
            setOpen(false);
            return;
        }

        const timer = setTimeout(async () => {
            abortRef.current?.abort();
            abortRef.current = new AbortController();

            setLoading(true);
            try {
                const res = await fetch(
                    `/api/search?q=${encodeURIComponent(query.trim())}`,
                    { signal: abortRef.current.signal },
                );
                const data: GameResult[] = await res.json();
                setResults(data);
                setOpen(data.length > 0);
                setActiveIndex(-1);
            } catch {
                // aborted or network error — ignore
            } finally {
                setLoading(false);
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [query]);

    // Close on outside click
    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    const navigate = (slug: string) => {
        setOpen(false);
        setQuery('');
        router.visit(`/order/${slug}`);
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (!open) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveIndex((i) => Math.min(i + 1, results.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveIndex((i) => Math.max(i - 1, -1));
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            navigate(results[activeIndex].slug);
        } else if (e.key === 'Escape') {
            setOpen(false);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (activeIndex >= 0 && results[activeIndex]) {
            navigate(results[activeIndex].slug);
        } else if (results.length > 0) {
            navigate(results[0].slug);
        }
    };

    return (
        <div ref={containerRef} className="relative flex-1">
            <form
                onSubmit={handleSubmit}
                className="flex items-center rounded-lg border border-gray-500 transition-all duration-300 focus-within:border-primary focus-within:shadow-[0_0_0_2px_rgba(168,85,247,0.2)]"
            >
                <input
                    type="text"
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onKeyDown={handleKeyDown}
                    onFocus={() => results.length > 0 && setOpen(true)}
                    placeholder="Cari Game atau Voucher"
                    autoFocus={autoFocus}
                    autoComplete="off"
                    className={`w-full bg-transparent px-5 py-2.5 text-sm placeholder-gray-400 focus:outline-none ${inputClassName}`}
                />
                <button
                    type="submit"
                    aria-label="Cari game atau voucher"
                    className={`flex cursor-pointer items-center justify-center rounded-r-lg bg-primary transition-colors duration-300 hover:bg-primary/90 ${buttonClassName}`}
                >
                    {loading ? (
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="animate-spin text-white"
                        >
                            <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                        </svg>
                    ) : (
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="text-white"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                    )}
                </button>
            </form>

            {/* Dropdown */}
            {open && (
                <ul
                    role="listbox"
                    className="absolute top-full left-0 z-[300] mt-1 w-full overflow-hidden rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)] shadow-xl"
                >
                    {results.map((game, i) => (
                        <li
                            key={game.slug}
                            role="option"
                            aria-selected={i === activeIndex}
                            onMouseEnter={() => setActiveIndex(i)}
                            onMouseDown={(e) => {
                                e.preventDefault();
                                navigate(game.slug);
                            }}
                            className={`flex cursor-pointer items-center gap-3 px-4 py-2.5 transition-colors ${
                                i === activeIndex
                                    ? 'bg-primary/20 text-white'
                                    : 'text-gray-200 hover:bg-white/5'
                            }`}
                        >
                            <div className="h-9 w-9 shrink-0 overflow-hidden rounded-lg bg-[var(--color-border-light)]">
                                {game.thumbnail ? (
                                    <img
                                        src={`/storage/${game.thumbnail}`}
                                        alt={game.name}
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="flex h-full w-full items-center justify-center text-gray-500">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" />
                                            <circle cx="8.5" cy="8.5" r="1.5" />
                                            <polyline points="21 15 16 10 5 21" />
                                        </svg>
                                    </div>
                                )}
                            </div>
                            <span className="truncate text-sm font-medium">{game.name}</span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}



