import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
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
}

interface WelcomeProps {
    categories: Category[];
    games: Game[];
    trendingGames: Game[];
}

export default function Welcome({ categories, games, trendingGames }: WelcomeProps) {
    // Default active category tab to the first category if it exists
    const [activeTab, setActiveTab] = useState<number | null>(
        categories.length > 0 ? categories[0].id : null
    );

    // Limit the number of games displayed initially
    const [displayLimit, setDisplayLimit] = useState<number>(12);

    // Filter games by active category
    const filteredGames = games.filter(g => g.category_id === activeTab);
    
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

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                {/* Trending Section */}
                {trendingGames.length > 0 && (
                    <section className="mb-12">
                        <div className="flex flex-col md:flex-row gap-6 md:items-center">
                            <div className="md:w-1/3">
                                <h2 className="text-2xl font-black uppercase text-white tracking-tight drop-shadow-sm">
                                    Trending Games
                                </h2>
                                <div className="mt-1 flex items-baseline gap-2">
                                    <span className="text-primary font-bold text-xl">12.928</span>
                                    <span className="text-gray-400 text-sm font-medium">Orang</span>
                                </div>
                                <p className="text-xs text-gray-500 mt-1">Telah membeli voucher dari game disamping</p>
                            </div>
                            
                            {/* Horizontal Trending Cards */}
                            <div className="md:w-2/3 flex gap-4 overflow-x-auto pb-4 scrollbar-hide md:border-l md:border-border md:pl-6">
                                {trendingGames.map(game => (
                                    <GameCard 
                                        key={game.id}
                                        id={game.id}
                                        title={game.name}
                                        subTitle={game.publisher || "Nebustore"}
                                        imgSrc={getImageUrl(game.thumbnail || game.image, game.name)}
                                        slug={game.slug}
                                        isSmall={true}
                                    />
                                ))}
                            </div>
                        </div>
                    </section>
                )}

                {/* Main Catalog Section */}
                <section className="mb-16">
                    {/* Category Tabs */}
                    <div className="flex border-b border-border/60 mb-8 overflow-x-auto scrollbar-hide">
                        {categories.map((category) => (
                            <button
                                key={category.id}
                                onClick={() => handleTabChange(category.id)}
                                className={`flex-1 min-w-[140px] py-4 px-6 text-sm font-bold text-center border-b-2 transition-all duration-300 ${
                                    activeTab === category.id 
                                        ? 'border-primary text-primary bg-primary/5' 
                                        : 'border-transparent text-gray-400 hover:text-gray-200 hover:bg-white/5'
                                } flex items-center justify-center gap-2`}
                            >
                                {/* We can add some generic icons based on name later. For now, just a generic box icon */}
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                {category.name}
                            </button>
                        ))}
                    </div>

                    {/* Games Grid */}
                    {displayedGames.length > 0? (
                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 md:gap-6">
                            {displayedGames.map((game) => (
                                <GameCard 
                                    key={game.id}
                                    id={game.id}
                                    title={game.name}
                                    subTitle={game.publisher || "Nebustore"}
                                    imgSrc={getImageUrl(game.image, game.name)}
                                    slug={game.slug}
                                    cardSize="sm"
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="py-20 text-center border border-dashed border-border rounded-2xl">
                            <p className="text-gray-500 font-medium">Tidak ada game di kategori ini untuk saat ini.</p>
                        </div>
                    )}
                    
                    {/* Menampilkan tombol "Lihat Lainnya" jika game lebih dari kapasitas grid */}
                    {filteredGames.length >= 12 && (
                        <button onClick={() => setDisplayLimit(prev => prev + 12)} className="relative mt-12 flex w-full items-center justify-center py-6 group gap-4">
                            <div className="flex-1 border-t border-white/20"></div>
                            
                            <div className="relative z-10 flex items-center gap-2 rounded-full border border-white/20 bg-client-ground px-6 py-2.5 text-sm font-semibold text-white transition-all duration-300 group-hover:px-8">
                                Lihat Lainnya
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="transition-transform group-hover:translate-y-0.5"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                            
                            <div className="flex-1 border-t border-white/20"></div>
                        </button>
                    )}
                </section>
            </div>

            <PromoBanner />
        </GuestLayout>
    );
}
