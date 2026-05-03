import { Head, Link, usePage } from '@inertiajs/react';
import React, { useState } from 'react';
import GuestLayout from '@/layouts/guest-layout';

interface NavLinksProps {
    onNav?: () => void;
    url: string;
    avatarUrl: string | null;
    initials: string;
    user: any;
}

function NavLinks({ onNav, url, avatarUrl, initials, user }: NavLinksProps) {
    const navBase = 'flex items-center gap-3 rounded-xl px-6 py-4 transition';
    const navActive =
        'border border-[var(--color-accent-border)] bg-[var(--color-accent-soft)] font-semibold text-[var(--color-accent)] shadow-[inset_3px_0_0_var(--color-accent)]';
    const navInactive =
        'font-medium text-gray-300 hover:bg-white/5 hover:text-white';

    return (
        <>
            {/* User Card */}
            <div className="border-border-light mb-2 rounded-2xl border bg-card p-4">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/10 text-sm font-bold text-white ring-2 ring-[var(--color-accent-border)]">
                        {avatarUrl ? (
                            <img
                                src={avatarUrl}
                                alt="avatar"
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            initials
                        )}
                    </div>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-semibold text-white">
                            {user?.name ?? 'User'}
                        </p>
                        <p className="truncate text-xs text-gray-400">
                            {user?.email ?? ''}
                        </p>
                    </div>
                </div>
            </div>

            <Link
                href="/dashboard"
                onClick={onNav}
                className={`${navBase} ${url.startsWith('/dashboard') && !url.includes('/dashboard/transactions') && !url.includes('/dashboard/coin-history') && !url.includes('/dashboard/settings') && !url.includes('/dashboard/member-club') && !url.includes('/dashboard/api-credentials') ? navActive : navInactive}`}
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
                    <rect width="7" height="9" x="3" y="3" rx="1" />
                    <rect width="7" height="5" x="14" y="3" rx="1" />
                    <rect width="7" height="9" x="14" y="12" rx="1" />
                    <rect width="7" height="5" x="3" y="16" rx="1" />
                </svg>
                Dashboard
            </Link>

            <Link
                href="/dashboard/transactions"
                onClick={onNav}
                className={`${navBase} ${url.includes('/dashboard/transactions') ? navActive : navInactive}`}
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
                onClick={onNav}
                className={`${navBase} ${url.includes('/dashboard/coin-history') ? navActive : navInactive}`}
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
                onClick={onNav}
                className={`${navBase} ${url.includes('/dashboard/settings') ? navActive : navInactive}`}
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

            {user?.api_access_enabled && (
                <Link
                    href="/dashboard/api-credentials"
                    onClick={onNav}
                    className={`${navBase} ${url.includes('/dashboard/api-credentials') ? navActive : navInactive}`}
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
                onClick={onNav}
                className="mt-2 flex items-center gap-3 rounded-xl px-6 py-4 font-medium text-red-400 transition hover:bg-red-500/10 hover:text-red-300"
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
    const initials = user?.name
        ? user.name
              .split(' ')
              .map((w: string) => w[0])
              .slice(0, 2)
              .join('')
              .toUpperCase()
        : '?';
    const avatarUrl: string | null = user?.avatar_url ?? null;

    return (
        <GuestLayout>
            <Head title={title} />

            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 gap-8 md:grid-cols-12">
                    {/* Left Sidebar — desktop only */}
                    <aside className="hidden flex-col gap-2 md:col-span-3 md:col-start-1 md:flex">
                        <NavLinks
                            url={url}
                            avatarUrl={avatarUrl}
                            initials={initials}
                            user={user}
                        />
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
