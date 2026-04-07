import { Head, Link } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import GuestLayout from '@/layouts/guest-layout';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Product {
    id: string;
    name: string;
    clean_name: string;
    price: number;
    original_price: number | null;
    discount_percent: number;
    extra: string | null;
    flash_sale_ends_at: number | null;
}

interface GameEntry {
    id: number;
    name: string;
    slug: string;
    thumbnail: string | null;
    category_id: number;
    category_name: string | null;
    product_count: number;
    min_price: number | null;
    products: Record<string, Product[]> | Record<string, Record<string, Product[]>>;
}

interface PriceListProps {
    categories: Category[];
    priceList: GameEntry[];
}

function flattenProducts(products: GameEntry['products']): Product[] {
    const values = Object.values(products);
    if (values.length === 0) return [];
    // Check if nested (regions) or flat (categories)
    if (Array.isArray(values[0])) {
        return (values as Product[][]).flat();
    }
    return Object.values(values as Record<string, Product[]>).flat().flat();
}

function StatusBadge({ status }: { status: string }) {
    const map: Record<string, string> = {
        success: 'bg-green-500/20 text-green-400',
        failed: 'bg-red-500/20 text-red-400',
        pending: 'bg-yellow-500/20 text-yellow-400',
        processing: 'bg-blue-500/20 text-blue-400',
        paid: 'bg-cyan-500/20 text-cyan-400',
        expired: 'bg-gray-500/20 text-gray-400',
        canceled: 'bg-gray-500/20 text-gray-400',
        coin_topup: 'bg-amber-500/20 text-amber-400',
    };
    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize ${map[status] ?? 'bg-gray-500/20 text-gray-400'}`}>
            {status}
        </span>
    );
}

export default function PriceList({ categories, priceList }: PriceListProps) {
    const [activeCategoryId, setActiveCategoryId] = useState<number | 'all'>('all');
    const [search, setSearch] = useState('');
    const [expandedGame, setExpandedGame] = useState<number | null>(null);

    const filtered = useMemo(() => {
        let list = priceList;

        if (activeCategoryId !== 'all') {
            list = list.filter((g) => g.category_id === activeCategoryId);
        }

        if (search.trim()) {
            const q = search.trim().toLowerCase();
            list = list.filter((g) => {
                if (g.name.toLowerCase().includes(q)) return true;
                const products = flattenProducts(g.products);
                return products.some((p) => p.clean_name.toLowerCase().includes(q));
            });
        }

        return list;
    }, [priceList, activeCategoryId, search]);

    const toggleGame = (id: number) =>
        setExpandedGame((prev) => (prev === id ? null : id));

    const getImageUrl = (thumbnail: string | null, name: string) => {
        if (thumbnail) return thumbnail;
        return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=ffffff&background=8327d8&size=256&rounded=true&font-size=0.33`;
    };

    return (
        <GuestLayout>
            <Head title="Daftar Harga — Semua Game" />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-2xl font-black text-white md:text-3xl">
                        Daftar Harga
                    </h1>
                    <p className="mt-1 text-sm text-gray-400">
                        Cek harga produk untuk semua game yang tersedia.
                    </p>
                </div>

                {/* Search + Category Filter */}
                <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-center">
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cari game atau produk..."
                        className="w-full rounded-xl border border-[#31334c] bg-[#1A1A24] px-4 py-3 text-sm text-white placeholder-gray-500 outline-none transition focus:border-primary md:max-w-sm"
                    />

                    <div className="flex flex-wrap gap-2">
                        <button
                            onClick={() => setActiveCategoryId('all')}
                            className={`rounded-full px-4 py-1.5 text-xs font-semibold transition ${activeCategoryId === 'all' ? 'bg-primary text-white' : 'border border-[#31334c] text-gray-400 hover:border-primary hover:text-white'}`}
                        >
                            Semua
                        </button>
                        {categories.map((c) => (
                            <button
                                key={c.id}
                                onClick={() => setActiveCategoryId(c.id)}
                                className={`rounded-full px-4 py-1.5 text-xs font-semibold transition ${activeCategoryId === c.id ? 'bg-primary text-white' : 'border border-[#31334c] text-gray-400 hover:border-primary hover:text-white'}`}
                            >
                                {c.name}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Game List */}
                {filtered.length === 0 ? (
                    <div className="py-20 text-center text-gray-500">
                        Tidak ada game ditemukan.
                    </div>
                ) : (
                    <div className="flex flex-col gap-3">
                        {filtered.map((game) => {
                            const isExpanded = expandedGame === game.id;
                            const allProducts = flattenProducts(game.products);

                            // Filter products if searching by product name
                            const searchQ = search.trim().toLowerCase();
                            const displayProducts = searchQ && !game.name.toLowerCase().includes(searchQ)
                                ? allProducts.filter((p) => p.clean_name.toLowerCase().includes(searchQ))
                                : allProducts;

                            return (
                                <div
                                    key={game.id}
                                    className="overflow-hidden rounded-2xl border border-[#31334c] bg-[#1e1f29]"
                                >
                                    {/* Game Row Header */}
                                    <button
                                        className="flex w-full items-center gap-4 px-4 py-4 text-left transition hover:bg-white/5"
                                        onClick={() => toggleGame(game.id)}
                                    >
                                        <img
                                            src={getImageUrl(game.thumbnail, game.name)}
                                            alt={game.name}
                                            className="h-12 w-12 shrink-0 rounded-xl object-cover"
                                        />
                                        <div className="min-w-0 flex-1">
                                            <div className="font-bold text-white">{game.name}</div>
                                            <div className="mt-0.5 text-xs text-gray-400">
                                                {game.category_name} &middot; {game.product_count} produk
                                                {game.min_price && (
                                                    <> &middot; Mulai <span className="font-semibold text-primary">Rp {game.min_price.toLocaleString('id-ID')}</span></>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-3 shrink-0">
                                            <Link
                                                href={`/order/${game.slug}`}
                                                onClick={(e) => e.stopPropagation()}
                                                className="hidden rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-white transition hover:bg-primary/90 sm:block"
                                            >
                                                Top Up
                                            </Link>
                                            <svg
                                                className={`h-4 w-4 text-gray-400 transition-transform ${isExpanded ? 'rotate-180' : ''}`}
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                strokeWidth={2}
                                            >
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </button>

                                    {/* Product Table */}
                                    {isExpanded && (
                                        <div className="border-t border-[#31334c] px-4 pb-4 pt-3">
                                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                                {displayProducts.map((product) => (
                                                    <div
                                                        key={product.id}
                                                        className="flex items-center justify-between rounded-xl border border-[#31334c] bg-[#1A1A24] px-3 py-2.5"
                                                    >
                                                        <div className="min-w-0 flex-1">
                                                            <div className="truncate text-xs font-semibold text-[#FFC107]">
                                                                {product.clean_name}
                                                            </div>
                                                            {product.extra && (
                                                                <div className="truncate text-[10px] text-gray-500">{product.extra}</div>
                                                            )}
                                                        </div>
                                                        <div className="ml-3 shrink-0 text-right">
                                                            {product.discount_percent > 0 && (
                                                                <div className="flex items-center justify-end gap-1">
                                                                    <span className={`rounded px-1 py-0.5 text-[9px] font-bold text-white ${product.flash_sale_ends_at ? 'bg-orange-600' : 'bg-orange-500'}`}>
                                                                        {product.flash_sale_ends_at ? '⚡' : ''}{product.discount_percent}%
                                                                    </span>
                                                                    <span className="text-[10px] text-gray-500 line-through">
                                                                        Rp {product.original_price?.toLocaleString('id-ID')}
                                                                    </span>
                                                                </div>
                                                            )}
                                                            <div className="text-sm font-bold text-white">
                                                                Rp {product.price.toLocaleString('id-ID')}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                            <div className="mt-3 flex justify-end">
                                                <Link
                                                    href={`/order/${game.slug}`}
                                                    className="rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white transition hover:bg-primary/90"
                                                >
                                                    Top Up Sekarang →
                                                </Link>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </GuestLayout>
    );
}
