import { useForm, usePage } from '@inertiajs/react';
import UserLayout from '@/layouts/user-layout';
import {
    formatCurrency,
    formatDate,
    getPaymentStatusBadge,
    getTransactionStatusBadge,
} from '@/lib';

interface TransactionItem {
    id: string;
    invoice_id: string;
    game_name: string;
    product_name: string;
    amount: number;
    created_at: string | null;
    status: string;
    payment_status: string;
}

interface TransactionFilters {
    status: string;
    payment_status: string;
    start_date: string;
    end_date: string;
    search: string;
}

export default function Transactions() {
    const pageProps = usePage().props as any;
    const filters: TransactionFilters = pageProps.filters ?? {
        status: '',
        payment_status: '',
        start_date: '',
        end_date: '',
        search: '',
    };
    const transactions: TransactionItem[] = pageProps.transactions ?? [];

    const { data, setData, get, processing } = useForm<TransactionFilters>({
        status: filters.status,
        payment_status: filters.payment_status,
        start_date: filters.start_date,
        end_date: filters.end_date,
        search: filters.search,
    });

    const onApplyFilters = (e: React.FormEvent) => {
        e.preventDefault();

        get('/dashboard/transactions', {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <UserLayout title="Riwayat Transaksi">
            <h2 className="mb-6 text-2xl font-bold text-white">
                Riwayat Transaksi
            </h2>

            {/* Filter Section */}
            <form
                onSubmit={onApplyFilters}
                className="mb-6 rounded-xl border border-[#31334c] bg-[#1e1f29] p-6"
            >
                <div className="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label className="mb-2 block text-sm font-bold text-white">
                            Status
                        </label>
                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-2.5 text-sm text-gray-300 transition outline-none focus:border-primary focus:ring-primary"
                        >
                            <option value="">Semua</option>
                            <option value="pending">Menunggu</option>
                            <option value="paid">Paid</option>
                            <option value="processing">Processing</option>
                            <option value="success">Success</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-bold text-white">
                            Status Pembayaran
                        </label>
                        <select
                            value={data.payment_status}
                            onChange={(e) =>
                                setData('payment_status', e.target.value)
                            }
                            className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-2.5 text-sm text-gray-300 transition outline-none focus:border-primary focus:ring-primary"
                        >
                            <option value="">Semua</option>
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-bold text-white">
                            Tanggal Mulai
                        </label>
                        <div className="relative">
                            <input
                                type="date"
                                value={data.start_date}
                                onChange={(e) =>
                                    setData('start_date', e.target.value)
                                }
                                className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-2.5 text-sm text-gray-300 transition outline-none focus:border-primary focus:ring-primary [&::-webkit-calendar-picker-indicator]:invert-[0.6]"
                            />
                        </div>
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-bold text-white">
                            Tanggal Selesai
                        </label>
                        <div className="relative">
                            <input
                                type="date"
                                value={data.end_date}
                                onChange={(e) =>
                                    setData('end_date', e.target.value)
                                }
                                className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-2.5 text-sm text-gray-300 transition outline-none focus:border-primary focus:ring-primary [&::-webkit-calendar-picker-indicator]:invert-[0.6]"
                            />
                        </div>
                    </div>
                </div>

                {/* Cari Search Bar */}
                <div>
                    <label className="mb-2 block text-sm font-bold text-white">
                        Cari
                    </label>
                    <div className="relative">
                        <input
                            type="text"
                            value={data.search}
                            onChange={(e) => setData('search', e.target.value)}
                            placeholder="Masukkan nomor invoice, produk, atau game"
                            className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-2.5 pr-10 text-sm text-gray-300 transition outline-none focus:border-primary focus:ring-primary"
                        />
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
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
                                <circle cx="11" cy="11" r="8" />
                                <line x1="21" x2="16.65" y1="21" y2="16.65" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div className="mt-4 flex flex-wrap gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white transition hover:opacity-90 disabled:opacity-60"
                    >
                        {processing ? 'Memuat...' : 'Terapkan Filter'}
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            setData({
                                status: '',
                                payment_status: '',
                                start_date: '',
                                end_date: '',
                                search: '',
                            });
                            get('/dashboard/transactions', {
                                preserveState: true,
                                preserveScroll: true,
                                replace: true,
                            });
                        }}
                        className="rounded-lg border border-[#31334c] px-4 py-2 text-sm font-bold text-gray-300 transition hover:bg-white/5"
                    >
                        Reset
                    </button>
                </div>
            </form>

            {/* Table Section */}
            <div className="overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29]">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-[#31334c] bg-white/10 text-xs font-bold text-white uppercase">
                            <tr>
                                <th scope="col" className="px-6 py-4">
                                    Nomor Invoice
                                </th>
                                <th scope="col" className="px-6 py-4">
                                    Produk
                                </th>
                                <th scope="col" className="px-6 py-4">
                                    Item
                                </th>
                                <th scope="col" className="px-6 py-4">
                                    Harga
                                </th>
                                <th scope="col" className="px-6 py-4">
                                    Tanggal
                                </th>
                                <th scope="col" className="px-6 py-4">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {transactions.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-6 py-8 text-center text-gray-400"
                                    >
                                        Data transaksi tidak ditemukan.
                                    </td>
                                </tr>
                            )}
                            {transactions.map((transaction) => {
                                const transactionBadge =
                                    getTransactionStatusBadge(
                                        transaction.status,
                                    );
                                const paymentBadge = getPaymentStatusBadge(
                                    transaction.payment_status,
                                );

                                return (
                                    <tr
                                        key={transaction.id}
                                        className="border-b border-[#31334c] transition hover:bg-white/5"
                                    >
                                        <td className="px-6 py-4 font-medium text-white">
                                            {transaction.invoice_id}
                                        </td>
                                        <td className="px-6 py-4 text-gray-300">
                                            {transaction.game_name}
                                        </td>
                                        <td className="px-6 py-4 text-gray-300">
                                            {transaction.product_name}
                                        </td>
                                        <td className="px-6 py-4 text-gray-300">
                                            {formatCurrency(transaction.amount)}
                                        </td>
                                        <td className="px-6 py-4 text-gray-300">
                                            {transaction.created_at
                                                ? formatDate(
                                                      transaction.created_at,
                                                  )
                                                : '-'}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex flex-col gap-1">
                                                <span
                                                    className={`${transactionBadge.className} w-fit rounded px-2 py-1 text-[10px] font-bold uppercase`}
                                                >
                                                    {transactionBadge.label}
                                                </span>
                                                <span
                                                    className={`${paymentBadge.className} w-fit rounded px-2 py-1 text-[10px] font-bold uppercase`}
                                                >
                                                    {paymentBadge.label}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>
        </UserLayout>
    );
}
