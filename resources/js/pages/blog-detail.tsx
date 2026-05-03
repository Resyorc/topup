import { useEffect, useRef, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import DOMPurify from 'dompurify';
import GuestLayout from '@/layouts/guest-layout';

interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    thumbnail: string | null;
    published_at: string | null;
    reading_time: number;
}

interface RelatedArticle {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    thumbnail: string | null;
    published_at: string | null;
    reading_time: number;
}

interface BlogDetailProps {
    article: Article;
    related_articles: RelatedArticle[];
}

interface TocItem {
    id: string;
    text: string;
    level: number;
}

function useReadingProgress(ref: React.RefObject<HTMLElement | null>) {
    const [progress, setProgress] = useState(0);
    useEffect(() => {
        const onScroll = () => {
            const el = ref.current;
            if (!el) return;
            const top = el.getBoundingClientRect().top + window.scrollY;
            const height = el.offsetHeight;
            const scrolled = window.scrollY - top + window.innerHeight * 0.2;
            setProgress(Math.min(100, Math.max(0, (scrolled / height) * 100)));
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, [ref]);
    return progress;
}

function useToc(contentRef: React.RefObject<HTMLDivElement | null>) {
    const [toc, setToc] = useState<TocItem[]>([]);
    const [activeId, setActiveId] = useState('');

    useEffect(() => {
        const el = contentRef.current;
        if (!el) return;

        const headings = Array.from(el.querySelectorAll('h2, h3'));
        const items: TocItem[] = headings.map((h, i) => {
            const id = h.id || `heading-${i}`;
            h.id = id;
            return {
                id,
                text: h.textContent ?? '',
                level: parseInt(h.tagName[1]),
            };
        });
        setToc(items);

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) setActiveId(entry.target.id);
                });
            },
            { rootMargin: '-10% 0% -80% 0%' },
        );
        headings.forEach((h) => observer.observe(h));
        return () => observer.disconnect();
    }, [contentRef]);

    return { toc, activeId };
}

function ShareButtons({ title, slug }: { title: string; slug: string }) {
    const [copied, setCopied] = useState(false);
    const url =
        typeof window !== 'undefined' ? window.location.href : `/blog/${slug}`;

    const copyLink = () => {
        navigator.clipboard.writeText(url).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    const waUrl = `https://wa.me/?text=${encodeURIComponent(title + '\n' + url)}`;

    return (
        <div className="flex flex-wrap items-center gap-2">
            <span className="shrink-0 text-xs text-gray-500">Bagikan:</span>
            <button
                onClick={copyLink}
                className="flex shrink-0 items-center gap-1.5 rounded-lg border border-(--color-border-light) bg-(--color-bg-card) px-3 py-1.5 text-xs font-medium text-gray-300 transition hover:border-[var(--color-accent-border)] hover:text-white"
            >
                {copied ? (
                    <>
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="text-green-400"
                        >
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Tersalin!
                    </>
                ) : (
                    <>
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                        </svg>
                        Salin Link
                    </>
                )}
            </button>
            <a
                href={waUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="flex shrink-0 items-center gap-1.5 rounded-lg border border-(--color-border-light) bg-(--color-bg-card) px-3 py-1.5 text-xs font-medium text-gray-300 transition hover:border-green-500/40 hover:text-green-400"
            >
                <svg
                    width="13"
                    height="13"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    className="text-green-500"
                >
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
                </svg>
                WhatsApp
            </a>
        </div>
    );
}

function TableOfContents({
    toc,
    activeId,
}: {
    toc: TocItem[];
    activeId: string;
}) {
    if (toc.length < 2) return null;
    return (
        <nav className="rounded-xl border border-(--color-border-light) bg-(--color-bg-card) p-4">
            <p className="mb-3 text-xs font-semibold tracking-widest text-gray-500 uppercase">
                Daftar Isi
            </p>
            <ul className="space-y-0.5 border-l-2 border-(--color-border-light) pl-0">
                {toc.map((item) => (
                    <li
                        key={item.id}
                        className={`-ml-0.5 border-l-2 transition-colors ${activeId === item.id ? 'border-[var(--color-accent)]' : 'border-transparent'}`}
                        style={{
                            paddingLeft:
                                item.level === 3 ? '1.5rem' : '0.75rem',
                        }}
                    >
                        <a
                            href={`#${item.id}`}
                            onClick={(e) => {
                                e.preventDefault();
                                document
                                    .getElementById(item.id)
                                    ?.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'start',
                                    });
                            }}
                            className={`block py-1 text-sm leading-snug transition-colors ${
                                activeId === item.id
                                    ? 'font-semibold text-[var(--color-accent)]'
                                    : 'text-gray-500 hover:text-gray-200'
                            }`}
                        >
                            {item.text}
                        </a>
                    </li>
                ))}
            </ul>
        </nav>
    );
}

