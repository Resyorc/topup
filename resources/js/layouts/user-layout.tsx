import { Head, Link, usePage } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';
import React from 'react';

export default function UserLayout({ children, title = "" }: { children: React.ReactNode, title?: string }) {
    const { url } = usePage();

    return (
        <GuestLayout>
            <Head title={title} />
            
            <div className="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-10">
                <div className="grid grid-cols-1 md:grid-cols-12 gap-8">
                    
                    {/* Left Sidebar */}
                    <aside className="md:col-span-3 md:col-start-1 flex flex-col gap-2">
                        <Link 
                            href="/dashboard" 
                            className={`flex items-center gap-3 px-6 py-4 rounded-xl transition ${
                                url.startsWith('/dashboard') && !url.includes('/dashboard/transactions') && !url.includes('/dashboard/settings')
                                ? 'bg-gradient-to-r from-primary to-[#9b4dec] text-white font-semibold shadow-lg shadow-primary/20' 
                                : 'text-gray-300 font-medium hover:bg-white/5 hover:text-white'
                            }`}
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                            Dashboard
                        </Link>
                        
                        <Link 
                            href="/dashboard/transactions" 
                            className={`flex items-center gap-3 px-6 py-4 rounded-xl transition ${
                                url.includes('/dashboard/transactions')
                                ? 'bg-gradient-to-r from-primary to-[#9b4dec] text-white font-semibold shadow-lg shadow-primary/20' 
                                : 'text-gray-300 font-medium hover:bg-white/5 hover:text-white'
                            }`}
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Transaksi
                        </Link>
                        
                        <Link 
                            href="/dashboard/settings" 
                            className={`flex items-center gap-3 px-6 py-4 rounded-xl transition ${
                                url.includes('/dashboard/settings')
                                ? 'bg-gradient-to-r from-primary to-[#9b4dec] text-white font-semibold shadow-lg shadow-primary/20' 
                                : 'text-gray-300 font-medium hover:bg-white/5 hover:text-white'
                            }`}
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                            Pengaturan
                        </Link>
                    </aside>
                    
                    {/* Main Content Area */}
                    <div className="md:col-span-9 flex flex-col gap-10">
                        {children}
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
