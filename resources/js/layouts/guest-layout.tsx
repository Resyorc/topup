import SearchBar from '@/components/search-bar';
import { Link, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    Clock3,
    Gamepad2,
    LockKeyhole,
    MessageCircle,
    ReceiptText,
    Tags,
} from 'lucide-react';
import { useState, useMemo, lazy, Suspense } from 'react';
import { Toaster } from 'sonner';
import NewsTicker from '@/components/news-ticker';

// Lazy LiveChat
const LiveChat = lazy(() => import('@/components/live-chat'));

type FooterPaymentChannel = {
    code: string;
    name: string;
    group?: string;
    icon_url?: string | null;
};

/** Pilih ikon SVG berdasarkan nama platform */
function SosmedIcon({ platform }: { platform: string }) {
    const p = platform?.toLowerCase();
    if (p === 'instagram')
        return (
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
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
            </svg>
        );
    if (p === 'tiktok')
        return (
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
        );
    if (p === 'youtube')
        return (
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
                <path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z" />
                <polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" />
            </svg>
        );
    if (p === 'twitter' || p === 'x')
        return (
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
                <path d="M4 4l16 16M4 20L20 4" />
            </svg>
        );
    if (p === 'facebook')
        return (
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
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
            </svg>
        );
    if (p === 'whatsapp')
        return (
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
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
            </svg>
        );
    // fallback — link icon
    return (
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
            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
        </svg>
    );
}

