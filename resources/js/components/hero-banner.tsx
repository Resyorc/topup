import { useCallback, useEffect, useState } from 'react';

interface Banner {
    image: string;
    link: string | null;
}

interface HeroBannerProps {
    banners: Banner[];
}

const AUTOPLAY_INTERVAL = 5000;

export default function HeroBanner({ banners }: HeroBannerProps) {
    const [current, setCurrent] = useState(0);

    const count = banners.length;

    const goNext = useCallback(() => {
        setCurrent((prev) => (prev + 1) % count);
    }, [count]);

    // Reset index if banner list changes
    useEffect(() => {
        setCurrent(0);
    }, [count]);

    // Autoplay
    useEffect(() => {
        if (count <= 1) return;
        const timer = setInterval(goNext, AUTOPLAY_INTERVAL);
        return () => clearInterval(timer);
    }, [goNext, count]);

    if (count === 0) return null;

    const handleClick = (link: string | null) => {
        if (link) window.location.href = link;
    };

    return (
        <div className="relative mb-10 w-full">
            <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                {/* Slider Track */}
                <div className="relative aspect-21/9 overflow-hidden rounded-3xl shadow-2xl md:aspect-3/1">
                    {banners.map((banner, index) => (
                        <div
                            key={index}
                            onClick={() => handleClick(banner.link)}
                            className={`absolute inset-0 transition-opacity duration-700 ${
                                index === current ? 'opacity-100' : 'opacity-0 pointer-events-none'
                            } ${banner.link ? 'cursor-pointer' : ''}`}
                        >
                            <img
                                src={`/storage/${banner.image}`}
                                alt={`Banner ${index + 1}`}
                                className="h-full w-full object-cover"
                            />
                        </div>
                    ))}

                    {/* Prev / Next arrows — only show when there's more than 1 banner */}
                    {count > 1 && (
                        <>
                            <button
                                onClick={() => setCurrent((prev) => (prev - 1 + count) % count)}
                                aria-label="Previous banner"
                                className="absolute top-1/2 left-3 z-10 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white backdrop-blur-sm transition hover:bg-black/60 md:left-4 md:p-2.5"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="m15 18-6-6 6-6" />
                                </svg>
                            </button>
                            <button
                                onClick={goNext}
                                aria-label="Next banner"
                                className="absolute top-1/2 right-3 z-10 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white backdrop-blur-sm transition hover:bg-black/60 md:right-4 md:p-2.5"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </button>
                        </>
                    )}
                </div>

                {/* Dot Indicators — only show when there's more than 1 banner */}
                {count > 1 && (
                    <div className="mt-4 flex justify-center gap-2">
                        {banners.map((_, index) => (
                            <button
                                key={index}
                                onClick={() => setCurrent(index)}
                                aria-label={`Go to banner ${index + 1}`}
                                className={`h-2 rounded-full transition-all duration-300 ${
                                    index === current
                                        ? 'w-8 bg-primary shadow-sm'
                                        : 'w-2 bg-gray-600 hover:bg-gray-400'
                                }`}
                            />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
