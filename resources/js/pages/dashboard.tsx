import { usePage } from '@inertiajs/react';
import UserLayout from '@/layouts/user-layout';

export default function Dashboard() {
    const { auth } = usePage().props as any;
    const user = auth?.user;

    return (
        <UserLayout title="Dashboard">
                        {/* 1. Profil Section */}
                        <section>
                            <h2 className="text-2xl font-bold text-white mb-6">Profil</h2>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Profile Card (Polygon) */}
                                <div className="relative group p-[1px]">
                                    {/* The border / background wrapper */}
                                    <div className="absolute inset-0 bg-primary [clip-path:polygon(0_20px,20px_0,100%_0,100%_calc(100%-20px),calc(100%-20px)_100%,0_100%)]"></div>
                                    {/* Inner dark container */}
                                    <div className="relative h-full bg-[#1A1A24] [clip-path:polygon(0_20px,20px_0,100%_0,100%_calc(100%-20px),calc(100%-20px)_100%,0_100%)] p-6 md:p-8 flex flex-col items-center justify-center">
                                        <div className="w-20 h-20 rounded-full border-4 border-gray-400 bg-white mb-4 overflow-hidden shadow-lg flex items-center justify-center">
                                            {/* Avatar Placeholder */}
                                            <svg className="w-16 h-16 text-gray-300 mt-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                        </div>
                                        <h3 className="text-lg font-bold text-white">{user?.name || 'Ferry Oktariansyah'}</h3>
                                        <div className="mt-1 px-4 py-0.5 border border-primary text-primary text-xs font-bold rounded-full mb-6">
                                            Member
                                        </div>
                                        
                                        <div className="w-full h-px bg-white/10 mb-4"></div>
                                        
                                        <div className="flex flex-col w-full gap-3 text-sm text-gray-300">
                                            <div className="flex items-center gap-3">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                                {user?.email || 'ferrygaming@gmail.com'}
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                                +62822990087654
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                {/* Nebits Coins Card */}
                                <div className="h-full rounded-2xl bg-gradient-to-br from-[#1b1c2a] via-[#212338] to-[#12101e] border border-white/5 p-6 md:p-8 flex flex-col justify-between relative overflow-hidden shadow-2xl">
                                    {/* Dotted texture background simulate */}
                                    <div className="absolute top-0 right-0 w-40 h-40 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:10px_10px] [mask-image:radial-gradient(ellipse_at_top_right,black,transparent_70%)]"></div>
                                    
                                    <div className="flex items-center gap-4 relative z-10">
                                        <div className="w-12 h-12 rounded-full bg-gradient-to-tr from-cyan-500 to-primary flex items-center justify-center font-black text-2xl text-white shadow-[0_0_15px_rgba(168,85,247,0.4)] border border-primary/50">
                                            N
                                        </div>
                                        <div>
                                            <h3 className="text-xl font-bold text-white">Nebits Coins</h3>
                                            <p className="text-xs text-gray-400">(Bebas Biaya Admin)</p>
                                        </div>
                                    </div>
                                    
                                    <div className="mt-8 flex flex-col md:flex-row md:items-end justify-between gap-4 relative z-10">
                                        <div>
                                            <div className="flex items-baseline gap-2">
                                                <span className="text-4xl font-black text-yellow-400">19.280</span>
                                                <span className="text-2xl font-bold text-white">Coins</span>
                                            </div>
                                            <div className="flex items-center gap-1.5 mt-2 text-xs text-gray-300 italic">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                                1 Nebits Coins = 1 Rupiah
                                            </div>
                                        </div>
                                        
                                        <button className="px-6 py-2.5 rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] text-white font-bold text-sm shadow-lg hover:opacity-90 transition w-full md:w-auto">
                                            Top Up
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {/* 2. Transaksi Hari Ini */}
                        <section>
                            <h2 className="text-2xl font-bold text-white mb-6">Transaksi Hari Ini</h2>
                            
                            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                                {/* Menunggu */}
                                <div className="bg-[#1e1f29]/80 border border-[#31334c] rounded-xl p-5 flex flex-col justify-between min-h-[120px]">
                                    <div className="w-10 h-10 rounded-lg bg-yellow-600/20 text-yellow-500 flex items-center justify-center mb-4">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    </div>
                                    <div className="flex items-end justify-between w-full">
                                        <span className="text-xs font-medium text-gray-400">Menunggu</span>
                                        <span className="text-2xl font-black text-white leading-none">12</span>
                                    </div>
                                </div>
                                
                                {/* Proses */}
                                <div className="bg-[#1e1f29]/80 border border-[#31334c] rounded-xl p-5 flex flex-col justify-between min-h-[120px]">
                                    <div className="w-10 h-10 rounded-lg bg-blue-600/20 text-blue-500 flex items-center justify-center mb-4">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h.01"/><path d="M17 7h.01"/><path d="M7 17h.01"/><path d="M17 17h.01"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="M2 12h2"/><path d="M20 12h2"/></svg>
                                    </div>
                                    <div className="flex items-end justify-between w-full">
                                        <span className="text-xs font-medium text-gray-400">Proses</span>
                                        <span className="text-2xl font-black text-white leading-none">12</span>
                                    </div>
                                </div>

                                {/* Selesai */}
                                <div className="bg-[#1e1f29]/80 border border-[#31334c] rounded-xl p-5 flex flex-col justify-between min-h-[120px]">
                                    <div className="w-10 h-10 rounded-lg bg-green-600/20 text-green-500 flex items-center justify-center mb-4">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    </div>
                                    <div className="flex items-end justify-between w-full">
                                        <span className="text-xs font-medium text-gray-400">Selesai</span>
                                        <span className="text-2xl font-black text-white leading-none">20</span>
                                    </div>
                                </div>

                                {/* Gagal */}
                                <div className="bg-[#1e1f29]/80 border border-[#31334c] rounded-xl p-5 flex flex-col justify-between min-h-[120px]">
                                    <div className="w-10 h-10 rounded-lg bg-red-600/20 text-red-500 flex items-center justify-center mb-4">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
                                    </div>
                                    <div className="flex items-end justify-between w-full">
                                        <span className="text-xs font-medium text-gray-400">Gagal</span>
                                        <span className="text-2xl font-black text-white leading-none">0</span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {/* 3. List Transaksi Terbaru */}
                        <section>
                            <h2 className="text-2xl font-bold text-white mb-6">List Transaksi Terbaru</h2>
                            
                            <div className="bg-[#1e1f29] rounded-xl overflow-hidden border border-[#31334c]">
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm text-left">
                                        <thead className="text-xs font-bold text-gray-300 uppercase bg-white/10 border-b border-[#31334c]">
                                            <tr>
                                                <th scope="col" className="px-6 py-4">Nomor Invoice</th>
                                                <th scope="col" className="px-6 py-4">Produk</th>
                                                <th scope="col" className="px-6 py-4">Item</th>
                                                <th scope="col" className="px-6 py-4">Harga</th>
                                                <th scope="col" className="px-6 py-4">Tanggal</th>
                                                <th scope="col" className="px-6 py-4">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {/* Dummy Rows for Showcase */}
                                            <tr className="border-b border-[#31334c] hover:bg-white/5 transition">
                                                <td className="px-6 py-4 font-medium text-white">LDO1B2C7ECEDE...</td>
                                                <td className="px-6 py-4 text-gray-300">Mobile Legends</td>
                                                <td className="px-6 py-4 text-gray-300">Diamonds</td>
                                                <td className="px-6 py-4 text-gray-300">Rp 5.330</td>
                                                <td className="px-6 py-4 text-gray-300">11/07/2025</td>
                                                <td className="px-6 py-4">
                                                    <span className="bg-green-500/20 text-green-400 border border-green-500/30 text-[10px] font-bold px-2 py-1 rounded">SUCCESS</span>
                                                </td>
                                            </tr>
                                            <tr className="border-b border-[#31334c] hover:bg-white/5 transition">
                                                <td className="px-6 py-4 font-medium text-white">LDO1B2C7ECED7...</td>
                                                <td className="px-6 py-4 text-gray-300">Mobile Legends</td>
                                                <td className="px-6 py-4 text-gray-300">Diamonds</td>
                                                <td className="px-6 py-4 text-gray-300">Rp 5.330</td>
                                                <td className="px-6 py-4 text-gray-300">09/07/2025</td>
                                                <td className="px-6 py-4">
                                                    <span className="bg-green-500/20 text-green-400 border border-green-500/30 text-[10px] font-bold px-2 py-1 rounded">SUCCESS</span>
                                                </td>
                                            </tr>
                                            {/* We can map over real transactions here when the backend is connected */}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                        
        </UserLayout>
    );
}
