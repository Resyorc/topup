import { Head, Link, usePage } from '@inertiajs/react';
import React, { useState } from 'react';
import GuestLayout from '@/layouts/guest-layout';

const TIER_CONFIG: Record<string, {
    icon: string;
    label: string;
    cardBg: string;
    badgeBg: string;
    badgeText: string;
    glow: string;
    initials: string;
}> = {
    platinum: {
        icon: '💎',
        label: 'Platinum',
        cardBg: 'from-purple-900/60 via-[#1e1f29] to-[#1e1f29] border-purple-500/40',
        badgeBg: 'bg-purple-500/20',
        badgeText: 'text-purple-300',
        glow: 'shadow-purple-500/20',
        initials: 'ring-purple-400',
    },
    gold: {
        icon: '🥇',
        label: 'Gold',
        cardBg: 'from-yellow-900/40 via-[#1e1f29] to-[#1e1f29] border-yellow-500/40',
        badgeBg: 'bg-yellow-500/20',
        badgeText: 'text-yellow-300',
        glow: 'shadow-yellow-500/20',
        initials: 'ring-yellow-400',
    },
    silver: {
        icon: '🥈',
        label: 'Silver',
        cardBg: 'from-blue-900/30 via-[#1e1f29] to-[#1e1f29] border-blue-500/30',
        badgeBg: 'bg-blue-500/20',
        badgeText: 'text-blue-300',
        glow: 'shadow-blue-500/10',
        initials: 'ring-blue-400',
    },
    bronze: {
        icon: '🥉',
        label: 'Bronze',
        cardBg: 'from-orange-900/20 via-[#1e1f29] to-[#1e1f29] border-orange-500/20',
        badgeBg: 'bg-orange-500/20',
        badgeText: 'text-orange-300',
        glow: '',
        initials: 'ring-orange-400',
    },
};

interface NavLinksProps {
    onNav?: () => void;
    url: string;
    tc: (typeof TIER_CONFIG)[string];
    avatarUrl: string | null;
    initials: string;
    user: any;
}

function NavLinks({ onNav, url, tc, avatarUrl, initials, user }: NavLinksProps) {
    return (
        <>
            {/* User + Tier Card */}
            <div className={`mb-2 rounded-2xl border bg-gradient-to-br p-4 shadow-lg ${tc.cardBg} ${tc.glow}`}>
                <div className="flex items-center gap-3">
                    <div className={`flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/10 text-sm font-bold text-white ring-2 ${tc.initials}`}>
                        {avatarUrl ? <img src={avatarUrl} alt="avatar" className="h-full w-full object-cover" /> : initials}
                    </div>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-semibold text-white">{user?.name ?? 'Member'}</p>
                        <div className={`mt-0.5 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold ${tc.badgeBg} ${tc.badgeText}`}>
                            {tc.icon} {tc.label}
                        </div>
                    </div>
                </div>
            </div>

            <Link href="/dashboard" onClick={onNav} className={`flex items-center gap-3 rounded-xl px-6 py-4 transition ${url.startsWith('/dashboard') && !url.includes('/dashboard/transactions') && !url.includes('/dashboard/coin-history') && !url.includes('/dashboard/settings') && !url.includes('/dashboard/member-club') && !url.includes('/dashboard/api-credentials') ? 'bg-gradient-to-r from-primary to-[#9b4dec] font-semibold text-white shadow-lg shadow-primary/20' : 'font-medium text-gray-300 hover:bg-white/5 hover:text-white'}`}>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="7" height="9" x="3" y="3" rx="1" /><rect width="7" height="5" x="14" y="3" rx="1" /><rect width="7" height="9" x="14" y="12" rx="1" /><rect width="7" height="5" x="3" y="16" rx="1" /></svg>
                Dashboard
            </Link>

            <Link href="/dashboard/transactions" onClick={onNav} className={`flex items-center gap-3 rounded-xl px-6 py-4 transition ${url.includes('/dashboard/transactions') ? 'bg-gradient-to-r from-primary to-[#9b4dec] font-semibold text-white shadow-lg shadow-primary/20' : 'font-medium text-gray-300 hover:bg-white/5 hover:text-white'}`}>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" /><path d="M3 6h18" /><path d="M16 10a4 4 0 0 1-8 0" /></svg>
                Transaksi
            </Link>

            <Link href="/dashboard/member-club" onClick={onNav} className={`flex items-center gap-3 rounded-xl px-6 py-4 transition ${url.includes('/dashboard/member-club') ? 'bg-gradient-to-r from-primary to-[#9b4dec] font-semibold text-white shadow-lg shadow-primary/20' : 'font-medium text-gray-300 hover:bg-white/5 hover:text-white'}`}>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" /><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" /><path d="M4 22h16" /><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" /><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" /><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" /></svg>
                Member Club
            </Link>

            <Link href="/dashboard/coin-history" onClick={onNav} className={`flex items-center gap-3 rounded-xl px-6 py-4 transition ${url.includes('/dashboard/coin-history') ? 'bg-gradient-to-r from-primary to-[#9b4dec] font-semibold text-white shadow-lg shadow-primary/20' : 'font-medium text-gray-300 hover:bg-white/5 hover:text-white'}`}>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="8" /><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" /><path d="M12 17h.01" /></svg>
                Riwayat Coin
            </Link>

            <Link href="/dashboard/settings" onClick={onNav} className={`flex items-center gap-3 rounded-xl px-6 py-4 transition ${url.includes('/dashboard/settings') ? 'bg-gradient-to-r from-primary to-[#9b4dec] font-semibold text-white shadow-lg shadow-primary/20' : 'font-medium text-gray-300 hover:bg-white/5 hover:text-white'}`}>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" /><circle cx="12" cy="12" r="3" /></svg>
                Pengaturan
            </Link>

            {user?.api_access_enabled && (
                <Link href="/dashboard/api-credentials" onClick={onNav} className={`flex items-center gap-3 rounded-xl px-6 py-4 transition ${url.includes('/dashboard/api-credentials') ? 'bg-linear-to-r from-primary to-[#9b4dec] font-semibold text-white shadow-lg shadow-primary/20' : 'font-medium text-gray-300 hover:bg-white/5 hover:text-white'}`}>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4" /></svg>
                    API Credentials
                </Link>
            )}

            <Link href="/logout" method="post" as="button" onClick={onNav} className="mt-2 flex items-center gap-3 rounded-xl px-6 py-4 font-medium text-red-400 transition hover:bg-red-500/10 hover:text-red-300">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m16 17 5-5-5-5" /><path d="M21 12H9" /><path d="M12 19H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h7" /></svg>
                Log out
            </Link>
        </>
    );
}

