import { Head, Link, usePage } from '@inertiajs/react';
import { icons } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import GameCard from '@/components/game-card';
import HeroBanner from '@/components/hero-banner';
import PromoBanner from '@/components/promo-banner';
import GuestLayout from '@/layouts/guest-layout';

interface Category {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
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

interface Banner {
    image: string;
    link: string | null;
}

interface FlashSaleInfo {
    id: string;
    name: string;
    clean_name: string;
    game_name: string;
    game_slug: string;
    logo_url: string | null;
    flash_sale_price: number;
    regular_price: number;
    discount_percent: number;
    flash_sale_ends_at: number;
    flash_sale_stock: number | null;
    flash_sale_purchased: number;
}

interface WelcomeProps {
    banners: Banner[];
    categories: Category[];
    games: Game[];
    trendingGames: Game[];
    trendingTotalSold: number;
    loyaltyMinAmount: number;
    loyaltyRate: number;
    flashSaleItems?: FlashSaleInfo[];
}

function FlashSaleCard({ item }: { item: FlashSaleInfo }) {
    const pct = item.flash_sale_stock && item.flash_sale_stock > 0
        ? Math.min(100, Math.round((item.flash_sale_purchased / item.flash_sale_stock) * 100))
        : null;
    const outOfStock = item.flash_sale_stock !== null && item.flash_sale_purchased >= item.flash_sale_stock;

    return (
        <Link
            href={`/order/${item.game_slug}`}
            className={`group relative flex w-36 shrink-0 flex-col overflow-hidden rounded-xl border border-[#31334c] bg-[#1a1b26] transition hover:border-orange-500/50 sm:w-44 md:w-48 ${outOfStock ? 'pointer-events-none opacity-50' : ''}`}
        >
            {item.discount_percent > 0 && (
                <div className="absolute top-2 left-2 z-10 rounded-md bg-orange-500 px-1.5 py-0.5 text-[10px] font-black text-white">
                    -{item.discount_percent}%
                </div>
            )}
            <div className="flex h-20 items-center justify-center bg-[#12121a] sm:h-24">
                {item.logo_url ? (
                    <img src={item.logo_url} alt={item.clean_name} className="h-12 w-12 object-contain sm:h-14 sm:w-14" />
                ) : (
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-500/10">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" className="text-orange-400/50">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
                        </svg>
                    </div>
                )}
            </div>
            <div className="flex flex-1 flex-col gap-1.5 p-2.5">
                <p className="line-clamp-2 text-[11px] font-semibold leading-tight text-white sm:text-xs">{item.name}</p>
                <p className="text-[10px] text-gray-500">{item.game_name}</p>
                <div className="mt-auto">
                    <p className="text-xs font-black text-white sm:text-sm">
                        Rp {item.flash_sale_price.toLocaleString('id-ID')}
                    </p>
                    {item.regular_price > 0 && (
                        <p className="text-[10px] text-gray-400 line-through">
                            Rp {item.regular_price.toLocaleString('id-ID')}
                        </p>
                    )}
                </div>
                {pct !== null && (
                    <div>
                        <div className="h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                            <div className="h-full rounded-full bg-orange-500 transition-all" style={{ width: `${pct}%` }} />
                        </div>
                        <p className="mt-1 text-[10px] text-gray-500">
                            {outOfStock ? 'Out of Stock' : `${item.flash_sale_purchased} / ${item.flash_sale_stock} purchased`}
                        </p>
                    </div>
                )}
            </div>
        </Link>
    );
}

function FlashSaleSection({ items }: { items: FlashSaleInfo[] }) {
    const [countdown, setCountdown] = useState<{ h: string; m: string; s: string } | null>(null);
    const trackRef = useRef<HTMLDivElement>(null);
    const pausedRef = useRef(false);
    const rafRef = useRef<number>(0);

    const endMs = items[0]?.flash_sale_ends_at ? items[0].flash_sale_ends_at * 1000 : null;

    // Countdown
    useEffect(() => {
        if (!endMs) return;
        const update = () => {
            const diff = Math.max(0, Math.floor((endMs - Date.now()) / 1000));
            setCountdown({
                h: String(Math.floor(diff / 3600)).padStart(2, '0'),
                m: String(Math.floor((diff % 3600) / 60)).padStart(2, '0'),
                s: String(diff % 60).padStart(2, '0'),
            });
        };
        update();
        const id = setInterval(update, 1000);
        return () => clearInterval(id);
    }, [endMs]);

    // Auto-scroll marquee — hanya aktif jika item lebih dari 3
    useEffect(() => {
        const track = trackRef.current;
        if (!track || items.length <= 3) return;

        let pos = 0;
        const speed = 0.5; // px per frame

        const animate = () => {
            if (!pausedRef.current && track) {
                pos += speed;
                // Lebar setengah track (clone) → reset seamless
                const half = track.scrollWidth / 2;
                if (pos >= half) pos = 0;
                track.style.transform = `translateX(-${pos}px)`;
            }
            rafRef.current = requestAnimationFrame(animate);
        };

        rafRef.current = requestAnimationFrame(animate);
        return () => cancelAnimationFrame(rafRef.current);
    }, [items.length]);

    // Clone items untuk loop seamless (hanya kalau > 3)
    const displayItems = items.length > 3 ? [...items, ...items] : items;
    const isMarquee = items.length > 3;

    return (
        <section className="mb-8 md:mb-12">
            {/* Header */}
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-3">
                    <div className="flex items-center gap-1.5 rounded-lg bg-orange-500/15 px-3 py-1.5">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" className="text-orange-400">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
                        </svg>
                        <span className="text-sm font-black tracking-wide text-orange-400 uppercase">Flash Sale</span>
                    </div>
                    {countdown && (
                        <div className="flex items-center gap-1">
                            {[countdown.h, countdown.m, countdown.s].map((val, i) => (
                                <span key={i} className="flex items-center gap-1">
                                    <span className="min-w-7 rounded bg-[#1e1f29] px-1.5 py-0.5 text-center font-mono text-xs font-bold text-orange-400">{val}</span>
                                    {i < 2 && <span className="text-orange-400 font-bold">:</span>}
                                </span>
                            ))}
                        </div>
                    )}
                </div>
                <p className="text-[11px] font-semibold text-orange-400/60 uppercase tracking-wider">Persediaan terbatas!</p>
            </div>

            {/* Cards */}
            {isMarquee ? (
                // Marquee mode: overflow hidden, auto-scroll, pause on hover/touch
                <div
                    className="overflow-hidden"
                    onMouseEnter={() => { pausedRef.current = true; }}
                    onMouseLeave={() => { pausedRef.current = false; }}
                    onTouchStart={() => { pausedRef.current = true; }}
                    onTouchEnd={() => { pausedRef.current = false; }}
                >
                    <div ref={trackRef} className="flex gap-3 will-change-transform">
                        {displayItems.map((item, i) => (
                            <FlashSaleCard key={`${item.id}-${i}`} item={item} />
                        ))}
                    </div>
                </div>
            ) : (
                // Few items: normal responsive grid
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    {items.map((item) => (
                        <FlashSaleCard key={item.id} item={item} />
                    ))}
                </div>
            )}
        </section>
    );
}

export default function Welcome({
    banners,
    categories,
    games,
    trendingGames,
    trendingTotalSold,
    loyaltyMinAmount,
    loyaltyRate,
    flashSaleItems = [],
}: WelcomeProps) {
    const { auth, appUrl } = usePage<{
        auth: { user: unknown };
        appUrl: string;
    }>().props;

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
            <Head title="Nuvelo: Top Up Game Murah - Top Up ML & Top Up FF Cepat dan Aman">
                <meta
                    name="description"
                    content="Nuvelo — platform top up game terpercaya Indonesia. Top up Mobile Legends, Free Fire, PUBG Mobile, dan 100+ game dengan harga murah, proses instan, dan aman."
                />
                <link rel="canonical" href={appUrl} />
                <meta property="og:type" content="website" />
                <meta property="og:site_name" content="Nuvelo" />
                <meta property="og:url" content={appUrl} />
                <meta
                    property="og:title"
                    content="Nuvelo: Top Up Game Murah - Top Up ML & Top Up FF Cepat dan Aman"
                />
                <meta
                    property="og:description"
                    content="Nuvelo — platform top up game terpercaya Indonesia. Top up Mobile Legends, Free Fire, PUBG Mobile, dan 100+ game dengan harga murah, proses instan, dan aman."
                />
                <meta property="og:image" content={`${appUrl}/logo.png`} />
                <meta name="twitter:card" content="summary_large_image" />
                <meta
                    name="twitter:title"
                    content="Nuvelo: Top Up Game Murah - Top Up ML & Top Up FF Cepat dan Aman"
                />
                <meta
                    name="twitter:description"
                    content="Nuvelo — platform top up game terpercaya Indonesia. Top up Mobile Legends, Free Fire, PUBG Mobile, dan 100+ game dengan harga murah, proses instan, dan aman."
                />
                <meta name="twitter:image" content={`${appUrl}/logo.png`} />
                {displayedGames[0]?.image && (
                    <link
                        rel="preload"
                        as="image"
                        href={`/storage/${displayedGames[0].image}`}
                        fetchPriority="high"
                    />
                )}
            </Head>

            <HeroBanner banners={banners} />

            <div
                className={`mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 ${banners.length === 0 ? 'pt-6 md:pt-8' : ''}`}
            >
                {/* ===== Flash Sale Section ===== */}
                {flashSaleItems.length > 0 && (
                    <FlashSaleSection items={flashSaleItems} />
                )}

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
                                    <span className="text-lg font-bold text-client-primary md:text-xl">
                                        {trendingTotalSold.toLocaleString(
                                            'id-ID',
                                        )}
                                    </span>
                                    <span className="text-xs font-medium text-gray-400 md:text-sm">
                                        Total Pesanan
                                    </span>
                                </div>
                                <p className="mt-1 text-xs text-gray-300 md:hidden">
                                    Paling banyak di pesan dari game dibawah
                                </p>
                                <p className="mt-1 hidden text-xs text-gray-300 md:block">
                                    Paling banyak di pesan dari game disamping
                                </p>
                            </div>

                            {/* Horizontal scrolling trending cards — works naturally on mobile */}
                            <div className="scrollbar-hide flex w-full gap-3 overflow-x-auto pb-2 md:w-2/3 md:gap-4 md:border-l md:border-border md:pb-4 md:pl-6">
                                {trendingGames.map((game, index) => (
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
                                        priority={index === 0}
                                    />
                                ))}
                            </div>
                        </div>
                    </section>
                )}

                {/* ===== Loyalty Program Banner ===== */}
                {auth.user && (
                    <section className="mb-6 md:mb-10">
                        <div className="rounded-2xl border border-yellow-500/20 bg-gradient-to-r from-yellow-500/5 via-amber-500/5 to-yellow-500/5 px-4 py-3.5 md:px-6 md:py-4">
                            <div className="flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-yellow-500/15 md:h-11 md:w-11">
                                    <img
                                        src="/coin.png"
                                        alt="Coin"
                                        className="h-5 w-5 object-contain md:h-6 md:w-6"
                                    />
                                </div>
                                <div className="flex-1">
                                    <h3 className="text-sm font-bold text-yellow-400 md:text-[0.9rem]">
                                        Dapatkan Krysta Coin Setiap Top Up!
                                    </h3>
                                    <p className="mt-0.5 text-xs leading-relaxed text-gray-400">
                                        Setiap top up berhasil via{' '}
                                        <span className="font-semibold text-gray-300">
                                            QRIS, E-Wallet, atau Virtual Account
                                        </span>{' '}
                                        min.{' '}
                                        <span className="font-semibold text-gray-300">
                                            Rp{' '}
                                            {loyaltyMinAmount.toLocaleString(
                                                'id-ID',
                                            )}
                                        </span>
                                        , kamu otomatis mendapat{' '}
                                        <span className="font-semibold text-yellow-400">
                                            cashback {loyaltyRate}%
                                        </span>{' '}
                                        dalam bentuk Krysta Coin — bisa dipakai
                                        untuk top up berikutnya.
                                    </p>
                                </div>
                                <div className="flex shrink-0 flex-row items-center gap-4 rounded-xl border border-yellow-500/20 bg-yellow-500/8 px-4 py-2.5 sm:flex-col sm:gap-0">
                                    <p className="text-xl leading-none font-black text-yellow-400">
                                        {loyaltyRate}%
                                    </p>
                                    <p className="text-[10px] text-gray-400 sm:mt-0.5">
                                        Cashback Coin
                                    </p>
                                </div>
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
                                        ? 'border-primary bg-primary/5 text-client-primary'
                                        : 'border-transparent text-gray-300 hover:bg-white/5 hover:text-white'
                                } flex items-center justify-center gap-1.5 whitespace-nowrap md:gap-2`}
                            >
                                {/* Category icon */}
                                {(() => {
                                    const iconName = category.icon
                                        ? category.icon
                                              .split('-')
                                              .map(
                                                  (s: string) =>
                                                      s
                                                          .charAt(0)
                                                          .toUpperCase() +
                                                      s.slice(1),
                                              )
                                              .join('')
                                        : 'Layers';
                                    const LucideIcon =
                                        icons[iconName as keyof typeof icons];
                                    return LucideIcon ? (
                                        <LucideIcon className="h-4 w-4 shrink-0 md:h-4.5 md:w-4.5" />
                                    ) : null;
                                })()}
                                {category.name}
                            </button>
                        ))}
                    </div>

                    {/* Games Grid — 3 columns on mobile (matching Figma design), scales up on larger screens */}
                    {displayedGames.length > 0 ? (
                        <div className="grid grid-cols-3 gap-2 md:grid-cols-4 lg:grid-cols-6 lg:gap-4">
                            {displayedGames.map((game, index) => (
                                <GameCard
                                    key={game.id}
                                    id={game.id}
                                    title={game.name}
                                    subTitle={game.publisher || 'Nuvelo'}
                                    imgSrc={getImageUrl(game.image, game.name)}
                                    slug={game.slug}
                                    cardSize="sm"
                                    priority={index === 0}
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
