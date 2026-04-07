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
}

interface BlogDetailProps {
    article: Article;
}

export default function BlogDetail({ article }: BlogDetailProps) {
    const safeContent = typeof window !== 'undefined'
        ? DOMPurify.sanitize(article.content)
        : article.content;

    return (
        <GuestLayout>
            <Head title={article.title}>
                {article.excerpt && <meta name="description" content={article.excerpt} />}
                {article.thumbnail && <meta property="og:image" content={article.thumbnail} />}
            </Head>

            <div className="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
                {/* Breadcrumb */}
                <nav className="mb-6 flex items-center gap-2 text-xs text-gray-500">
                    <Link href="/" className="hover:text-white">Beranda</Link>
                    <span>/</span>
                    <Link href="/blog" className="hover:text-white">Blog</Link>
                    <span>/</span>
                    <span className="line-clamp-1 text-gray-400">{article.title}</span>
                </nav>

                {/* Thumbnail */}
                {article.thumbnail && (
                    <div className="mb-8 overflow-hidden rounded-2xl">
                        <img
                            src={article.thumbnail}
                            alt={article.title}
                            className="w-full object-cover"
                        />
                    </div>
                )}

                {/* Header */}
                <div className="mb-8">
                    {article.published_at && (
                        <p className="mb-3 text-xs text-gray-500">{article.published_at}</p>
                    )}
                    <h1 className="text-2xl font-black leading-snug text-white md:text-3xl">
                        {article.title}
                    </h1>
                    {article.excerpt && (
                        <p className="mt-3 text-base text-gray-400">{article.excerpt}</p>
                    )}
                </div>

                {/* Article Content */}
                <div
                    className="prose prose-invert prose-sm max-w-none md:prose-base"
                    dangerouslySetInnerHTML={{ __html: safeContent }}
                />

                {/* Back Link */}
                <div className="mt-12 border-t border-[#31334c] pt-8">
                    <Link
                        href="/blog"
                        className="inline-flex items-center gap-2 text-sm font-semibold text-primary transition hover:opacity-80"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M19 12H5M12 19l-7-7 7-7" />
                        </svg>
                        Kembali ke Blog
                    </Link>
                </div>
            </div>
        </GuestLayout>
    );
}
