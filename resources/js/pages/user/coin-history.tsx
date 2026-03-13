import { usePage } from '@inertiajs/react';
import UserLayout from '@/layouts/user-layout';

interface CoinHistoryItem {
    id: number;
    type: 'credit' | 'debit';
    amount: number;
    description: string;
    reference_id: string | null;
    created_at: string | null;
}

export default function CoinHistory() {
    const { coinBalance, history } = usePage<{
        coinBalance: number;
        history: CoinHistoryItem[];
    }>().props;

    const formatDate = (iso: string | null) => {
        if (!iso) return '-';
        return new Date(iso).toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    };

    return (
        <UserLayout title="Riwayat Coin">
            <section className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-white">Riwayat Coin</h1>
                        <p className="mt-1 text-sm text-gray-400">Histori kredit & debit Krysta Coin kamu.</p>
                    </div>
                    <div className="rounded-xl border border-[#31334c] bg-[#1e1f29] px-5 py-3 text-right">
                        <div className="text-xs tracking-wide text-gray-500 uppercase">Saldo Saat Ini</div>
                        <div className="text-2xl font-black text-yellow-400">
                            {coinBalance.toLocaleString('id-ID')} Coins
                        </div>
                    </div>
                </div>

                {/* Table */}
                <div className="overflow-hidden rounded-2xl border border-[#31334c] bg-[#1e1f29]">
                    {history.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 py-16 text-center text-gray-500">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 8v4l3 3" />
                            </svg>
                            <p className="text-sm">Belum ada riwayat transaksi coin.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-[#31334c] bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                                        <th className="px-5 py-3">Tanggal</th>
                                        <th className="px-5 py-3">Keterangan</th>
                                        <th className="px-5 py-3">Referensi</th>
                                        <th className="px-5 py-3 text-right">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[#31334c]">
                                    {history.map((item) => (
                                        <tr key={item.id} className="transition hover:bg-white/[0.02]">
                                            <td className="whitespace-nowrap px-5 py-3.5 text-gray-400">
                                                {formatDate(item.created_at)}
                                            </td>
                                            <td className="px-5 py-3.5 text-gray-200">
                                                {item.description || '-'}
                                            </td>
                                            <td className="px-5 py-3.5 font-mono text-xs text-gray-500">
                                                {item.reference_id || '-'}
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-3.5 text-right font-bold">
                                                {item.type === 'credit' ? (
                                                    <span className="text-green-400">
                                                        +{item.amount.toLocaleString('id-ID')}
                                                    </span>
                                                ) : (
                                                    <span className="text-red-400">
                                                        -{item.amount.toLocaleString('id-ID')}
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </section>
        </UserLayout>
    );
}