export default function GuestLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const {
        auth,
        broadcastMessages,
        webSetting,
        footerPaymentChannels = [],
    } = usePage().props as any;
    const sosmedLinks: Array<{ platform: string; label: string; url: string }> =
        (webSetting as any)?.sosmedLinks ?? [];
    const tripayPaymentChannels: FooterPaymentChannel[] = Array.isArray(
        footerPaymentChannels,
    )
        ? footerPaymentChannels
        : [];
    const paymentLogoTrack = [
        ...tripayPaymentChannels,
        ...tripayPaymentChannels,
    ];
    const currentUrl = usePage().url;
    const hasBroadcast = broadcastMessages && broadcastMessages.length > 0;
    const waNumber = webSetting?.waBubble?.number;
    const waLink = waNumber ? `https://wa.me/${waNumber}` : null;
    const currentYear = new Date().getFullYear();


    const footerNavigation = [
        { href: '/', label: 'Top Up Game', icon: Gamepad2 },
        { href: '/invoice', label: 'Cek Invoice', icon: ReceiptText },
        { href: '/blog', label: 'Blog', icon: Tags },
    ];

    const footerTrust = [
        {
            label: 'Pembayaran fleksibel',
            detail: 'Mengikuti channel aktif dari Tripay',
        },
        { label: 'Status transparan', detail: 'Cek invoice kapan saja' },
        {
            label: 'Privasi terjaga',
            detail: 'Data dipakai hanya untuk transaksi',
        },
    ];

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
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    // Helper: check if a nav link is currently active
    const isActive = (href: string) => {
        if (href === '/') return currentUrl === '/';
        return currentUrl.startsWith(href);
    };

    return (
        <>
            <div className="relative isolate flex min-h-screen flex-col pt-[116px] md:pt-[106px]">
                {/* Cyber background layer */}
                <div aria-hidden="true" className="nuvelo-cyber-bg">
                    <svg
                        className="nuvelo-cyber-lines"
                        viewBox="0 0 1440 900"
                        preserveAspectRatio="xMidYMid slice"
                        xmlns="http://www.w3.org/2000/svg"
                    >

                        {/* Garis dasar — static */}
                        <g className="line-base">
                            <path d="M -10 150 H 200 V 80  H 460 V 200 H 620" />
                            <path d="M 1450 110 H 1210 V 210 H 1040 V 130 H 860" />
                            <path d="M -10 430 H 160 V 350 H 400 V 490 H 580 V 380 H 740" />
                            <path d="M 1450 490 H 1270 V 390 H 1070 V 510 H 890 V 420" />
                            <path d="M 180 910 V 720 H 390 V 800 H 610 V 670 H 790" />
                            <path d="M 1220 910 V 760 H 1010 V 850 H 830 V 710" />
                        </g>

                        {/* Cahaya berjalan — sama path, animasi berbeda */}
                        <g className="line-light">
                            <path d="M -10 150 H 200 V 80  H 460 V 200 H 620" />
                            <path d="M 1450 110 H 1210 V 210 H 1040 V 130 H 860" />
                            <path d="M -10 430 H 160 V 350 H 400 V 490 H 580 V 380 H 740" />
                            <path d="M 1450 490 H 1270 V 390 H 1070 V 510 H 890 V 420" />
                            <path d="M 180 910 V 720 H 390 V 800 H 610 V 670 H 790" />
                            <path d="M 1220 910 V 760 H 1010 V 850 H 830 V 710" />
                        </g>

                        {/* Streak diagonal */}
                        <g className="line-streak">
                            <path d="M -80 -80 L 520 640" />
                            <path d="M 720 -80 L 1360 620" />
                            <path d="M 1620 180 L 880 910" />
                        </g>
                    </svg>
                </div>
                {/* Header Navbar */}
                <header className="border-border-light fixed inset-x-0 top-0 z-50 border-b bg-background/80 text-foreground shadow-md backdrop-blur-sm">
                    <div className="mx-auto flex w-full max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 md:gap-10 md:py-4 lg:px-8">
                        {/* Logo — cropped image with no padding, sits naturally at left */}
                        <Link
                            href="/"
                            className="flex shrink-0 cursor-pointer items-center focus:outline-0"
                        >
                            {webSetting?.logo ? (
                                <img
                                    src={webSetting.logo}
                                    alt="Nuvelo"
                                    className="h-10 w-auto md:h-12"
                                />
                            ) : (
                                <picture>
                                    <source
                                        srcSet="/logo-2x.webp"
                                        type="image/webp"
                                    />
                                    <img
                                        src="/logo.png"
                                        alt="Nuvelo"
                                        className="h-10 w-auto md:h-12"
                                        width="280"
                                        height="96"
                                    />
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

                            {/* Mobile-only: hamburger menu toggle */}
                            <button
                                onClick={() => setMobileMenuOpen(true)}
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-500/50 bg-white/10 transition hover:bg-white/20 md:hidden"
                                aria-label="Buka menu navigasi"
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
                                    <line x1="3" y1="12" x2="21" y2="12"></line>
                                    <line x1="3" y1="6" x2="21" y2="6"></line>
                                    <line x1="3" y1="18" x2="21" y2="18"></line>
                                </svg>
                            </button>

                            {auth?.user ? (
                                <div className="flex items-center gap-2 md:gap-3">
                                    <Link
                                        href="/dashboard"
                                        className={`hidden h-10 w-10 items-center justify-center overflow-hidden rounded-full border-2 transition md:flex ${isActive('/dashboard') ? 'border-primary bg-primary/20 text-white shadow-[0_0_12px_rgba(131,39,216,0.35)]' : 'border-primary/40 bg-white/10 text-gray-300 hover:bg-white/20'}`}
                                        aria-label="Buka dashboard"
                                    >
                                        {auth?.user?.avatar_url ? (
                                            <img
                                                src={auth.user.avatar_url}
                                                alt="Profile"
                                                className="h-full w-full object-cover"
                                            />
                                        ) : (
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
                                        )}
                                    </Link>
                                </div>
                            ) : (
                                <div className="flex items-center gap-2 md:gap-3">
                                    {/* Masuk button — desktop only (since it's inside mobile drawer now) */}
                                    <Link
                                        href="/login"
                                        className="hidden rounded-md border border-white/70 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/20 md:inline-block md:px-4 md:py-2 md:text-sm"
                                    >
                                        Masuk
                                    </Link>
                                    {/* Daftar button — desktop only (unchanged) */}
                                    <Link
                                        href="/register"
                                        className="hidden rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-[var(--shadow-glow)] transition hover:hue-rotate-15 md:inline-block"
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
                    <div className="w-full border-t border-b border-primary/30 bg-primary/40 text-sm font-medium shadow-inner backdrop-blur-sm select-none">
                        <div className="mx-auto flex w-full max-w-7xl items-center gap-6 px-4 md:px-8">
                            {/* Static Links — hidden on mobile (bottom nav handles navigation), visible on desktop */}
                            <div className="hidden shrink-0 items-center gap-8 md:flex">
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
                                            <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2" />
                                            <path d="M18 14h-8" />
                                            <path d="M15 18h-5" />
                                            <path d="M10 6h8v4h-8V6Z" />
                                        </svg>
                                        <span className="text-xs md:text-sm">
                                            Blog
                                        </span>
                                    </div>
                                </Link>
                            </div>

                            {/* Divider — desktop only */}
                            {/* Divider — desktop only */}
                            {hasBroadcast && (
                                <div className="hidden h-8 w-px shrink-0 bg-white/15 md:block" />
                            )}

                            {/* Ticker */}
                            {hasBroadcast && (
                                <div className="hidden min-w-0 flex-1 overflow-hidden md:block">
                                    <NewsTicker
                                        messages={broadcastMessages}
                                        speed={30}
                                        separator="•"
                                    />
                                </div>
                            )}
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <main className="relative z-10 w-full flex-1 bg-transparent">
                    {children}
                </main>

                {/* ===== Mobile Overlay Menu Drawer ===== */}
                <div
                    className={`fixed inset-0 z-[60] bg-black/60 backdrop-blur-sm transition-opacity duration-300 md:hidden ${mobileMenuOpen ? 'pointer-events-auto opacity-100' : 'pointer-events-none opacity-0'}`}
                    onClick={() => setMobileMenuOpen(false)}
                >
                    <div
                        className={`border-border-light absolute top-0 right-0 flex h-full w-3/4 max-w-sm flex-col border-l bg-card shadow-2xl transition-transform duration-300 ${mobileMenuOpen ? 'translate-x-0' : 'translate-x-full'}`}
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="border-border-light flex items-center justify-between border-b p-4">
                            <span className="text-lg font-bold text-foreground">
                                Menu Navigasi
                            </span>
                            <button
                                onClick={() => setMobileMenuOpen(false)}
                                className="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-gray-300 transition hover:bg-white/20"
                                aria-label="Tutup menu"
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
                                >
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>

                        <div className="flex flex-1 flex-col space-y-2 overflow-y-auto p-4">
                            <Link
                                href="/"
                                onClick={() => setMobileMenuOpen(false)}
                                className={`flex items-center gap-3 rounded-xl px-4 py-3 font-semibold transition ${isActive('/') ? 'border border-primary/30 bg-primary/20 text-primary' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}
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
                                >
                                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                </svg>
                                Top Up
                            </Link>

                            <Link
                                href="/invoice"
                                onClick={() => setMobileMenuOpen(false)}
                                className={`flex items-center gap-3 rounded-xl px-4 py-3 font-semibold transition ${isActive('/invoice') ? 'border border-primary/30 bg-primary/20 text-primary' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}
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
                                >
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line
                                        x1="21"
                                        y1="21"
                                        x2="16.65"
                                        y2="16.65"
                                    ></line>
                                </svg>
                                Cek Invoice
                            </Link>

                            <Link
                                href="/blog"
                                onClick={() => setMobileMenuOpen(false)}
                                className={`flex items-center gap-3 rounded-xl px-4 py-3 font-semibold transition ${isActive('/blog') ? 'border border-primary/30 bg-primary/20 text-primary' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}
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
                                >
                                    <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2" />
                                    <path d="M18 14h-8" />
                                    <path d="M15 18h-5" />
                                    <path d="M10 6h8v4h-8V6Z" />
                                </svg>
                                Blog
                            </Link>

                            <div className="bg-border-light my-2 h-px w-full"></div>

                            {auth?.user ? (
                                <>
                                    <Link
                                        href="/dashboard"
                                        onClick={() => setMobileMenuOpen(false)}
                                        className={`flex items-center gap-3 rounded-xl px-4 py-3 font-semibold transition ${currentUrl === '/dashboard' ? 'border border-primary/30 bg-primary/20 text-primary' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}
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
                                        >
                                            <rect
                                                width="7"
                                                height="9"
                                                x="3"
                                                y="3"
                                                rx="1"
                                            />
                                            <rect
                                                width="7"
                                                height="5"
                                                x="14"
                                                y="3"
                                                rx="1"
                                            />
                                            <rect
                                                width="7"
                                                height="9"
                                                x="14"
                                                y="12"
                                                rx="1"
                                            />
                                            <rect
                                                width="7"
                                                height="5"
                                                x="3"
                                                y="16"
                                                rx="1"
                                            />
                                        </svg>
                                        Dashboard
                                    </Link>

                                    <Link
                                        href="/dashboard/transactions"
                                        onClick={() => setMobileMenuOpen(false)}
                                        className={`flex items-center gap-3 rounded-xl px-4 py-3 font-semibold transition ${currentUrl.includes('/dashboard/transactions') ? 'border border-primary/30 bg-primary/20 text-primary' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}
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
                                        >
                                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                            <path d="M3 6h18" />
                                            <path d="M16 10a4 4 0 0 1-8 0" />
                                        </svg>
                                        Transaksi
                                    </Link>

                                    <Link
                                        href="/dashboard/coin-history"
                                        onClick={() => setMobileMenuOpen(false)}
                                        className={`flex items-center gap-3 rounded-xl px-4 py-3 font-semibold transition ${currentUrl.includes('/dashboard/coin-history') ? 'border border-primary/30 bg-primary/20 text-primary' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}
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
                                        >
                                            <circle cx="12" cy="12" r="8" />
                                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                                            <path d="M12 17h.01" />
                                        </svg>
                                        Riwayat Coin
                                    </Link>

                                    <Link
                                        href="/dashboard/settings"
                                        onClick={() => setMobileMenuOpen(false)}
                                        className={`flex items-center gap-3 rounded-xl px-4 py-3 font-semibold transition ${currentUrl.includes('/dashboard/settings') ? 'border border-primary/30 bg-primary/20 text-primary' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}
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
                                        >
                                            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        Pengaturan
                                    </Link>

                                    {auth?.user?.api_access_enabled && (
                                        <Link
                                            href="/dashboard/api-credentials"
                                            onClick={() =>
                                                setMobileMenuOpen(false)
                                            }
                                            className={`flex items-center gap-3 rounded-xl px-4 py-3 font-semibold transition ${currentUrl.includes('/dashboard/api-credentials') ? 'border border-primary/30 bg-primary/20 text-primary' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}
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
                                            >
                                                <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4" />
                                            </svg>
                                            API Credentials
                                        </Link>
                                    )}

                                    <Link
                                        href="/logout"
                                        method="post"
                                        as="button"
                                        onClick={() => setMobileMenuOpen(false)}
                                        className="mt-2 flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-red-400 transition hover:bg-red-500/10 hover:text-red-300"
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
                                        >
                                            <path d="m16 17 5-5-5-5" />
                                            <path d="M21 12H9" />
                                            <path d="M12 19H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h7" />
                                        </svg>
                                        Log out
                                    </Link>
                                </>
                            ) : (
                                <div className="mt-2 flex flex-col gap-2">
                                    <Link
                                        href="/login"
                                        onClick={() => setMobileMenuOpen(false)}
                                        className="w-full rounded-xl border border-white/70 bg-white/5 px-4 py-3 text-center font-bold text-white transition hover:bg-white/10"
                                    >
                                        Masuk
                                    </Link>
                                    <Link
                                        href="/register"
                                        onClick={() => setMobileMenuOpen(false)}
                                        className="w-full rounded-xl bg-gradient-to-r from-primary to-[var(--color-primary-light)] px-4 py-3 text-center font-bold text-white shadow-[var(--shadow-glow)] transition hover:opacity-90"
                                    >
                                        Daftar Akun Baru
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Footer — responsive grid: 2 columns on mobile, 4 on desktop */}
                <footer className="border-border-light relative z-10 mt-10 border-t bg-[#1F2937]/85 px-4 pt-6 pb-10 backdrop-blur-sm md:mt-16 md:pt-10 md:pb-6">
                    <div className="mx-auto max-w-7xl px-4">

                        <div className="grid gap-8 border-y border-white/10 py-8 md:grid-cols-[1.25fr_0.8fr_0.9fr_0.9fr] md:gap-10">
                            <div>
                                <Link
                                    href="/"
                                    className="mb-4 inline-flex items-center"
                                >
                                    {webSetting?.logo ? (
                                        <img
                                            src={webSetting.logo}
                                            alt="Nuvelo"
                                            className="h-8 w-auto md:h-10"
                                        />
                                    ) : (
                                        <picture>
                                            <source
                                                srcSet="/logo-2x.webp"
                                                type="image/webp"
                                            />
                                            <img
                                                src="/logo.png"
                                                alt="Nuvelo"
                                                className="h-8 w-auto md:h-10"
                                                width="280"
                                                height="96"
                                            />
                                        </picture>
                                    )}
                                </Link>
                                <p className="max-w-sm text-sm leading-6 text-gray-400">
                                    Platform top up game dan voucher digital
                                    untuk pemain Indonesia. Pilih produk, bayar
                                    dengan metode favorit, lalu pantau pesanan
                                    lewat invoice.
                                </p>
                                <div className="mt-5 flex flex-wrap gap-2">
                                    <span className="inline-flex items-center gap-2 rounded-md border border-emerald-400/20 bg-emerald-400/10 px-3 py-2 text-xs font-semibold text-emerald-200">
                                        <Clock3 className="h-3.5 w-3.5" />
                                        24/7 Online
                                    </span>
                                    <span className="inline-flex items-center gap-2 rounded-md border border-cyan-400/20 bg-cyan-400/10 px-3 py-2 text-xs font-semibold text-cyan-200">
                                        <LockKeyhole className="h-3.5 w-3.5" />
                                        Transaksi Aman
                                    </span>
                                </div>
                            </div>

                            <div>
                                <h3 className="mb-4 text-sm font-bold text-white">
                                    Jelajah
                                </h3>
                                <ul className="space-y-2.5 text-sm text-gray-400">
                                    {footerNavigation.map(
                                        ({ href, label, icon: Icon }) => (
                                            <li key={href}>
                                                <Link
                                                    href={href}
                                                    className="group inline-flex items-center gap-2 transition hover:text-white"
                                                >
                                                    <Icon className="h-4 w-4 text-gray-500 transition group-hover:text-[var(--color-primary-light)]" />
                                                    {label}
                                                </Link>
                                            </li>
                                        ),
                                    )}
                                </ul>
                            </div>

                            <div>
                                <h3 className="mb-4 text-sm font-bold text-white">
                                    Kepercayaan
                                </h3>
                                <ul className="space-y-3">
                                    {footerTrust.map((item) => (
                                        <li key={item.label}>
                                            <p className="text-sm font-semibold text-gray-200">
                                                {item.label}
                                            </p>
                                            <p className="mt-0.5 text-xs leading-relaxed text-gray-500">
                                                {item.detail}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div>
                                <h3 className="mb-4 text-sm font-bold text-white">
                                    Hubungi Kami
                                </h3>
                                <div className="space-y-3">
                                    {waLink && (
                                        <a
                                            href={waLink}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex w-full items-center justify-between rounded-lg border border-[#25D366]/25 bg-[#25D366]/10 px-4 py-3 text-sm font-bold text-[#9ff0bd] transition hover:border-[#25D366]/50 hover:bg-[#25D366]/15"
                                        >
                                            <span className="inline-flex items-center gap-2">
                                                <MessageCircle className="h-4 w-4" />
                                                WhatsApp Support
                                            </span>
                                            <ArrowUpRight className="h-4 w-4" />
                                        </a>
                                    )}

                                    {sosmedLinks.length > 0 && (
                                        <div>
                                            <p className="mb-3 text-xs font-semibold text-gray-500 uppercase">
                                                Sosial Media
                                            </p>
                                            <div className="flex flex-wrap gap-2">
                                                {sosmedLinks.map((item, i) => (
                                                    <a
                                                        key={i}
                                                        href={item.url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        aria-label={item.label}
                                                        title={item.label}
                                                        className="flex h-9 w-9 items-center justify-center rounded-md border border-white/10 bg-white/[0.04] text-gray-300 transition hover:border-primary/50 hover:bg-primary/20 hover:text-white"
                                                    >
                                                        <SosmedIcon
                                                            platform={
                                                                item.platform
                                                            }
                                                        />
                                                    </a>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {tripayPaymentChannels.length > 0 && (
                            <div className="border-b border-white/10 py-5">
                                <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                                    <p className="text-xs font-semibold text-gray-500 uppercase">
                                        Metode Pembayaran
                                    </p>
                                    <span className="rounded bg-white/10 px-2 py-1 text-[11px] font-semibold text-gray-400">
                                        {tripayPaymentChannels.length} channel
                                        aktif
                                    </span>
                                </div>
                                <div className="relative overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_10%,black_90%,transparent)]">
                                    <div className="flex w-max animate-marquee gap-3 [animation-duration:42s] hover:[animation-play-state:paused]">
                                        {paymentLogoTrack.map(
                                            (channel, index) => (
                                                <div
                                                    key={`${channel.code || channel.name}-${index}`}
                                                    title={channel.name}
                                                    className="flex h-13 min-w-28 shrink-0 items-center justify-center rounded-md border border-white/10 bg-white px-4 py-2 shadow-sm"
                                                >
                                                    {channel.icon_url ? (
                                                        <img
                                                            src={
                                                                channel.icon_url
                                                            }
                                                            alt={channel.name}
                                                            className="max-h-8 max-w-24 object-contain"
                                                            loading="lazy"
                                                        />
                                                    ) : (
                                                        <span className="max-w-24 truncate text-sm font-bold text-gray-900">
                                                            {channel.name}
                                                        </span>
                                                    )}
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="flex flex-col gap-4 pt-5 text-xs text-gray-500 md:flex-row md:items-center md:justify-between">
                            <p>
                                &copy; {currentYear} Nuvelo. All rights
                                reserved.
                            </p>
                            <div className="flex flex-wrap gap-x-5 gap-y-2">
                                <Link
                                    href="/kebijakan-privasi"
                                    className="transition hover:text-white"
                                >
                                    Kebijakan Privasi
                                </Link>
                                <Link
                                    href="/syarat-ketentuan"
                                    className="transition hover:text-white"
                                >
                                    Syarat & Ketentuan
                                </Link>
                            </div>
                        </div>
                    </div>
                </footer>

                {false && (
                    <footer className="hidden">
                        <div className="mx-auto mb-8 grid max-w-7xl grid-cols-2 gap-6 px-4 md:grid-cols-4 md:gap-8">
                            {/* Logo & Description — spans full width on mobile */}
                            <div className="col-span-2 md:col-span-1">
                                <Link
                                    href="/"
                                    className="mb-3 flex items-center md:mb-4"
                                >
                                    {webSetting?.logo ? (
                                        <img
                                            src={webSetting.logo}
                                            alt="Nuvelo"
                                            className="h-7 w-auto md:h-9"
                                        />
                                    ) : (
                                        <picture>
                                            <source
                                                srcSet="/logo-2x.webp"
                                                type="image/webp"
                                            />
                                            <img
                                                src="/logo.png"
                                                alt="Nuvelo"
                                                className="h-7 w-auto md:h-9"
                                                width="280"
                                                height="96"
                                            />
                                        </picture>
                                    )}
                                </Link>
                                <p className="text-xs leading-relaxed text-gray-400 md:text-sm">
                                    <strong className="text-[var(--color-primary-light)]">
                                        Nuvelo
                                    </strong>{' '}
                                    menyediakan layanan top up game termurah.
                                    Dapatkan voucher untuk berbagai game
                                    populer, termasuk top up ml dan top up ff,
                                    dengan proses yang cepat dan aman.
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
                                            href="/blog"
                                            className="transition hover:text-primary"
                                        >
                                            Blog
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
                                </ul>
                            </div>

                            {/* Sosial Media */}
                            {sosmedLinks.length > 0 && (
                                <div className="col-span-2 md:col-span-1">
                                    <h3 className="mb-3 text-xs font-bold text-white md:mb-4 md:text-base">
                                        Sosial Media
                                    </h3>
                                    <div className="flex flex-wrap gap-3">
                                        {sosmedLinks.map((item, i) => (
                                            <a
                                                key={i}
                                                href={item.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                aria-label={item.label}
                                                title={item.label}
                                                className="flex h-8 w-8 items-center justify-center rounded-full bg-secondary text-gray-300 transition hover:bg-primary hover:text-white"
                                            >
                                                <SosmedIcon
                                                    platform={item.platform}
                                                />
                                            </a>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Footer Bottom — stacked on mobile, side-by-side on desktop */}
                        <div className="border-border-light mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 border-t px-4 pt-4 text-xs text-gray-300 md:flex-row md:gap-0 md:pt-6">
                            <p>&copy; 2026 Nuvelo. All rights reserved.</p>
                            <div className="mt-2 flex gap-4 md:mt-0">
                                <Link
                                    href="/kebijakan-privasi"
                                    className="transition hover:text-white"
                                >
                                    Kebijakan Privasi
                                </Link>
                                <Link
                                    href="/syarat-ketentuan"
                                    className="transition hover:text-white"
                                >
                                    Syarat & Ketentuan
                                </Link>
                            </div>
                        </div>
                    </footer>
                )}
            </div>

            <Toaster
                position="bottom-right"
                theme="dark"
                toastOptions={{
                    style: {
                        background: 'var(--color-bg-card)',
                        border: '1px solid var(--color-border-light)',
                        color: 'var(--color-text-primary)',
                        zIndex: 9999,
                    },
                }}
            />

            {webSetting?.waBubble?.enabled && webSetting?.waBubble?.number && (
                <a
                    href={`https://wa.me/${webSetting.waBubble.number}?text=${encodeURIComponent(webSetting.waBubble.message || '')}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="fixed right-4 bottom-[88px] z-[290] flex h-13 w-13 items-center justify-center rounded-full bg-[#25D366] text-white shadow-[0_0_20px_rgba(37,211,102,0.4)] transition hover:scale-105 hover:bg-[#20bd5a] md:right-6"
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
