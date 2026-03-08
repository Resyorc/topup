import { Link, usePage } from '@inertiajs/react';
import NewsTicker from '@/components/news-ticker';

export default function GuestLayout({ children }: { children: React.ReactNode }) {
    const { auth, broadcastMessages } = usePage().props as any;
    const tickerMsgs = broadcastMessages && broadcastMessages.length > 0 
        ? broadcastMessages 
        : ['🔥 PROMO SPESIAL MINGGU INI!', 'TOP UP DI NEBUSTORE CEPAT & TERPERCAYA'];

    return (
        <div className="min-h-screen flex flex-col pt-[116px] md:pt-[106px]">
            {/* Header Navbar */}
            <header className="fixed top-0 inset-x-0 z-50 bg-[#3E3D4980] text-white shadow-md backdrop-blur-sm">
                <div className="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4 py-4 md:gap-10">
                    {/* Logo */}
                    <Link href="/" className="cursor-pointer focus:outline-0 flex items-center gap-2">
                        <span className="text-primary font-black text-3xl tracking-tighter drop-shadow-[0_0_10px_#8327d8]">N</span>
                        <span className="text-white font-bold text-3xl tracking-tighter -ml-2 drop-shadow-md">ebu.</span>
                    </Link>

                    {/* Search Bar */}
                    <form className="hidden flex-1 items-center rounded-lg border border-gray-500 transition-all duration-300 focus-within:border-primary focus-within:shadow-[0_0_0_2px_rgba(168,85,247,0.2)] md:flex">
                        <input
                            type="text"
                            placeholder="Cari Game atau Voucher"
                            className="my-auto w-full bg-transparent px-5 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:outline-none"
                        />
                        <button
                            type="button"
                            className="cursor-pointer rounded-r-lg bg-primary px-5 py-2.5 hover:bg-primary/90 transition-colors duration-300 flex items-center justify-center"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-white">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </button>
                    </form>

                    {/* Right Auth / User Menu */}
                    <div className="flex items-center gap-3">
                        {auth?.user ? (
                            <div className="flex items-center gap-3">
                                {/* Dashboard Link */}
                                <Link 
                                    className="rounded-full w-10 h-10 border-2 border-primary/50 bg-white/10 hover:bg-white/20 flex items-center justify-center transition overflow-hidden" 
                                    href="/dashboard"
                                >
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-300">
                                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                </Link>
                            </div>
                        ) : (
                            <div className="flex items-center gap-3">
                                <Link href="/login" className="px-4 py-2 rounded-md bg-transparent border border-primary text-primary hover:bg-primary/10 text-sm font-semibold transition">
                                    Masuk
                                </Link>
                                <Link href="/register" className="hidden md:inline-block px-4 py-2 rounded-md bg-primary hover:hue-rotate-15 text-white shadow-[0_0_10px_#8327d8] text-sm font-semibold transition">
                                    Daftar
                                </Link>
                            </div>
                        )}
                    </div>
                </div>

                {/* Sub-Header Navigation with Ticker */}
                <div className="flex w-full items-center justify-center bg-[#6C3C8980] font-medium text-sm select-none shadow-inner">
                    <div className="max-w-7xl w-full mx-auto flex w-full flex-col-reverse flex-nowrap md:flex-row items-center gap-0 px-8 md:gap-12">
                        {/* Static Links */}
                        <div className="flex w-full md:w-auto flex-wrap items-center justify-center gap-x-8 sm:flex-nowrap sm:justify-between sm:gap-12 pb-2 md:pb-0">
                            <Link href="/" className="group relative flex cursor-pointer items-center py-3 text-nowrap text-white hover:text-gray-200 transition">
                                <div className="flex items-center justify-between gap-2.5">
                                    <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg" className="stroke-white">
                                        <path d="M15.75 3.125L9 9.875M9 9.875H13.008M9 9.875V5.867" strokeWidth="1.125" strokeLinecap="round" strokeLinejoin="round" />
                                        <path d="M16.5 9.875C16.5 13.4105 16.5 15.1782 15.4012 16.2762C14.304 17.375 12.5355 17.375 9 17.375C5.4645 17.375 3.69675 17.375 2.598 16.2762C1.5 15.179 1.5 13.4105 1.5 9.875C1.5 6.3395 1.5 4.57175 2.598 3.473C3.6975 2.375 5.4645 2.375 9 2.375" strokeWidth="1.125" strokeLinecap="round" />
                                    </svg>
                                    <span>Top Up</span>
                                </div>
                            </Link>

                            <Link href="/invoice" className="group relative flex cursor-pointer items-center py-3 text-nowrap text-white hover:text-gray-200 transition">
                                <div className="flex items-center justify-between gap-2.5">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" className="stroke-white">
                                        <path d="M15.012 1.5C14.1773 1.5 13.5 3.5145 13.5 6H15.012C15.741 6 16.1048 6 16.3305 5.74875C16.5555 5.49675 16.5165 5.16525 16.4385 4.503C16.23 2.7525 15.6705 1.5 15.012 1.5Z" strokeLinecap="round" strokeLinejoin="round" />
                                        <path d="M13.5 6.0405V13.9845C13.5 15.1177 13.5 15.6848 13.1535 15.9083C12.5873 16.2728 11.712 15.5077 11.2718 15.2302C10.908 15.0007 10.7265 14.8868 10.5247 14.88C10.3065 14.8725 10.1212 14.9827 9.72825 15.2302L8.295 16.134C7.908 16.3777 7.71525 16.5 7.5 16.5C7.28475 16.5 7.09125 16.3777 6.705 16.134L5.2725 15.2302C4.908 15.0007 4.7265 14.8868 4.52475 14.88C4.3065 14.8725 4.12125 14.9827 3.72825 15.2302C3.288 15.5077 2.41275 16.2728 1.84575 15.9083C1.5 15.6848 1.5 15.1185 1.5 13.9845V6.0405C1.5 3.9 1.5 2.8305 2.15925 2.16525C2.81775 1.5 3.879 1.5 6 1.5H15" strokeLinecap="round" strokeLinejoin="round" />
                                        <path d="M7.5 6C6.67125 6 6 6.504 6 7.125C6 7.746 6.67125 8.25 7.5 8.25C8.32875 8.25 9 8.754 9 9.375C9 9.996 8.32875 10.5 7.5 10.5M7.5 6C8.1525 6 8.709 6.31275 8.9145 6.75M7.5 6V5.25M7.5 10.5C6.8475 10.5 6.291 10.1873 6.0855 9.75M7.5 10.5V11.25" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                    <span>Cek Invoice</span>
                                </div>
                            </Link>
                        </div>

                        {/* Divider */}
                        <div className="hidden md:block">
                            <svg width="1" height="30" viewBox="0 0 1 30" fill="none" xmlns="http://www.w3.org/2000/svg" className="fill-white/30">
                                <path fillRule="evenodd" clipRule="evenodd" d="M0.5 0C0.367392 0 0.240214 0.0831764 0.146446 0.231231C0.052678 0.379286 0 0.580092 0 0.789474V29.2105C0 29.4199 0.052678 29.6207 0.146446 29.7688C0.240214 29.9168 0.367392 30 0.5 30C0.632608 30 0.759786 29.9168 0.853554 29.7688C0.947322 29.6207 1 29.4199 1 29.2105V0.789474C1 0.580092 0.947322 0.379286 0.853554 0.231231C0.759786 0.0831764 0.632608 0 0.5 0Z" />
                            </svg>
                        </div>

                        {/* Ticker Components */}
                        <div className="w-full flex-grow py-3 md:py-0 overflow-hidden relative">
                            <NewsTicker messages={tickerMsgs} speed={30} separator="◈" />
                        </div>
                    </div>
                </div>
            </header>

            {/* Main Content */}
            <main className="flex-1 w-full bg-background">
                {children}
            </main>

            {/* Footer */}
            <footer className="bg-[#1e1f29] border-t border-[#31334c] pt-12 pb-6 mt-16 px-4">
                <div className="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                    <div className="md:col-span-1">
                        <Link href="/" className="flex items-center gap-2 mb-4">
                            <span className="text-primary font-black text-3xl tracking-tighter">N</span>
                            <span className="text-white font-bold text-3xl tracking-tighter -ml-2">ebu.</span>
                        </Link>
                        <p className="text-sm text-gray-400">
                            <strong className="text-primary">NEBUSTORE</strong> adalah tempat top up games yang aman, murah dan terpercaya, dengan proses instan dan layanan 24 jam nonstop.
                        </p>
                    </div>
                    
                    <div>
                        <h3 className="font-bold text-white mb-4 text-primary">Navigasi</h3>
                        <ul className="space-y-3 text-sm text-gray-400">
                            <li><Link href="/register" className="hover:text-primary transition">Daftar</Link></li>
                            <li><Link href="/login" className="hover:text-primary transition">Masuk</Link></li>
                            <li><Link href="/" className="hover:text-primary transition">Top Up</Link></li>
                            <li><Link href="/invoice" className="hover:text-primary transition">Cek Invoice</Link></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 className="font-bold text-white mb-4 text-primary">Kontak</h3>
                        <ul className="space-y-3 text-sm text-gray-400">
                            <li><a href="#" className="hover:text-primary transition">WhatsApp</a></li>
                            <li><a href="#" className="hover:text-primary transition">Email</a></li>
                            <li><a href="#" className="hover:text-primary transition">Instagram</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 className="font-bold text-white mb-4 text-primary">Sosial Media</h3>
                        <div className="flex gap-4">
                            <a href="#" className="w-8 h-8 rounded-full bg-[#26273b] hover:bg-primary text-gray-300 hover:text-white flex items-center justify-center transition">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                            </a>
                            <a href="#" className="w-8 h-8 rounded-full bg-[#26273b] hover:bg-primary text-gray-300 hover:text-white flex items-center justify-center transition">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div className="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500 pt-6 border-t border-[#31334c]">
                    <p>&copy; 2025 Nebustore. All rights reserved.</p>
                    <div className="flex gap-4 mt-4 md:mt-0">
                        <Link href="/kebijakan-privasi" className="hover:text-gray-300 transition">Kebijakan Privasi</Link>
                        <Link href="/syarat-ketentuan" className="hover:text-gray-300 transition">Syarat & Ketentuan</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}
