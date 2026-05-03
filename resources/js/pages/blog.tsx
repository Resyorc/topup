import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';

interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    thumbnail: string | null;
    published_at: string | null;
    reading_time: number;
}

interface BlogProps {
    articles: Article[];
}

function ClockIcon() {
    return (
        <svg
            width="12"
            height="12"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" />
        </svg>
    );
}

function CalendarIcon() {
    return (
        <svg
            width="12"
            height="12"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <rect width="18" height="18" x="3" y="4" rx="2" />
            <line x1="16" x2="16" y1="2" y2="6" />
            <line x1="8" x2="8" y1="2" y2="6" />
            <line x1="3" x2="21" y1="10" y2="10" />
        </svg>
    );
}

function ArticlePlaceholder() {
    return (
        <div className="flex h-full w-full items-center justify-center bg-linear-to-br from-(--color-bg-card) to-(--color-bg-main)">
            <svg
                width="40"
                height="40"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="1"
                strokeLinecap="round"
                strokeLinejoin="round"
                className="text-gray-700"
            >
                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" x2="8" y1="13" y2="13" />
                <line x1="16" x2="8" y1="17" y2="17" />
                <line x1="10" x2="8" y1="9" y2="9" />
            </svg>
        </div>
    );
}

function ArticleMeta({
    date,
    readingTime,
}: {
    date: string | null;
    readingTime: number;
}) {
    return (
        <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
            {date && (
                <span className="flex min-w-0 items-center gap-1">
                    <CalendarIcon />
                    <span className="truncate">{date}</span>
                </span>
            )}
            <span className="flex shrink-0 items-center gap-1">
                <ClockIcon />
                {readingTime} menit baca
            </span>
        </div>
    );
}

export default function Blog({ articles }: BlogProps) {
    const [featured, ...rest] = articles;

    return (
        <GuestLayout>
            <Head title="Blog & Berita" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 md:py-10 lg:px-8">
                {/* Header */}
                <div className="mb-8 max-w-2xl md:mb-10">
                    <p className="mb-1 text-xs font-semibold tracking-widest text-[var(--color-accent)] uppercase">
                        Blog & Berita
                    </p>
                    <h1 className="text-2xl font-black text-white md:text-3xl">
                        Tips, Promo & Info Game
                    </h1>
                    <p className="mt-2 text-sm leading-relaxed text-gray-400">
                        Artikel terbaru seputar top up game, promo, dan panduan
                        bermain.
                    </p>
                </div>

                {articles.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-24 text-center">
                        <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white/5">
                            <svg
                                width="32"
                                height="32"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="1.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                className="text-gray-500"
                            >
                                <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2" />
                            </svg>
                        </div>
                        <p className="text-gray-500">
                            Belum ada artikel yang dipublikasikan.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-10">
                        {/* Featured Article */}
                        {featured && (
                            <Link
                                href={`/blog/${featured.slug}`}
                                className="group relative flex flex-col overflow-hidden rounded-2xl border border-(--color-border-light) bg-(--color-bg-card) transition hover:border-[var(--color-accent-border)] md:min-h-72 md:flex-row"
                            >
                                <div className="aspect-video w-full shrink-0 overflow-hidden md:aspect-auto md:w-2/5">
                                    {featured.thumbnail ? (
                                        <img
                                            src={featured.thumbnail}
                                            alt={featured.title}
                                            className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                        />
                                    ) : (
                                        <ArticlePlaceholder />
                                    )}
                                </div>

                                <div className="flex min-w-0 flex-col justify-between p-5 sm:p-6 md:p-8">
                                    <div>
                                        <span className="mb-3 inline-block rounded-full border border-[var(--color-highlight-border)] bg-[var(--color-highlight-soft)] px-3 py-1 text-xs font-semibold text-[var(--color-highlight)]">
                                            Artikel Utama
                                        </span>
                                        <h2 className="line-clamp-3 text-xl leading-snug font-black break-words text-white transition group-hover:text-[var(--color-accent)] md:line-clamp-2 md:text-2xl">
                                            {featured.title}
                                        </h2>
                                        {featured.excerpt && (
                                            <p className="mt-3 line-clamp-3 text-sm leading-relaxed text-gray-400">
                                                {featured.excerpt}
                                            </p>
                                        )}
                                    </div>
                                    <div className="mt-5 flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <ArticleMeta
                                            date={featured.published_at}
                                            readingTime={featured.reading_time}
                                        />
                                        <span className="shrink-0 text-xs font-semibold text-[var(--color-accent)]">
                                            Baca →
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        )}

                        {/* Article Grid */}
                        {rest.length > 0 && (
                            <div>
                                <h2 className="mb-5 text-sm font-semibold tracking-widest text-gray-500 uppercase">
                                    Artikel Lainnya
                                </h2>
                                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                    {rest.map((article) => (
                                        <Link
                                            key={article.id}
                                            href={`/blog/${article.slug}`}
                                            className="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-(--color-border-light) bg-(--color-bg-card) transition hover:border-[var(--color-accent-border)]"
                                        >
                                            <div className="aspect-video w-full overflow-hidden">
                                                {article.thumbnail ? (
                                                    <img
                                                        src={article.thumbnail}
                                                        alt={article.title}
                                                        className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                                    />
                                                ) : (
                                                    <ArticlePlaceholder />
                                                )}
                                            </div>

                                            <div className="flex min-w-0 flex-1 flex-col p-4">
                                                <ArticleMeta
                                                    date={article.published_at}
                                                    readingTime={
                                                        article.reading_time
                                                    }
                                                />
                                                <h3 className="mt-2 line-clamp-2 text-sm leading-snug font-bold break-words text-white transition group-hover:text-[var(--color-accent)] md:text-base">
                                                    {article.title}
                                                </h3>
                                                {article.excerpt && (
                                                    <p className="mt-1.5 line-clamp-2 text-xs text-gray-400 md:text-sm">
                                                        {article.excerpt}
                                                    </p>
                                                )}
                                                <div className="mt-auto pt-3">
                                                    <span className="text-xs font-semibold text-[var(--color-accent)]">
                                                        Baca selengkapnya →
                                                    </span>
                                                </div>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </GuestLayout>
    );
}
