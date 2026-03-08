import { usePage } from '@inertiajs/react';
import UserLayout from '@/layouts/user-layout';

export default function Transactions() {
    const { auth } = usePage().props as any;

    return (
        <UserLayout title="Riwayat Transaksi">
            <h2 className="text-2xl font-bold text-white mb-6">Riwayat Transaksi</h2>
            
            {/* Filter Section */}
            <div className="bg-[#1e1f29] rounded-xl border border-[#31334c] p-6 mb-6">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    {/* Status */}
                    <div>
                        <label className="block text-sm font-bold text-white mb-2">Status</label>
                        <select className="w-full bg-[#1A1A24] border border-[#31334c] text-sm text-gray-300 rounded-lg focus:ring-primary focus:border-primary block p-2.5 outline-none transition">
                            <option value="">Semua</option>
                            <option value="success">Success</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>

                    {/* Status Pembayaran */}
                    <div>
                        <label className="block text-sm font-bold text-white mb-2">Status Pembayaran</label>
                        <select className="w-full bg-[#1A1A24] border border-[#31334c] text-sm text-gray-300 rounded-lg focus:ring-primary focus:border-primary block p-2.5 outline-none transition">
                            <option value="">Semua</option>
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>

                    {/* Tanggal Mulai */}
                    <div>
                        <label className="block text-sm font-bold text-white mb-2">Tanggal Mulai</label>
                        <div className="relative">
                            <input 
                                type="date" 
                                className="w-full bg-[#1A1A24] border border-[#31334c] text-sm text-gray-300 rounded-lg focus:ring-primary focus:border-primary block p-2.5 outline-none transition [&::-webkit-calendar-picker-indicator]:invert-[0.6]"
                                defaultValue="2025-07-23"
                            />
                        </div>
                    </div>

                    {/* Tanggal Selesai */}
                    <div>
                        <label className="block text-sm font-bold text-white mb-2">Tanggal Selesai</label>
                        <div className="relative">
                            <input 
                                type="date" 
                                className="w-full bg-[#1A1A24] border border-[#31334c] text-sm text-gray-300 rounded-lg focus:ring-primary focus:border-primary block p-2.5 outline-none transition [&::-webkit-calendar-picker-indicator]:invert-[0.6]"
                                defaultValue="2025-07-23"
                            />
                        </div>
                    </div>
                </div>

                {/* Cari Search Bar */}
                <div>
                    <label className="block text-sm font-bold text-white mb-2">Cari</label>
                    <div className="relative">
                        <input 
                            type="text" 
                            placeholder="Masukkan Deskripsi" 
                            className="w-full bg-[#1A1A24] border border-[#31334c] text-sm text-gray-300 rounded-lg focus:ring-primary focus:border-primary block p-2.5 pr-10 outline-none transition"
                        />
                        <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            {/* Table Section */}
            <div className="bg-[#1e1f29] rounded-xl overflow-hidden border border-[#31334c]">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm text-left">
                        <thead className="text-xs font-bold text-white uppercase bg-white/10 border-b border-[#31334c]">
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
                        </tbody>
                    </table>
                </div>
            </div>
        </UserLayout>
    );
}
