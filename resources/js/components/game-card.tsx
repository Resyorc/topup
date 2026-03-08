import { InertiaLinkProps, Link } from "@inertiajs/react";
import { useState, useRef, useEffect } from "react";

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
}

export default function GameCard({ id, cardSize = "md", active = false, customClass = "", title, subTitle, imgSrc, slug, isSmall = false }: GameCardProps) {
    const xsCardSize = 'w-32 h-[180px]';
    const smCardSize = 'w-44 h-[244px]';
    const mdCardSize = 'w-[14.5rem] h-[330px]'; // Fixed size to preserve layout when used within a flex container
    const lgCardSize = 'w-72 h-[372px]';
    
    const cardSizeClass = ((cardSize === 'xs' ? xsCardSize :(cardSize === 'sm' ? smCardSize : (cardSize === 'md' ? mdCardSize : (cardSize === 'lg' ? lgCardSize : mdCardSize)))));
    
    const [loaded, setLoaded] = useState(false);
    const [error, setError] = useState(false);
    const [currentImgSrc, setCurrentImgSrc] = useState(imgSrc);
    const imgRef = useRef<HTMLImageElement>(null);

    // Ensure images loaded from cache still trigger the 'loaded' state
    useEffect(() => {
        if (imgRef.current && imgRef.current.complete) {
            setLoaded(true);
        }
    }, [currentImgSrc]);

    // Update if props change
    useEffect(() => {
        setCurrentImgSrc(imgSrc);
        setLoaded(false);
        setError(false);
    }, [imgSrc]);

    const handleImageError = () => {
        const fallback = `https://ui-avatars.com/api/?name=${encodeURIComponent(title)}&color=ffffff&background=8327d8&size=512&rounded=true&font-size=0.33`;
        if (currentImgSrc !== fallback) {
            setCurrentImgSrc(fallback);
        } else {
            setError(true);
        }
    };

    if (isSmall) {
        return (
            <Link 
                href={`/order/${slug}`} 
                className={`group relative block w-[70px] h-[70px] md:w-[80px] md:h-[80px] overflow-hidden rounded-xl bg-card border border-border shadow-md transition hover:-translate-y-1 shrink-0 ${customClass}`}
            >
                <img 
                    src={currentImgSrc} 
                    alt={title} 
                    className="absolute inset-0 w-full h-full object-cover transition duration-300 group-hover:scale-110"
                    onError={handleImageError}
                />
            </Link>
        );
    }

    const targetHref = `/order/${slug}`;

    return (
        <Link href={(active ? undefined : targetHref)} className={"group transition-all duration-100 ease-in-out block m-3 sm:m-4 " + (active ? "cursor-default pointer-events-none " : "cursor-pointer hover:-translate-y-1 ") + (customClass)}>
            <div className={(active ? "drop-shadow-[0_0_15px_#8327d8]" : "group-hover:drop-shadow-[0_0_15px_#8327d8]")}>     {/* Spread Shadow */}
            <div className={(active ? "drop-shadow-[0_-2px_0_#8327d8]" : "group-hover:drop-shadow-[0_-2px_0_#8327d8]")}>     {/* Top Outline */}
            <div className={(active ? "drop-shadow-[2.5px_0_0_#8327d8]" : "group-hover:drop-shadow-[2.5px_0_0_#8327d8]")}>    {/* Right Outline */}
            <div className={(active ? "drop-shadow-[0_2.5px_0_#8327d8]" : "group-hover:drop-shadow-[0_2.5px_0_#8327d8]")}>    {/* Bottom Outline */}
            <div className={(active ? "drop-shadow-[-2px_0_0_#8327d8]" : "group-hover:drop-shadow-[-2px_0_0_#8327d8]")}>     {/* Left Outline */}
                <div
                    className={`relative select-none bg-card overflow-hidden ${cardSizeClass}`}
                    style={{ clipPath: 'polygon(25% 0%, 100% 0, 100% 90%, 85% 100%, 0 100%, 0 20%)' }}
                >
                    {/* Skeleton loader */}
                    {!loaded && !error && <div className="absolute inset-0 w-full h-full animate-pulse bg-secondary" />}

                    {/* Image */}
                    {!error && (
                        <img
                            ref={imgRef}
                            src={currentImgSrc}
                            alt={title}
                            className={`absolute inset-0 w-full h-full object-cover transition-opacity duration-700 ${
                                loaded ? 'opacity-100' : 'opacity-0'
                            } group-hover:scale-110 group-hover:rotate-1`}
                            onLoad={() => setLoaded(true)}
                            onError={handleImageError}
                        />
                    )}

                    {/* Error fallback */}
                    {error && (
                        <div className="absolute inset-0 w-full h-full flex flex-col items-center justify-center bg-card text-gray-400">
                            <span className="text-center text-xs sm:text-base">(￣<span>◇</span>￣)</span>
                            <span className="text-center text-[10px] sm:text-xs mt-1">Gagal muat.</span>
                        </div>
                    )}

                    {/* Title */}
                    <div className="absolute bottom-0 left-0 right-0 px-3 pb-4 pt-16 bg-gradient-to-t from-[#111218] via-[#111218]/80 to-transparent">
                        <p className="font-bold text-white text-sm md:text-base truncate drop-shadow-md">{title}</p>
                        <p className="text-xs font-light text-gray-300 truncate mt-1 drop-shadow-md">{(subTitle ? subTitle : title)}</p>
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
