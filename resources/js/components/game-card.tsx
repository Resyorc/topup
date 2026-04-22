import { Link } from '@inertiajs/react';
import { useState, useRef, useEffect } from 'react';

function buildSrcSet(src: string): string | undefined {
    if (!src.startsWith('/storage/') || !src.endsWith('.webp'))
        return undefined;
    const smSrc = src.replace(/\.webp$/, '-sm.webp');
    return `${smSrc} 188w, ${src} 374w`;
}

const srcSetSizes: Record<string, string> = {
    xs: '(min-width: 1024px) 128px, 188px',
    sm: '(min-width: 1024px) 176px, 188px',
    md: '(min-width: 1024px) 232px, 188px',
    lg: '(min-width: 1024px) 288px, 188px',
};

interface GameCardProps {
    cardSize?: 'xs' | 'sm' | 'md' | 'lg';
    active?: boolean;
    customClass?: string;
    id?: number | string;
    title: string;
    subTitle?: string;
    imgSrc: string;
    slug: string;
    isSmall?: boolean;
    priority?: boolean;
}

export default function GameCard({
    cardSize = 'md',
    active = false,
    customClass = '',
    title,
    subTitle,
    imgSrc,
    slug,
    isSmall = false,
    priority = false,
}: GameCardProps) {
    /**
     * Card sizing strategy:
     * - Below lg (<1024px): Fluid — w-full fills the grid column, aspect-[3/4]
     *   keeps a consistent ratio. This covers mobile, small tablets, and devices
     *   like Nest Hub (1024x600) where fixed-pixel cards are too large.
     * - Desktop (lg+, ≥1024px): Original fixed pixel sizes so the wide-screen
     *   desktop layout is completely unchanged.
     */
    const sizeMap = {
        xs: 'w-full aspect-[3/4] lg:w-32 lg:h-[180px] lg:aspect-auto',
        sm: 'w-full aspect-[3/4] lg:w-44 lg:h-[244px] lg:aspect-auto',
        md: 'w-full aspect-[3/4] lg:w-[14.5rem] lg:h-[330px] lg:aspect-auto',
        lg: 'w-full aspect-[3/4] lg:w-72 lg:h-[372px] lg:aspect-auto',
    };

    const cardSizeClass = sizeMap[cardSize] || sizeMap.md;

    const [loaded, setLoaded] = useState(priority);
    const [error, setError] = useState(false);
    const [currentImgSrc, setCurrentImgSrc] = useState(imgSrc);
    const [prevImgSrc, setPrevImgSrc] = useState(imgSrc);
    const imgRef = useRef<HTMLImageElement>(null);

    // Ensure images loaded from cache still trigger the 'loaded' state
    useEffect(() => {
        const img = imgRef.current;
        if (img && img.complete) {
            const timerId = setTimeout(() => setLoaded(true), 0);
            return () => clearTimeout(timerId);
        }
    }, [currentImgSrc]);

    // Reset state when imgSrc prop changes (React derived-state pattern)
    if (prevImgSrc !== imgSrc) {
        setPrevImgSrc(imgSrc);
        setCurrentImgSrc(imgSrc);
        setLoaded(false);
        setError(false);
    }

    const handleImageError = () => {
        const fallback = `https://ui-avatars.com/api/?name=${encodeURIComponent(title)}&color=ffffff&background=8327d8&size=512&rounded=true&font-size=0.33`;
        if (currentImgSrc !== fallback) {
            setCurrentImgSrc(fallback);
        } else {
            setError(true);
        }
    };

    // ===== Small variant (used in Trending section) — unchanged =====
    if (isSmall) {
        return (
            <Link
                href={`/order/${slug}`}
                className={`group relative block h-[70px] w-[70px] shrink-0 overflow-hidden rounded-xl border border-border bg-card shadow-md transition hover:-translate-y-1 md:h-[80px] md:w-[80px] ${customClass}`}
            >
                <img
                    src={currentImgSrc}
                    alt={title}
                    className="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-110"
                    loading={priority ? 'eager' : 'lazy'}
                    fetchPriority={priority ? 'high' : 'auto'}
                    onError={handleImageError}
                />
            </Link>
        );
    }

    // ===== Regular card variant =====
    const targetHref = `/order/${slug}`;

    return (
        /**
         * Margin fix:
         * - Mobile: m-0 because the parent grid's `gap` already provides spacing
         *   between cards. Adding extra margin would double the spacing and push
         *   cards outside their grid cells.
         * - Desktop: original m-3 / sm:m-4 preserved so nothing changes there.
         */
        <Link
            href={active ? undefined : targetHref}
            className={`group m-0 block transition-all duration-100 ease-in-out lg:m-3 ${
                active
                    ? 'pointer-events-none cursor-default'
                    : 'cursor-pointer hover:-translate-y-1'
            } ${customClass}`}
        >
            <div
                className={
                    active
                        ? 'drop-shadow-[0_0_15px_var(--color-primary)]'
                        : 'group-hover:drop-shadow-[0_0_15px_var(--color-primary)]'
                }
            >
                {' '}
                {/* Spread Shadow */}
                <div
                    className={
                        active
                            ? 'drop-shadow-[0_-2px_0_var(--color-primary)]'
                            : 'group-hover:drop-shadow-[0_-2px_0_var(--color-primary)]'
                    }
                >
                    {' '}
                    {/* Top Outline */}
                    <div
                        className={
                            active
                                ? 'drop-shadow-[2.5px_0_0_var(--color-primary)]'
                                : 'group-hover:drop-shadow-[2.5px_0_0_var(--color-primary)]'
                        }
                    >
                        {' '}
                        {/* Right Outline */}
                        <div
                            className={
                                active
                                    ? 'drop-shadow-[0_2.5px_0_var(--color-primary)]'
                                    : 'group-hover:drop-shadow-[0_2.5px_0_var(--color-primary)]'
                            }
                        >
                            {' '}
                            {/* Bottom Outline */}
                            <div
                                className={
                                    active
                                        ? 'drop-shadow-[-2px_0_0_var(--color-primary)]'
                                        : 'group-hover:drop-shadow-[-2px_0_0_var(--color-primary)]'
                                }
                            >
                                {' '}
                                {/* Left Outline */}
                                <div
                                    className={`relative overflow-hidden bg-card select-none ${cardSizeClass}`}
                                    style={{
                                        clipPath:
                                            'polygon(25% 0%, 100% 0, 100% 90%, 85% 100%, 0 100%, 0 20%)',
                                    }}
                                >
                                    {/* Skeleton loader */}
                                    {!loaded && !error && (
                                        <div className="absolute inset-0 h-full w-full animate-pulse bg-secondary" />
                                    )}

                                    {/* Image */}
                                    {!error && (
                                        <img
                                            ref={imgRef}
                                            src={currentImgSrc}
                                            srcSet={buildSrcSet(currentImgSrc)}
                                            sizes={
                                                buildSrcSet(currentImgSrc)
                                                    ? srcSetSizes[cardSize]
                                                    : undefined
                                            }
                                            alt={title}
                                            className={`absolute inset-0 h-full w-full object-cover transition-opacity duration-700 ${
                                                loaded
                                                    ? 'opacity-100'
                                                    : 'opacity-0'
                                            } group-hover:scale-110 group-hover:rotate-1`}
                                            onLoad={() => setLoaded(true)}
                                            onError={handleImageError}
                                            loading={
                                                priority ? 'eager' : 'lazy'
                                            }
                                            fetchPriority={
                                                priority ? 'high' : 'auto'
                                            }
                                        />
                                    )}

                                    {/* Error fallback */}
                                    {error && (
                                        <div className="absolute inset-0 flex h-full w-full flex-col items-center justify-center bg-card text-gray-400">
                                            <span className="text-center text-xs sm:text-base">
                                                (￣<span>◇</span>￣)
                                            </span>
                                            <span className="mt-1 text-center text-[10px] sm:text-xs">
                                                Gagal muat.
                                            </span>
                                        </div>
                                    )}

                                    {/* 
                      Title overlay:
                      - Mobile: compact padding (px-2 pb-2) and smaller text (text-[11px])
                        so the title fits neatly inside the smaller card without overflowing.
                      - Desktop: original sizing (px-3 pb-4 pt-16, text-base) preserved.
                    */}
                                    <div className="absolute right-0 bottom-0 left-0 bg-gradient-to-t from-[var(--color-bg-main)] via-[var(--color-bg-main)]/80 to-transparent px-2 pt-10 pb-2 lg:px-3 lg:pt-16 lg:pb-4">
                                        <p className="truncate text-[11px] font-bold text-white drop-shadow-md lg:text-base">
                                            {title}
                                        </p>
                                        <p className="mt-0.5 truncate text-[9px] font-light text-gray-300 drop-shadow-md lg:mt-1 lg:text-xs">
                                            {subTitle ? subTitle : title}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Link>
    );
}


