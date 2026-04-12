import SearchBar from '@/components/search-bar';
import { Link, usePage } from '@inertiajs/react';
import { useState, useMemo, lazy, Suspense } from 'react';
import { Toaster } from 'sonner';
import NewsTicker from '@/components/news-ticker';

// LiveChat dimuat lazy — tidak memblokir initial render, axios tidak masuk critical path
const LiveChat = lazy(() => import('@/components/live-chat'));

export default function GuestLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { auth, broadcastMessages, webSetting } = usePage().props as any;
    const currentUrl = usePage().url;
    const hasBroadcast = broadcastMessages && broadcastMessages.length > 0;

    // Bangun chat context dari URL saat ini
    const chatContext = useMemo(() => {
        const ctx: Record<string, string> = { page: currentUrl };

        // /invoice?invoice_id=INV-xxx
        const invoiceMatch = currentUrl.match(/[?&]invoice_id=([^&]+)/);
        if (invoiceMatch) ctx.invoice_id = decodeURIComponent(invoiceMatch[1]);

        // /order/{slug}
        const orderMatch = currentUrl.match(/^\/order\/([^?/]+)/);
        if (orderMatch) ctx.game_slug = orderMatch[1];

        return ctx;
    }, [currentUrl]);

    const [mobileSearchOpen, setMobileSearchOpen] = useState(false);

    // Helper: check if a nav link is currently active
    const isActive = (href: string) => {
        if (href === '/') return currentUrl === '/';
        return currentUrl.startsWith(href);
    };

    return (
        <>
            <div className="flex min-h-screen flex-col pt-[116px] md:pt-[106px]">
                {/* Header Navbar */}
                <header className="fixed inset-x-0 top-0 z-50 bg-[#3E3D49CC] text-white shadow-md backdrop-blur-sm">
                    <div className="mx-auto flex w-full max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 md:gap-10 md:py-4 lg:px-8">
                        {/* Logo — cropped image with no padding, sits naturally at left */}
                        <Link
                            href="/"
                            className="flex shrink-0 cursor-pointer items-center focus:outline-0"
                        >
                            {webSetting?.logo ? (
                                <img src={webSetting.logo} alt="Nuvelo" className="h-10 w-auto md:h-12" />
                            ) : (
                                <picture>
                                    <source srcSet="/logo-2x.webp" type="image/webp" />
                                    <img src="/logo.png" alt="Nuvelo" className="h-10 w-auto md:h-12" width="280" height="96" />
                                </picture>
                            )}
                        </Link>

                        {/* Desktop Search Bar */}
                        <div className="hidden flex-1 md:flex">
                            <SearchBar
                                inputClassName="text-white my-auto"
                                buttonClassName="px-5 py-2.5"
                            />
                        </div>

                        {/* Right Side — Auth / Icons */}
                        <div className="flex items-center gap-2 md:gap-3">
                            {/* Mobile-only: search toggle button */}
                            <button
                                onClick={() =>
                                    setMobileSearchOpen(!mobileSearchOpen)
                                }
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-500/50 bg-white/10 transition hover:bg-white/20 md:hidden"
                                aria-label="Buka pencarian"
                            >
                                <svg
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="text-gray-300"
                                >
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line
                                        x1="21"
                                        y1="21"
                                        x2="16.65"
                                        y2="16.65"
                                    ></line>
                                </svg>
                            </button>

                            {auth?.user ? (
                                <div className="flex items-center gap-2 md:gap-3">
                                    <Link
                                        href="/dashboard"
                                        className={`hidden h-10 w-10 items-center justify-center overflow-hidden rounded-full border-2 transition md:flex ${isActive('/dashboard') ? 'border-primary bg-primary/20 text-white shadow-[0_0_12px_rgba(131,39,216,0.35)]' : 'border-primary/40 bg-white/10 text-gray-300 hover:bg-white/20'}`}
                                        aria-label="Buka dashboard"
                                    >
                                        <svg
                                            width="20"
                                            height="20"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            className="md:[height:24px] md:[width:24px]"
                                        >
                                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                            <circle cx="12" cy="7" r="4" />
                                        </svg>
                                    </Link>
                                </div>
                            ) : (
                                <div className="flex items-center gap-2 md:gap-3">
                                    {/* Masuk button — shown on mobile (next to search) and desktop */}
                                    <Link
                                        href="/login"
                                        className="rounded-md border border-white/70 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/20 md:px-4 md:py-2 md:text-sm"
                                    >
                                        Masuk
                                    </Link>
                                    {/* Daftar button — desktop only (unchanged) */}
                                    <Link
                                        href="/register"
                                        className="hidden rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-[0_0_10px_#8327d8] transition hover:hue-rotate-15 md:inline-block"
                                    >
                                        Daftar
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* ===== Mobile Search Bar — slides down when toggled ===== */}
                    {mobileSearchOpen && (
                        <div className="px-4 pb-3 md:hidden">
                            <SearchBar
                                inputClassName="text-white"
                                buttonClassName="px-4 py-2.5"
                                autoFocus
                            />
                        </div>
                    )}

                    {/* Sub-Header Navigation with Ticker */}
                    <div className="flex w-full items-center justify-center bg-[#6C3C89E6] text-sm font-medium shadow-inner select-none">
                        <div className="mx-auto flex w-full max-w-7xl flex-col-reverse flex-nowrap items-center gap-0 px-4 sm:px-4 md:flex-row md:gap-12 md:px-8">
                            {/* Static Links — hidden on mobile (bottom nav handles navigation), visible on desktop */}
                            <div className="hidden w-full flex-nowrap items-center justify-center gap-x-6 pb-2 sm:flex-nowrap sm:justify-between sm:gap-12 sm:gap-x-8 md:flex md:w-auto md:pb-0">
                                <Link
                                    href="/"
                                    className={`group relative flex cursor-pointer items-center py-2 text-nowrap transition md:py-3 ${isActive('/') ? 'text-client-warning' : 'text-white hover:text-gray-200'}`}
                                >
                                    <div className="flex items-center justify-between gap-2 md:gap-2.5">
                                        <svg
                                            width="16"
                                            height="17"
                                            viewBox="0 0 18 19"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                            className="stroke-white md:h-[19px] md:w-[18px]"
                                        >
                                            <path
                                                d="M15.75 3.125L9 9.875M9 9.875H13.008M9 9.875V5.867"
                                                strokeWidth="1.125"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            />
                                            <path
                                                d="M16.5 9.875C16.5 13.4105 16.5 15.1782 15.4012 16.2762C14.304 17.375 12.5355 17.375 9 17.375C5.4645 17.375 3.69675 17.375 2.598 16.2762C1.5 15.179 1.5 13.4105 1.5 9.875C1.5 6.3395 1.5 4.57175 2.598 3.473C3.6975 2.375 5.4645 2.375 9 2.375"
                                                strokeWidth="1.125"
                                                strokeLinecap="round"
                                            />
                                        </svg>
                                        <span className="text-xs md:text-sm">
                                            Top Up
                                        </span>
                                    </div>
                                </Link>

                                <Link
                                    href="/invoice"
                                    className={`group relative flex cursor-pointer items-center py-2 text-nowrap transition md:py-3 ${isActive('/invoice') ? 'text-client-warning' : 'text-white hover:text-gray-200'}`}
                                >
                                    <div className="flex items-center justify-between gap-2 md:gap-2.5">
                                        <svg
                                            width="16"
                                            height="16"
                                            viewBox="0 0 18 18"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                            className="stroke-white md:h-[18px] md:w-[18px]"
                                        >
                                            <path
                                                d="M15.012 1.5C14.1773 1.5 13.5 3.5145 13.5 6H15.012C15.741 6 16.1048 6 16.3305 5.74875C16.5555 5.49675 16.5165 5.16525 16.4385 4.503C16.23 2.7525 15.6705 1.5 15.012 1.5Z"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            />
                                            <path
                                                d="M13.5 6.0405V13.9845C13.5 15.1177 13.5 15.6848 13.1535 15.9083C12.5873 16.2728 11.712 15.5077 11.2718 15.2302C10.908 15.0007 10.7265 14.8868 10.5247 14.88C10.3065 14.8725 10.1212 14.9827 9.72825 15.2302L8.295 16.134C7.908 16.3777 7.71525 16.5 7.5 16.5C7.28475 16.5 7.09125 16.3777 6.705 16.134L5.2725 15.2302C4.908 15.0007 4.7265 14.8868 4.52475 14.88C4.3065 14.8725 4.12125 14.9827 3.72825 15.2302C3.288 15.5077 2.41275 16.2728 1.84575 15.9083C1.5 15.6848 1.5 15.1185 1.5 13.9845V6.0405C1.5 3.9 1.5 2.8305 2.15925 2.16525C2.81775 1.5 3.879 1.5 6 1.5H15"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            />
                                            <path
                                                d="M7.5 6C6.67125 6 6 6.504 6 7.125C6 7.746 6.67125 8.25 7.5 8.25C8.32875 8.25 9 8.754 9 9.375C9 9.996 8.32875 10.5 7.5 10.5M7.5 6C8.1525 6 8.709 6.31275 8.9145 6.75M7.5 6V5.25M7.5 10.5C6.8475 10.5 6.291 10.1873 6.0855 9.75M7.5 10.5V11.25"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            />
                                        </svg>
                                        <span className="text-xs md:text-sm">
                                            Cek Invoice
                                        </span>
                                    </div>
                                </Link>

                                <Link
                                    href="/blog"
                                    className={`group relative flex cursor-pointer items-center py-2 text-nowrap transition md:py-3 ${isActive('/blog') ? 'text-client-warning' : 'text-white hover:text-gray-200'}`}
                                >
                                    <div className="flex items-center justify-between gap-2 md:gap-2.5">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="md:h-4.5 md:w-4.5">
                                            <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2" />
                                            <path d="M18 14h-8" /><path d="M15 18h-5" /><path d="M10 6h8v4h-8V6Z" />
                                        </svg>
                                        <span className="text-xs md:text-sm">Blog</span>
                                    </div>
                                </Link>

                                <Link
                                    href="/price-list"
                                    className={`group relative flex cursor-pointer items-center py-2 text-nowrap transition md:py-3 ${isActive('/price-list') ? 'text-client-warning' : 'text-white hover:text-gray-200'}`}
                                >
                                    <div className="flex items-center justify-between gap-2 md:gap-2.5">
                                        <svg
                                            width="16"
                                            height="16"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            className="md:h-4.5 md:w-4.5"
                                        >
                                            <line x1="8" y1="6" x2="21" y2="6" />
                                            <line x1="8" y1="12" x2="21" y2="12" />
                                            <line x1="8" y1="18" x2="21" y2="18" />
                                            <line x1="3" y1="6" x2="3.01" y2="6" />
                                            <line x1="3" y1="12" x2="3.01" y2="12" />
                                            <line x1="3" y1="18" x2="3.01" y2="18" />
                                        </svg>
                                        <span className="text-xs md:text-sm">
                                            Daftar Harga
                                        </span>
                                    </div>
                                </Link>
                            </div>

                            {/* Divider — desktop only */}
                            {/* Divider — desktop only */}
                            {hasBroadcast && (
                                <div className="hidden md:block">
                                    <svg
                                        width="1"
                                        height="30"
                                        viewBox="0 0 1 30"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        className="fill-white/30"
                                    >
                                        <path
                                            fillRule="evenodd"
                                            clipRule="evenodd"
                                            d="M0.5 0C0.367392 0 0.240214 0.0831764 0.146446 0.231231C0.052678 0.379286 0 0.580092 0 0.789474V29.2105C0 29.4199 0.052678 29.6207 0.146446 29.7688C0.240214 29.9168 0.367392 30 0.5 30C0.632608 30 0.759786 29.9168 0.853554 29.7688C0.947322 29.6207 1 29.4199 1 29.2105V0.789474C1 0.580092 0.947322 0.379286 0.853554 0.231231C0.759786 0.0831764 0.632608 0 0.5 0Z"
                                        />
                                    </svg>
                                </div>
                            )}

                            {/* Ticker */}
                            {hasBroadcast && (
                                <div className="relative w-full flex-grow overflow-hidden py-2 md:py-0">
                                    <NewsTicker
                                        messages={broadcastMessages}
                                        speed={30}
                                        separator="◈"
                                    />
                                </div>
                            )}
                        </div>
                    </div>
                </header>

                {/* Main Content — extra bottom padding on mobile to clear the bottom nav */}
                <main className="w-full flex-1 bg-background pb-20 md:pb-0">
                    {children}
                </main>

                {/* ===== Mobile Bottom Navigation Bar ===== */}
                {/* Only visible on screens smaller than md (768px). 
                Provides thumb-friendly access to: Beranda, Cek Invoice, Akun/Masuk */}
                <nav
                    className="fixed inset-x-0 bottom-0 z-50 flex items-center justify-around border-t border-[#31334c] bg-[#1e1f29]/95 px-2 py-2 backdrop-blur-md md:hidden"
                    style={{
                        paddingBottom:
                            'max(0.5rem, env(safe-area-inset-bottom))',
                    }}
                >
                    <Link
                        href="/"
                        className={`relative flex flex-col items-center gap-0.5 px-3 py-1 transition ${isActive('/') ? 'text-primary' : 'text-gray-400 hover:text-gray-200'}`}
                    >
                        {/* Active indicator bar */}
                        {isActive('/') && (
                            <span className="absolute -top-2 left-1/2 h-[3px] w-6 -translate-x-1/2 rounded-b bg-primary"></span>
                        )}
                        <svg
                            width="22"
                            height="22"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span className="text-xs font-semibold">
                            Beranda
                        </span>
                    </Link>

                    <Link
                        href="/invoice"
                        className={`relative flex flex-col items-center gap-0.5 px-3 py-1 transition ${isActive('/invoice') ? 'text-primary' : 'text-gray-400 hover:text-gray-200'}`}
                    >
                        {isActive('/invoice') && (
                            <span className="absolute -top-2 left-1/2 h-[3px] w-6 -translate-x-1/2 rounded-b bg-primary"></span>
                        )}
                        <svg
                            width="22"
                            height="22"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <span className="text-xs font-medium">
                            Cek Invoice
                        </span>
                    </Link>

                    <Link
                        href="/price-list"
                        className={`relative flex flex-col items-center gap-0.5 px-3 py-1 transition ${isActive('/price-list') ? 'text-primary' : 'text-gray-400 hover:text-gray-200'}`}
                    >
                        {isActive('/price-list') && (
                            <span className="absolute -top-2 left-1/2 h-0.75 w-6 -translate-x-1/2 rounded-b bg-primary"></span>
                        )}
                        <svg
                            width="22"
                            height="22"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <line x1="8" y1="6" x2="21" y2="6" />
                            <line x1="8" y1="12" x2="21" y2="12" />
                            <line x1="8" y1="18" x2="21" y2="18" />
                            <line x1="3" y1="6" x2="3.01" y2="6" />
                            <line x1="3" y1="12" x2="3.01" y2="12" />
                            <line x1="3" y1="18" x2="3.01" y2="18" />
                        </svg>
                        <span className="text-xs font-medium">
                            Harga
                        </span>
                    </Link>

                    {/* Akun — only shown when user is logged in. Links to dashboard. */}
                    {auth?.user && (
                        <Link
                            href="/dashboard"
                            className={`relative flex flex-col items-center gap-0.5 px-3 py-1 transition ${isActive('/dashboard') ? 'text-primary' : 'text-gray-400 hover:text-gray-200'}`}
                        >
                            {isActive('/dashboard') && (
                                <span className="absolute -top-2 left-1/2 h-[3px] w-6 -translate-x-1/2 rounded-b bg-primary"></span>
                            )}
                            <svg
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span className="text-xs font-medium">
                                Akun
                            </span>
                        </Link>
                    )}
                </nav>

                {/* Footer — responsive grid: 2 columns on mobile, 4 on desktop */}
                <footer className="mt-10 border-t border-[#31334c] bg-[#1e1f29] px-4 pt-8 pb-24 md:mt-16 md:pt-12 md:pb-6">
                    <div className="mx-auto mb-8 grid max-w-7xl grid-cols-2 gap-6 px-4 md:grid-cols-4 md:gap-8">
                        {/* Logo & Description — spans full width on mobile */}
                        <div className="col-span-2 md:col-span-1">
                            <Link
                                href="/"
                                className="mb-3 flex items-center md:mb-4"
                            >
                                {webSetting?.logo ? (
                                    <img src={webSetting.logo} alt="Nuvelo" className="h-7 w-auto md:h-9" />
                                ) : (
                                    <picture>
                                        <source srcSet="/logo-2x.webp" type="image/webp" />
                                        <img src="/logo.png" alt="Nuvelo" className="h-7 w-auto md:h-9" width="280" height="96" />
                                    </picture>
                                )}
                            </Link>
                            <p className="text-xs leading-relaxed text-gray-400 md:text-sm">
                                <strong className="text-[#c084fc]">Nuvelo</strong>{' '}
                                menyediakan layanan top up game termurah.
                                Dapatkan voucher untuk berbagai game populer,
                                termasuk top up ml dan top up ff, dengan proses
                                yang cepat dan aman.
                            </p>
                        </div>

                        {/* Navigasi */}
                        <div>
                            <h3 className="mb-3 text-xs font-bold text-primary text-white md:mb-4 md:text-base">
                                Navigasi
                            </h3>
                            <ul className="space-y-2 text-xs text-gray-400 md:space-y-3 md:text-sm">
                                <li>
                                    <Link
                                        href="/register"
                                        className="transition hover:text-primary"
                                    >
                                        Daftar
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href="/login"
                                        className="transition hover:text-primary"
                                    >
                                        Masuk
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href="/"
                                        className="transition hover:text-primary"
                                    >
                                        Top Up
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href="/invoice"
                                        className="transition hover:text-primary"
                                    >
                                        Cek Invoice
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href="/price-list"
                                        className="transition hover:text-primary"
                                    >
                                        Daftar Harga
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href="/blog"
                                        className="transition hover:text-primary"
                                    >
                                        Blog
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href="/api-docs"
                                        className="transition hover:text-primary"
                                    >
                                        API Docs
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        {/* Kontak */}
                        <div>
                            <h3 className="mb-3 text-xs font-bold text-primary text-white md:mb-4 md:text-base">
                                Kontak
                            </h3>
                            <ul className="space-y-2 text-xs text-gray-400 md:space-y-3 md:text-sm">
                                {webSetting?.waBubble?.number && (
                                    <li>
                                        <a
                                            href={`https://wa.me/${webSetting.waBubble.number}`}
                                            target="_blank"
                                            className="transition hover:text-primary"
                                        >
                                            WhatsApp
                                        </a>
                                    </li>
                                )}
                                {webSetting?.sosmed?.instagram && (
                                    <li>
                                        <a
                                            href={webSetting.sosmed.instagram}
                                            target="_blank"
                                            className="transition hover:text-primary"
                                        >
                                            Instagram
                                        </a>
                                    </li>
                                )}
                            </ul>
                        </div>

                        {/* Sosial Media */}
                        <div className="col-span-2 md:col-span-1">
                            <h3 className="mb-3 text-xs font-bold text-primary text-white md:mb-4 md:text-base">
                                Sosial Media
                            </h3>
                            <div className="flex gap-4">
                                {webSetting?.sosmed?.instagram && (
                                    <a
                                        href={webSetting.sosmed.instagram}
                                        target="_blank"
                                        aria-label="Ikuti kami di Instagram"
                                        className="flex h-8 w-8 items-center justify-center rounded-full bg-[#26273b] text-gray-300 transition hover:bg-primary hover:text-white"
                                    >
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
                                        >
                                            <rect
                                                x="2"
                                                y="2"
                                                width="20"
                                                height="20"
                                                rx="5"
                                                ry="5"
                                            />
                                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                            <line
                                                x1="17.5"
                                                y1="6.5"
                                                x2="17.51"
                                                y2="6.5"
                                            />
                                        </svg>
                                    </a>
                                )}
                                {webSetting?.sosmed?.tiktok && (
                                    <a
                                        href={webSetting.sosmed.tiktok}
                                        target="_blank"
                                        aria-label="Ikuti kami di TikTok"
                                        className="flex h-8 w-8 items-center justify-center rounded-full bg-[#26273b] text-gray-300 transition hover:bg-primary hover:text-white"
                                    >
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
                                        >
                                            <path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5" />
                                        </svg>
                                    </a>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Footer Bottom — stacked on mobile, side-by-side on desktop */}
                    <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 border-t border-[#31334c] px-4 pt-4 text-xs text-gray-300 md:flex-row md:gap-0 md:pt-6">
                        <p>&copy; 2026 Nuvelo. All rights reserved.</p>
                        <div className="mt-2 flex gap-4 md:mt-0">
                            {webSetting?.footerLinks && webSetting.footerLinks.length > 0 ? (
                                webSetting.footerLinks.map((link: any, i: number) => (
                                    <Link key={i} href={link.url} className="transition hover:text-white">
                                        {link.label}
                                    </Link>
                                ))
                            ) : (
                                <>
                                    <Link href="/kebijakan-privasi" className="transition hover:text-white">
                                        Kebijakan Privasi
                                    </Link>
                                    <Link href="/syarat-ketentuan" className="transition hover:text-white">
                                        Syarat & Ketentuan
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </footer>
            </div>

            <Toaster
                position="bottom-right"
                theme="dark"
                toastOptions={{
                    style: {
                        background: '#1e1f29',
                        border: '1px solid #31334c',
                        color: '#e5e7eb',
                        zIndex: 9999,
                    },
                }}
            />

            {webSetting?.waBubble?.enabled && webSetting?.waBubble?.number && (
                <a
                    href={`https://wa.me/${webSetting.waBubble.number}?text=${encodeURIComponent(webSetting.waBubble.message || '')}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="fixed bottom-[138px] right-4 z-[290] flex h-13 w-13 items-center justify-center rounded-full bg-[#25D366] text-white shadow-[0_0_20px_rgba(37,211,102,0.4)] transition hover:scale-105 hover:bg-[#20bd5a] md:bottom-[88px] md:right-6"
                    aria-label="Hubungi kami via WhatsApp"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        className="h-7 w-7"
                    >
                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-3.825 3.113-6.937 6.937-6.937 3.825 0 6.938 3.112 6.938 6.937-.001 3.825-3.113 6.938-6.938 6.938z" />
                    </svg>
                </a>
            )}

            <Suspense fallback={null}>
                <LiveChat context={chatContext} />
            </Suspense>
        </>
    );
}
