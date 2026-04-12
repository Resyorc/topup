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
    price_guest: number;
    price_bronze: number;
    price_silver: number;
    price_gold: number;
    price_platinum: number;
}

interface GameEntry {
    id: number;
    name: string;
    slug: string;
    thumbnail: string | null;
    category_id: number;
    category_name: string | null;
    product_count: number;
    products: Product[];
}

interface PriceListProps {
    categories: Category[];
    priceList: GameEntry[];
}

const TIERS = [
    { key: 'price_guest',    label: 'Guest',    color: 'text-gray-300' },
    { key: 'price_bronze',   label: 'Bronze',   color: 'text-amber-600' },
    { key: 'price_silver',   label: 'Silver',   color: 'text-slate-300' },
    { key: 'price_gold',     label: 'Gold',     color: 'text-yellow-400' },
    { key: 'price_platinum', label: 'Platinum', color: 'text-cyan-400' },
] as const;

function fmt(n: number) {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function getImageUrl(thumbnail: string | null, name: string) {
    if (thumbnail) return thumbnail;
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=ffffff&background=8327d8&size=256&rounded=true&font-size=0.33`;
}

export default function PriceList({ categories, priceList }: PriceListProps) {
    const [activeCategoryId, setActiveCategoryId] = useState<number | 'all'>('all');
    const [selectedGameId, setSelectedGameId]     = useState<number | null>(null);
    const [search, setSearch]                     = useState('');

    // Filtered game list for dropdown
    const filteredGames = useMemo(() => {
        let list = priceList;
        if (activeCategoryId !== 'all') {
            list = list.filter((g) => g.category_id === activeCategoryId);
        }
        if (search.trim()) {
            const q = search.trim().toLowerCase();
            list = list.filter((g) => g.name.toLowerCase().includes(q));
        }
        return list;
    }, [priceList, activeCategoryId, search]);

    // Auto-select first game when filters change
    const selectedGame = useMemo(() => {
        // If currently selected game is still in filtered list, keep it
        if (selectedGameId) {
            const found = filteredGames.find((g) => g.id === selectedGameId);
            if (found) return found;
        }
        return filteredGames[0] ?? null;
    }, [filteredGames, selectedGameId]);

    // Filter products by search (product name)
    const displayProducts = useMemo(() => {
        if (!selectedGame) return [];
        if (!search.trim()) return selectedGame.products;
        const q = search.trim().toLowerCase();
        // If search matches game name, show all products
        if (selectedGame.name.toLowerCase().includes(q)) return selectedGame.products;
        return selectedGame.products.filter((p) => p.name.toLowerCase().includes(q));
    }, [selectedGame, search]);

    const memberTiers = TIERS.filter((t) => t.key !== 'price_guest');

    return (
        <GuestLayout>
            <Head title="Daftar Harga — Semua Game" />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-2xl font-black text-white md:text-3xl">Daftar Harga</h1>
                    <p className="mt-1 text-sm text-gray-400">
                        Cek harga produk untuk semua game yang tersedia.
                    </p>
                </div>

                {/* Controls Row */}
                <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start">

                    {/* LEFT: Dropdown game + search */}
                    <div className="flex flex-col gap-3 lg:w-72 lg:shrink-0">
                        {/* Search */}
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari game..."
                            className="w-full rounded-xl border border-[#31334c] bg-[#1A1A24] px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-primary"
                        />

                        {/* Category Pills */}
                        <div className="flex flex-wrap gap-2">
                            <button
                                onClick={() => { setActiveCategoryId('all'); setSelectedGameId(null); }}
                                className={`rounded-full px-3 py-1 text-xs font-semibold transition ${activeCategoryId === 'all' ? 'bg-primary text-white' : 'border border-[#31334c] text-gray-400 hover:border-primary hover:text-white'}`}
                            >
                                Semua
                            </button>
                            {categories.map((c) => (
                                <button
                                    key={c.id}
                                    onClick={() => { setActiveCategoryId(c.id); setSelectedGameId(null); }}
                                    className={`rounded-full px-3 py-1 text-xs font-semibold transition ${activeCategoryId === c.id ? 'bg-primary text-white' : 'border border-[#31334c] text-gray-400 hover:border-primary hover:text-white'}`}
                                >
                                    {c.name}
                                </button>
                            ))}
                        </div>

                        {/* Game List Sidebar */}
                        <div className="flex flex-col gap-1 overflow-y-auto rounded-2xl border border-[#31334c] bg-[#1e1f29] p-2 lg:max-h-[calc(100vh-260px)]">
                            {filteredGames.length === 0 ? (
                                <p className="py-6 text-center text-xs text-gray-500">Tidak ada game ditemukan.</p>
                            ) : (
                                filteredGames.map((game) => {
                                    const isActive = selectedGame?.id === game.id;
                                    return (
                                        <button
                                            key={game.id}
                                            onClick={() => setSelectedGameId(game.id)}
                                            className={`flex items-center gap-3 rounded-xl px-3 py-2.5 text-left transition ${isActive ? 'bg-primary/20 ring-1 ring-primary/40' : 'hover:bg-white/5'}`}
                                        >
                                            <img
                                                src={getImageUrl(game.thumbnail, game.name)}
                                                alt={game.name}
                                                className="h-9 w-9 shrink-0 rounded-lg object-cover"
                                            />
                                            <div className="min-w-0 flex-1">
                                                <div className={`truncate text-sm font-semibold ${isActive ? 'text-white' : 'text-gray-300'}`}>
                                                    {game.name}
                                                </div>
                                                <div className="text-[11px] text-gray-500">{game.product_count} produk</div>
                                            </div>
                                        </button>
                                    );
                                })
                            )}
                        </div>
                    </div>

                    {/* RIGHT: Pricing Table */}
                    <div className="flex-1">
                        {!selectedGame ? (
                            <div className="flex h-60 items-center justify-center rounded-2xl border border-[#31334c] bg-[#1e1f29] text-gray-500">
                                Pilih game di sebelah kiri.
                            </div>
                        ) : (
                            <div className="rounded-2xl border border-[#31334c] bg-[#1e1f29]">
                                {/* Game Header */}
                                <div className="flex items-center gap-4 border-b border-[#31334c] px-5 py-4">
                                    <img
                                        src={getImageUrl(selectedGame.thumbnail, selectedGame.name)}
                                        alt={selectedGame.name}
                                        className="h-12 w-12 shrink-0 rounded-xl object-cover"
                                    />
                                    <div className="flex-1 min-w-0">
                                        <h2 className="text-base font-bold text-white">{selectedGame.name}</h2>
                                        <p className="text-xs text-gray-400">{selectedGame.category_name} · {selectedGame.product_count} produk</p>
                                    </div>
                                    <Link
                                        href={`/order/${selectedGame.slug}`}
                                        className="shrink-0 rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white transition hover:bg-primary/90"
                                    >
                                        Top Up Sekarang →
                                    </Link>
                                </div>

                                {/* Table */}
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-[#31334c]">
                                                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-400">Produk</th>
                                                <th className="px-3 py-3 text-right text-xs font-semibold text-gray-300">Guest</th>
                                                <th className="px-3 py-3 text-right text-xs font-semibold text-amber-600">Bronze</th>
                                                <th className="px-3 py-3 text-right text-xs font-semibold text-slate-300">Silver</th>
                                                <th className="px-3 py-3 text-right text-xs font-semibold text-yellow-400">Gold</th>
                                                <th className="px-3 py-3 text-right text-xs font-semibold text-cyan-400">Platinum</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {displayProducts.length === 0 ? (
                                                <tr>
                                                    <td colSpan={6} className="py-10 text-center text-xs text-gray-500">
                                                        Tidak ada produk ditemukan.
                                                    </td>
                                                </tr>
                                            ) : (
                                                displayProducts.map((product, i) => (
                                                    <tr
                                                        key={product.id}
                                                        className={`border-b border-[#31334c]/50 transition hover:bg-white/[0.03] ${i % 2 === 0 ? '' : 'bg-white/[0.015]'}`}
                                                    >
                                                        <td className="px-4 py-3 text-xs font-medium text-white">{product.name}</td>
                                                        {TIERS.map((tier) => (
                                                            <td key={tier.key} className={`px-3 py-3 text-right text-xs font-semibold ${tier.color} tabular-nums`}>
                                                                {fmt(product[tier.key])}
                                                            </td>
                                                        ))}
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Legend */}
                                <div className="border-t border-[#31334c] px-5 py-3">
                                    <p className="text-[11px] text-gray-500">
                                        Harga member berlaku setelah login. Tier lebih tinggi mendapatkan harga lebih murah.
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
