import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';

interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    thumbnail: string | null;
    published_at: string | null;
}

interface BlogProps {
    articles: Article[];
}

export default function Blog({ articles }: BlogProps) {
    return (
        <GuestLayout>
            <Head title="Blog & Berita" />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-2xl font-black text-white md:text-3xl">Blog & Berita</h1>
                    <p className="mt-1 text-sm text-gray-400">Tips, info promo, dan berita terbaru seputar top up game.</p>
                </div>

                {articles.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-24 text-center">
                        <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white/5">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-gray-500">
                                <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2" />
                                <path d="M18 14h-8" /><path d="M15 18h-5" /><path d="M10 6h8v4h-8V6Z" />
                            </svg>
                        </div>
                        <p className="text-gray-500">Belum ada artikel yang dipublikasikan.</p>
                    </div>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {articles.map((article) => (
                            <Link
                                key={article.id}
                                href={`/blog/${article.slug}`}
                                className="group flex flex-col overflow-hidden rounded-2xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)] transition hover:border-primary/50"
                            >
                                {/* Thumbnail */}
                                <div className="aspect-video w-full overflow-hidden bg-[var(--color-bg-main)]">
                                    {article.thumbnail ? (
                                        <img
                                            src={article.thumbnail}
                                            alt={article.title}
                                            className="h-full w-full object-cover transition group-hover:scale-105"
                                        />
                                    ) : (
                                        <div className="flex h-full w-full items-center justify-center">
                                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-gray-600">
                                                <rect width="18" height="18" x="3" y="3" rx="2" />
                                                <path d="M3 9h18M9 21V9" />
                                            </svg>
                                        </div>
                                    )}
                                </div>

                                {/* Content */}
                                <div className="flex flex-1 flex-col p-5">
                                    {article.published_at && (
                                        <p className="mb-2 text-xs text-gray-500">{article.published_at}</p>
                                    )}
                                    <h2 className="line-clamp-2 text-base font-bold text-white transition group-hover:text-primary">
                                        {article.title}
                                    </h2>
                                    {article.excerpt && (
                                        <p className="mt-2 line-clamp-3 text-sm text-gray-400">{article.excerpt}</p>
                                    )}
                                    <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-primary">
                                        Baca selengkapnya
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                            <path d="M5 12h14M12 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </GuestLayout>
    );
}



