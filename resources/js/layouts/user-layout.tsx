import { Head, Link, usePage } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';
import React from 'react';

export default function UserLayout({
    children,
    title = '',
}: {
    children: React.ReactNode;
    title?: string;
}) {
    const { url } = usePage();

    return (
        <GuestLayout>
            <Head title={title} />

            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 gap-8 md:grid-cols-12">
                    {/* Left Sidebar */}
                    <aside className="flex flex-col gap-2 md:col-span-3 md:col-start-1">
                        <Link
                            href="/dashboard"
                            className={`flex items-center gap-3 rounded-xl px-6 py-4 transition ${
                                url.startsWith('/dashboard') &&
                                !url.includes('/dashboard/transactions') &&
                                !url.includes('/dashboard/settings')
                                    ? 'bg-gradient-to-r from-primary to-[#9b4dec] font-semibold text-white shadow-lg shadow-primary/20'
                                    : 'font-medium text-gray-300 hover:bg-white/5 hover:text-white'
                            }`}
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
                            className={`flex items-center gap-3 rounded-xl px-6 py-4 transition ${
                                url.includes('/dashboard/transactions')
                                    ? 'bg-gradient-to-r from-primary to-[#9b4dec] font-semibold text-white shadow-lg shadow-primary/20'
                                    : 'font-medium text-gray-300 hover:bg-white/5 hover:text-white'
                            }`}
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
                            href="/dashboard/settings"
                            className={`flex items-center gap-3 rounded-xl px-6 py-4 transition ${
                                url.includes('/dashboard/settings')
                                    ? 'bg-gradient-to-r from-primary to-[#9b4dec] font-semibold text-white shadow-lg shadow-primary/20'
                                    : 'font-medium text-gray-300 hover:bg-white/5 hover:text-white'
                            }`}
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

                        <Link
                            href="/logout"
                            method="post"
                            as="button"
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
