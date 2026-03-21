import SearchBar from '@/components/search-bar';
import { Link, usePage } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { useState, useRef, useEffect, useMemo } from 'react';
import { Toaster, toast } from 'sonner';
import LiveChat from '@/components/live-chat';
import NewsTicker from '@/components/news-ticker';

const STATUS_LABEL: Record<
    string,
    { label: string; type: 'success' | 'error' | 'info' }
> = {
    success: { label: 'berhasil diproses', type: 'success' },
    failed: { label: 'gagal diproses', type: 'error' },
    processing: { label: 'sedang diproses', type: 'info' },
    paid: { label: 'pembayaran diterima', type: 'info' },
};

type Notif = {
    invoice_id: string;
    product_name: string;
    status: string;
    time: string;
};

function TransactionNotifier({
    userId,
    onNotification,
}: {
    userId: number;
    onNotification: (data: any) => void;
}) {
    useEcho(`transactions.${userId}`, '.InvoiceStatusUpdated', onNotification);
    return null;
}

export default function GuestLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { auth, broadcastMessages } = usePage().props as any;
    const currentUrl = usePage().url;
    const tickerMsgs =
        broadcastMessages && broadcastMessages.length > 0
            ? broadcastMessages
            : [
                  '🔥 PROMO SPESIAL MINGGU INI!',
                  'TOP UP DI Nuvelo CEPAT & TERPERCAYA',
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
    const [notifOpen, setNotifOpen] = useState(false);
    const [notifications, setNotifications] = useState<Notif[]>([]);
    const notifRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (
                notifRef.current &&
                !notifRef.current.contains(e.target as Node)
            ) {
                setNotifOpen(false);
            }
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    const handleNotification = (data: any) => {
        const info = STATUS_LABEL[data.status];
        if (!info) return;
        setNotifications((prev) =>
            [
                {
                    invoice_id: data.invoice_id,
                    product_name: data.product_name,
                    status: data.status,
                    time: new Date().toLocaleTimeString('id-ID'),
                },
                ...prev,
            ].slice(0, 20),
        );
        toast[info.type](`${data.product_name} ${info.label}`, {
            description: `Invoice: ${data.invoice_id}`,
            duration: 6000,
        });
    };

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
                            <picture>
                                <source srcSet="/logo-2x.webp" type="image/webp" />
                                <img
                                    src="/logo.png"
                                    alt="Nuvelo"
                                    className="h-10 w-auto md:h-12"
                                    width="280"
                                    height="96"
                                />
                            </picture>
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
                                    {/* Notification Bell — desktop & mobile */}
                                    <div ref={notifRef} className="relative">
                                        <button
                                            onClick={() =>
                                                setNotifOpen(!notifOpen)
                                            }
                                            className="relative flex h-9 w-9 items-center justify-center rounded-full border border-gray-500/50 bg-white/10 transition hover:bg-white/20 md:h-10 md:w-10 md:border-2 md:border-primary/40"
                                            aria-label="Notifikasi"
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
                                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                            </svg>
                                            {notifications.length > 0 && (
                                                <span className="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                                                    {notifications.length > 9
                                                        ? '9+'
                                                        : notifications.length}
                                                </span>
                                            )}
                                        </button>

                                        {/* Dropdown panel */}
                                        {notifOpen && (
                                            <div className="absolute top-12 right-0 z-[200] w-80 rounded-xl border border-[#31334c] bg-[#1e1f29] shadow-xl">
                                                <div className="flex items-center justify-between border-b border-[#31334c] px-4 py-3">
                                                    <span className="text-sm font-semibold text-white">
                                                        Notifikasi
                                                    </span>
                                                    {notifications.length >
                                                        0 && (
                                                        <button
                                                            onClick={() =>
                                                                setNotifications(
                                                                    [],
                                                                )
                                                            }
                                                            className="text-xs text-gray-400 hover:text-white"
                                                        >
                                                            Hapus semua
                                                        </button>
                                                    )}
                                                </div>
                                                <div className="max-h-72 overflow-y-auto">
                                                    {notifications.length ===
                                                    0 ? (
                                                        <p className="py-6 text-center text-xs text-gray-300">
                                                            Tidak ada notifikasi
                                                        </p>
                                                    ) : (
                                                        notifications.map(
                                                            (n, i) => {
                                                                const info =
                                                                    STATUS_LABEL[
                                                                        n.status
                                                                    ];
                                                                return (
                                                                    <div
                                                                        key={i}
                                                                        className="border-b border-[#31334c]/50 px-4 py-3"
                                                                    >
                                                                        <div className="flex items-start gap-2">
                                                                            <span
                                                                                className={`mt-1.5 h-2 w-2 flex-shrink-0 rounded-full ${info?.type === 'success' ? 'bg-green-400' : info?.type === 'error' ? 'bg-red-400' : 'bg-blue-400'}`}
                                                                            />
                                                                            <div>
                                                                                <p className="text-sm text-white">
                                                                                    {
                                                                                        n.product_name
                                                                                    }{' '}
                                                                                    <span className="text-gray-400">
                                                                                        {
                                                                                            info?.label
                                                                                        }
                                                                                    </span>
                                                                                </p>
                                                                                <p className="text-xs text-gray-400">
                                                                                    {
                                                                                        n.invoice_id
                                                                                    }{' '}
                                                                                    ·{' '}
                                                                                    {
                                                                                        n.time
                                                                                    }
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                );
                                                            },
                                                        )
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>

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
                                    className={`group relative flex cursor-pointer items-center py-2 text-nowrap transition md:py-3 ${isActive('/') ? 'text-primary' : 'text-white hover:text-gray-200'}`}
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
                                    className={`group relative flex cursor-pointer items-center py-2 text-nowrap transition md:py-3 ${isActive('/invoice') ? 'text-primary' : 'text-white hover:text-gray-200'}`}
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
                            </div>

                            {/* Divider — desktop only */}
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

                            {/* Ticker */}
                            <div className="relative w-full flex-grow overflow-hidden py-2 md:py-0">
                                <NewsTicker
                                    messages={tickerMsgs}
                                    speed={30}
                                    separator="◈"
                                />
                            </div>
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
                        className={`relative flex flex-col items-center gap-0.5 px-3 py-1 transition ${isActive('/') ? 'text-primary' : 'text-gray-500 hover:text-gray-300'}`}
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
                        <span className="text-[10px] font-semibold">
                            Beranda
                        </span>
                    </Link>

                    <Link
                        href="/invoice"
                        className={`relative flex flex-col items-center gap-0.5 px-3 py-1 transition ${isActive('/invoice') ? 'text-primary' : 'text-gray-500 hover:text-gray-300'}`}
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
                        <span className="text-[10px] font-medium">
                            Cek Invoice
                        </span>
                    </Link>

                    {/* Akun — only shown when user is logged in. Links to dashboard. */}
                    {auth?.user && (
                        <Link
                            href="/dashboard"
                            className={`relative flex flex-col items-center gap-0.5 px-3 py-1 transition ${isActive('/dashboard') ? 'text-primary' : 'text-gray-500 hover:text-gray-300'}`}
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
                            <span className="text-[10px] font-medium">
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
                            </ul>
                        </div>

                        {/* Kontak */}
                        <div>
                            <h3 className="mb-3 text-xs font-bold text-primary text-white md:mb-4 md:text-base">
                                Kontak
                            </h3>
                            <ul className="space-y-2 text-xs text-gray-400 md:space-y-3 md:text-sm">
                                <li>
                                    <a
                                        href="#"
                                        className="transition hover:text-primary"
                                    >
                                        WhatsApp
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="#"
                                        className="transition hover:text-primary"
                                    >
                                        Email
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="#"
                                        className="transition hover:text-primary"
                                    >
                                        Instagram
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {/* Sosial Media */}
                        <div className="col-span-2 md:col-span-1">
                            <h3 className="mb-3 text-xs font-bold text-primary text-white md:mb-4 md:text-base">
                                Sosial Media
                            </h3>
                            <div className="flex gap-4">
                                <a
                                    href="https://www.instagram.com/nuvelo.id?igsh=YnA5eXhzNjA3eTdw"
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
                                {/* <a
                                    href="#"
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
                                </a> */}
                            </div>
                        </div>
                    </div>

                    {/* Footer Bottom — stacked on mobile, side-by-side on desktop */}
                    <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 border-t border-[#31334c] px-4 pt-4 text-xs text-gray-300 md:flex-row md:gap-0 md:pt-6">
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

            {auth?.user && (
                <TransactionNotifier
                    userId={auth.user.id}
                    onNotification={handleNotification}
                />
            )}
            <LiveChat context={chatContext} />
        </>
    );
}