export default function UserLayout({
    children,
    title = '',
}: {
    children: React.ReactNode;
    title?: string;
}) {
    const { url } = usePage();
    const user = (usePage().props as any).auth?.user;
    const tier = user?.tier ?? 'bronze';
    const tc = TIER_CONFIG[tier] ?? TIER_CONFIG.bronze;
    const initials = user?.name
        ? user.name.split(' ').map((w: string) => w[0]).slice(0, 2).join('').toUpperCase()
        : '?';
    const avatarUrl: string | null = user?.avatar_url ?? null;
    const [menuOpen, setMenuOpen] = useState(false);

    return (
        <GuestLayout>
            <Head title={title} />

            {/* Mobile hamburger bar */}
            <div className="sticky top-0 z-40 flex items-center justify-between border-b border-[#31334c] bg-[#12121a]/95 px-4 py-3 backdrop-blur md:hidden">
                <div className="flex items-center gap-2">
                    <div className={`flex h-7 w-7 items-center justify-center overflow-hidden rounded-full bg-white/10 text-xs font-bold text-white ring-1 ${tc.initials}`}>
                        {avatarUrl ? <img src={avatarUrl} alt="avatar" className="h-full w-full object-cover" /> : initials}
                    </div>
                    <span className={`text-xs font-bold ${tc.badgeText}`}>{tc.icon} {tc.label}</span>
                </div>
                <button
                    onClick={() => setMenuOpen(true)}
                    className="rounded-lg p-2 text-gray-300 hover:bg-white/10"
                    aria-label="Buka menu"
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <line x1="4" x2="20" y1="6" y2="6" /><line x1="4" x2="20" y1="12" y2="12" /><line x1="4" x2="20" y1="18" y2="18" />
                    </svg>
                </button>
            </div>

            {/* Mobile drawer overlay */}
            {menuOpen && (
                <div className="fixed inset-0 z-50 md:hidden">
                    {/* Backdrop */}
                    <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={() => setMenuOpen(false)} />
                    {/* Drawer */}
                    <div className="absolute top-0 left-0 flex h-full w-72 flex-col gap-2 overflow-y-auto bg-[#12121a] p-4 shadow-2xl">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="text-sm font-semibold text-gray-400">Menu</span>
                            <button onClick={() => setMenuOpen(false)} className="rounded-lg p-1.5 text-gray-400 hover:bg-white/10">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                                </svg>
                            </button>
                        </div>
                        <NavLinks onNav={() => setMenuOpen(false)} url={url} tc={tc} avatarUrl={avatarUrl} initials={initials} user={user} />
                    </div>
                </div>
            )}

            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 gap-8 md:grid-cols-12">
                    {/* Left Sidebar — desktop only */}
                    <aside className="hidden flex-col gap-2 md:col-span-3 md:col-start-1 md:flex">
                        <NavLinks url={url} tc={tc} avatarUrl={avatarUrl} initials={initials} user={user} />
                    </aside>

                    {/* Main Content Area */}
                    <div className="flex flex-col gap-10 md:col-span-9">
                        {children}
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