function RelatedCard({ article }: { article: RelatedArticle }) {
    return (
        <Link
            href={`/blog/${article.slug}`}
            className="group flex min-w-0 flex-col gap-3 rounded-xl border border-(--color-border-light) bg-(--color-bg-card) p-4 transition hover:border-[var(--color-accent-border)] sm:flex-row sm:gap-4"
        >
            <div className="aspect-video w-full shrink-0 overflow-hidden rounded-lg bg-(--color-bg-main) sm:aspect-auto sm:h-20 sm:w-28">
                {article.thumbnail ? (
                    <img
                        src={article.thumbnail}
                        alt={article.title}
                        className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center">
                        <svg
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="1.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="text-gray-700"
                        >
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
                            <polyline points="14 2 14 8 20 8" />
                        </svg>
                    </div>
                )}
            </div>
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2 text-xs text-gray-500">
                    {article.published_at && (
                        <span>{article.published_at}</span>
                    )}
                    <span>·</span>
                    <span>{article.reading_time} menit</span>
                </div>
                <h4 className="mt-1 line-clamp-2 text-sm leading-snug font-bold break-words text-white transition group-hover:text-[var(--color-accent)]">
                    {article.title}
                </h4>
            </div>
        </Link>
    );
}

export default function BlogDetail({
    article,
    related_articles,
}: BlogDetailProps) {
    const safeContent =
        typeof window !== 'undefined'
            ? DOMPurify.sanitize(article.content)
            : article.content;

    const articleRef = useRef<HTMLElement>(null);
    const contentRef = useRef<HTMLDivElement>(null);
    const progress = useReadingProgress(articleRef);
    const { toc, activeId } = useToc(contentRef);

    return (
        <GuestLayout>
            <Head title={article.title}>
                {article.excerpt && (
                    <meta name="description" content={article.excerpt} />
                )}
                {article.thumbnail && (
                    <meta property="og:image" content={article.thumbnail} />
                )}
            </Head>

            {/* Reading Progress Bar */}
            <div className="fixed top-0 left-0 z-[70] h-0.5 w-full bg-[var(--color-accent-soft)]">
                <div
                    className="h-full bg-[var(--color-accent)] transition-all duration-150"
                    style={{ width: `${progress}%` }}
                />
            </div>

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 md:py-10 lg:px-8">
                {/* Breadcrumb */}
                <nav className="mb-6 flex min-w-0 items-center gap-2 overflow-hidden text-xs text-gray-500">
                    <Link
                        href="/"
                        className="shrink-0 transition hover:text-white"
                    >
                        Beranda
                    </Link>
                    <span className="shrink-0">/</span>
                    <Link
                        href="/blog"
                        className="shrink-0 transition hover:text-white"
                    >
                        Blog
                    </Link>
                    <span className="shrink-0">/</span>
                    <span className="min-w-0 truncate text-gray-400">
                        {article.title}
                    </span>
                </nav>

                <div className="flex flex-col gap-10 lg:flex-row">
                    {/* Main Content */}
                    <article ref={articleRef} className="min-w-0 flex-1">
                        {/* Hero Image */}
                        {article.thumbnail && (
                            <div className="mb-6 overflow-hidden rounded-2xl md:mb-8">
                                <img
                                    src={article.thumbnail}
                                    alt={article.title}
                                    className="max-h-[460px] w-full object-cover"
                                />
                            </div>
                        )}

                        {/* Article Header */}
                        <header className="mb-8 md:mb-10">
                            <h1 className="text-2xl leading-tight font-black break-words text-white md:text-4xl lg:text-5xl">
                                {article.title}
                            </h1>
                            {article.excerpt && (
                                <p className="mt-4 text-base leading-relaxed text-gray-400 md:text-lg">
                                    {article.excerpt}
                                </p>
                            )}

                            {/* Meta row */}
                            <div className="mt-6 flex flex-col items-start gap-4 border-t border-(--color-border-light) pt-5 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                                <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500">
                                    {article.published_at && (
                                        <span className="flex items-center gap-1.5">
                                            <svg
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            >
                                                <rect
                                                    width="18"
                                                    height="18"
                                                    x="3"
                                                    y="4"
                                                    rx="2"
                                                />
                                                <line
                                                    x1="16"
                                                    x2="16"
                                                    y1="2"
                                                    y2="6"
                                                />
                                                <line
                                                    x1="8"
                                                    x2="8"
                                                    y1="2"
                                                    y2="6"
                                                />
                                                <line
                                                    x1="3"
                                                    x2="21"
                                                    y1="10"
                                                    y2="10"
                                                />
                                            </svg>
                                            {article.published_at}
                                        </span>
                                    )}
                                    <span className="flex items-center gap-1.5">
                                        <svg
                                            width="14"
                                            height="14"
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
                                        {article.reading_time} menit baca
                                    </span>
                                </div>
                                <ShareButtons
                                    title={article.title}
                                    slug={article.slug}
                                />
                            </div>
                        </header>

                        {/* TOC (mobile only — inline) */}
                        {toc.length >= 2 && (
                            <div className="mb-8 lg:hidden">
                                <TableOfContents
                                    toc={toc}
                                    activeId={activeId}
                                />
                            </div>
                        )}

                        {/* Content */}
                        <div
                            ref={contentRef}
                            className="article-content min-w-0"
                            dangerouslySetInnerHTML={{ __html: safeContent }}
                        />

                        {/* Bottom Share */}
                        <div className="mt-12 flex flex-col gap-5 border-t border-(--color-border-light) pt-8 sm:flex-row sm:items-center sm:justify-between">
                            <ShareButtons
                                title={article.title}
                                slug={article.slug}
                            />
                            <Link
                                href="/blog"
                                className="inline-flex w-fit items-center gap-2 text-sm font-semibold text-[var(--color-accent)] transition hover:text-[var(--color-accent-hover)]"
                            >
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M19 12H5M12 19l-7-7 7-7" />
                                </svg>
                                Kembali ke Blog
                            </Link>
                        </div>

                        {/* Related Articles */}
                        {related_articles.length > 0 && (
                            <section className="mt-14">
                                <h2 className="mb-5 text-sm font-semibold tracking-widest text-gray-500 uppercase">
                                    Artikel Terkait
                                </h2>
                                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {related_articles.map((rel) => (
                                        <RelatedCard
                                            key={rel.id}
                                            article={rel}
                                        />
                                    ))}
                                </div>
                            </section>
                        )}
                    </article>

                    {/* Sidebar TOC (desktop) */}
                    {toc.length >= 2 && (
                        <aside className="hidden w-64 shrink-0 lg:block">
                            <div className="sticky top-24">
                                <TableOfContents
                                    toc={toc}
                                    activeId={activeId}
                                />
                            </div>
                        </aside>
                    )}
                </div>
            </div>
        </GuestLayout>
    );
}
