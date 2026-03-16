import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';
import HeroBanner from '@/components/hero-banner';
import PromoBanner from '@/components/promo-banner';
import GameCard from '@/components/game-card';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Game {
    id: number;
    category_id: number;
    name: string;
    slug: string;
    image: string | null;
    thumbnail: string | null;
    publisher: string | null;
    total_sold?: number;
}

interface WelcomeProps {
    categories: Category[];
    games: Game[];
    trendingGames: Game[];
    trendingTotalSold: number;
}

export default function Welcome({
    categories,
    games,
    trendingGames,
    trendingTotalSold,
}: WelcomeProps) {
    const { auth } = usePage<{ auth: { user: unknown } }>().props;

    // Default active category tab to the first category if it exists
    const [activeTab, setActiveTab] = useState<number | null>(
        categories.length > 0 ? categories[0].id : null,
    );

    // Limit the number of games displayed initially
    const [displayLimit, setDisplayLimit] = useState<number>(12);

    // Filter games by active category
    const filteredGames = games.filter((g) => g.category_id === activeTab);

    // Slice to the current limit
    const displayedGames = filteredGames.slice(0, displayLimit);

    // Reset display limit when changing tabs
    const handleTabChange = (categoryId: number) => {
        setActiveTab(categoryId);
        setDisplayLimit(12);
    };

    // Helper to get image URL properly (from storage or dummy)
    const getImageUrl = (image: string | null, name: string) => {
        if (image && image.length > 0) {
            return `/storage/${image}`;
        }
        // Fallback to UI-Avatars for a nice looking placeholder based on the game name
        return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=ffffff&background=8327d8&size=512&rounded=true&font-size=0.33`;
    };

    return (
        <GuestLayout>
            <Head title="Top Up Game Favoritmu" />

            <HeroBanner />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {/* ===== Trending Section ===== */}
                {/* On mobile: stacked layout (column). On desktop: side-by-side (row) — unchanged. */}
                {trendingGames.length > 0 && (
                    <section className="mb-8 md:mb-12">
                        <div className="flex flex-col gap-4 md:flex-row md:items-center md:gap-6">
                            {/* Trending info text — full width on mobile */}
                            <div className="w-full md:w-1/3">
                                <h2 className="text-xl font-black tracking-tight text-white uppercase drop-shadow-sm md:text-2xl">
                                    Trending Games
                                </h2>
                                <div className="mt-1 flex items-baseline gap-2">
                                    <span className="text-lg font-bold text-primary md:text-xl">
                                        {trendingTotalSold.toLocaleString(
                                            'id-ID',
                                        )}
                                    </span>
                                    <span className="text-xs font-medium text-gray-400 md:text-sm">
                                        Total Pesanan
                                    </span>
                                </div>
                                <p className="mt-1 text-[10px] text-gray-500 md:text-xs">
                                    Paling banyak di pesan dari game disamping
                                </p>
                            </div>

                            {/* Horizontal scrolling trending cards — works naturally on mobile */}
                            <div className="scrollbar-hide flex w-full gap-3 overflow-x-auto pb-2 md:w-2/3 md:gap-4 md:border-l md:border-border md:pb-4 md:pl-6">
                                {trendingGames.map((game) => (
                                    <GameCard
                                        key={game.id}
                                        id={game.id}
                                        title={game.name}
                                        subTitle={game.publisher || 'Nuvelo'}
                                        imgSrc={getImageUrl(
                                            game.thumbnail || game.image,
                                            game.name,
                                        )}
                                        slug={game.slug}
                                        isSmall={true}
                                    />
                                ))}
                            </div>
                        </div>
                    </section>
                )}

                {/* ===== Main Catalog Section ===== */}
                <section className="mb-10 md:mb-16">
                    {/* Category Tabs — horizontally scrollable on mobile with smaller sizing */}
                    <div className="scrollbar-hide -mx-4 mb-6 flex overflow-x-auto border-b border-border/60 px-4 md:mx-0 md:mb-8 md:px-0">
                        {categories.map((category) => (
                            <button
                                key={category.id}
                                onClick={() => handleTabChange(category.id)}
                                className={`min-w-[110px] flex-1 border-b-2 px-4 py-3 text-center text-xs font-bold transition-all duration-300 md:min-w-[140px] md:px-6 md:py-4 md:text-sm ${
                                    activeTab === category.id
                                        ? 'border-primary bg-primary/5 text-primary'
                                        : 'border-transparent text-gray-400 hover:bg-white/5 hover:text-gray-200'
                                } flex items-center justify-center gap-1.5 whitespace-nowrap md:gap-2`}
                            >
                                {/* Category icon */}
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="flex-shrink-0 md:h-[18px] md:w-[18px]"
                                >
                                    <path d="m7.5 4.27 9 5.15" />
                                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                    <path d="m3.3 7 8.7 5 8.7-5" />
                                    <path d="M12 22V12" />
                                </svg>
                                {category.name}
                            </button>
                        ))}
                    </div>

                    {/* Games Grid — 3 columns on mobile (matching Figma design), scales up on larger screens */}
                    {displayedGames.length > 0 ? (
                        <div className="grid grid-cols-3 gap-2 md:grid-cols-4 lg:grid-cols-6 lg:gap-4">
                            {displayedGames.map((game) => (
                                <GameCard
                                    key={game.id}
                                    id={game.id}
                                    title={game.name}
                                    subTitle={game.publisher || 'Nuvelo'}
                                    imgSrc={getImageUrl(game.image, game.name)}
                                    slug={game.slug}
                                    cardSize="sm"
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-2xl border border-dashed border-border py-12 text-center md:py-20">
                            <p className="text-sm font-medium text-gray-500 md:text-base">
                                Tidak ada game di kategori ini untuk saat ini.
                            </p>
                        </div>
                    )}

                    {/* "Lihat Lainnya" button — slightly compact on mobile */}
                    {filteredGames.length >= 12 && (
                        <button
                            onClick={() => setDisplayLimit((prev) => prev + 12)}
                            className="group relative mt-8 flex w-full items-center justify-center gap-4 py-4 md:mt-12 md:py-6"
                        >
                            <div className="flex-1 border-t border-white/20"></div>

                            <div className="relative z-10 flex items-center gap-2 rounded-full border border-white/20 bg-client-ground px-5 py-2 text-xs font-semibold text-white transition-all duration-300 group-hover:px-8 md:px-6 md:py-2.5 md:text-sm">
                                Lihat Lainnya
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="transition-transform group-hover:translate-y-0.5 md:h-[18px] md:w-[18px]"
                                >
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </div>

                            <div className="flex-1 border-t border-white/20"></div>
                        </button>
                    )}
                </section>
            </div>

            {!auth.user && <PromoBanner />}
        </GuestLayout>
    );
}
