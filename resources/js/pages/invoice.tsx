import React, { useState, useEffect } from 'react';
import { Head, useForm, Link, router } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';
import GameCard from '@/components/game-card';
import {
    getPaymentStatusBadge,
    getTransactionStatusBadge,
    formatCurrency,
} from '@/lib';

interface InvoiceSearchProps {
    initialInvoiceData?: any;
    searchedInvoiceId?: string;
}

export default function InvoiceSearch({
    initialInvoiceData = null,
    searchedInvoiceId = '',
}: InvoiceSearchProps) {
    const { data, setData, get, processing, errors } = useForm({
        invoice_id: searchedInvoiceId,
    });

    const [invoiceData, setInvoiceData] = useState<any>(initialInvoiceData);
    const [animatedStatus, setAnimatedStatus] = useState<number>(0);
    const [isPaymentOpen, setIsPaymentOpen] = useState<boolean>(true);

    // Sync newly grabbed server data
    useEffect(() => {
        setInvoiceData(initialInvoiceData);
    }, [initialInvoiceData]);

    // Auto-polling effect for real-time updates
    useEffect(() => {
        let pollInterval: NodeJS.Timeout;

        // Only poll if we have an active invoice displayed and it's not yet successful/failed
        if (
            invoiceData &&
            !['success', 'failed', 'expired', 'canceled'].includes(
                invoiceData.status.toLowerCase(),
            )
        ) {
            pollInterval = setInterval(() => {
                router.reload({
                    only: ['initialInvoiceData'],
                });
            }, 3000); // Poll every 3 seconds
        }

        return () => {
            if (pollInterval) clearInterval(pollInterval);
        };
    }, [invoiceData]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        get('/invoice', {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const lastInvoiceNoRef = React.useRef<string>('');

    const paymentBadge = getPaymentStatusBadge(invoiceData?.payment_status);
    const transactionBadge = getTransactionStatusBadge(invoiceData?.status);

    // Animate progress bar incrementally when invoice is loaded
    useEffect(() => {
        if (invoiceData) {
            let targetStep = 0;
            const statusLower = invoiceData.status.toLowerCase();
            switch (statusLower) {
                case 'pending':
                    targetStep = 1;
                    break;
                case 'paid':
                    targetStep = 2;
                    break;
                case 'processing':
                    targetStep = 3;
                    break;
                case 'success':
                    targetStep = 4;
                    break;
                // 'failed' might remain at step 0 or step 1
                default:
                    targetStep = 0;
            }

            let startStatus = animatedStatus;

            // If it's a completely different invoice, reset animation to 0
            if (invoiceData.invoice_no !== lastInvoiceNoRef.current) {
                startStatus = 0;
                setAnimatedStatus(0);
                lastInvoiceNoRef.current = invoiceData.invoice_no;
            }

            if (targetStep > startStatus) {
                const interval = setInterval(() => {
                    setAnimatedStatus((prev) => {
                        if (prev < targetStep) return prev + 1;
                        clearInterval(interval);
                        return prev;
                    });
                }, 500); // animate every 500ms

                return () => clearInterval(interval);
            } else if (targetStep < startStatus) {
                setAnimatedStatus(targetStep);
            }
        } else {
            setAnimatedStatus(0);
            lastInvoiceNoRef.current = '';
        }
    }, [invoiceData]);

    return (
        <GuestLayout>
            <Head title="Cek Invoice" />

            <div className="relative flex min-h-[calc(100vh-106px)] items-center justify-center overflow-hidden py-20">
                {/* Dotted Texture Background - Top Right */}
                <div className="pointer-events-none absolute top-0 right-0 h-96 w-96 bg-[radial-gradient(#fff_2px,transparent_2px)] [mask-image:radial-gradient(ellipse_at_top_right,black_10%,transparent_70%)] [background-size:24px_24px] opacity-[0.03]"></div>

                {/* Dotted Texture Background - Bottom Left */}
                <div className="pointer-events-none absolute bottom-0 left-0 h-96 w-96 bg-[radial-gradient(#fff_2px,transparent_2px)] [mask-image:radial-gradient(ellipse_at_bottom_left,black_10%,transparent_70%)] [background-size:24px_24px] opacity-[0.03]"></div>

                <div className="relative z-10 mx-auto flex w-full max-w-5xl flex-col items-center px-4 sm:px-6 lg:px-8">
                    {/* Tampilkan Header & Pencarian hanya jika TIDAK ada invoiceData */}
                    {!invoiceData && (
                        <>
                            {/* Header Texts */}
                            <div className="mb-10 text-center">
                                <h1 className="mb-4 text-3xl font-bold text-white md:text-4xl">
                                    Periksa Invoice Anda dengan{' '}
                                    <span className="text-[#FFC107]">
                                        Mudah dan Cepat
                                    </span>
                                </h1>
                                <p className="text-sm text-gray-300 md:text-base">
                                    Lihat detail pembelian anda menggunakan
                                    nomor Invoice.
                                </p>
                            </div>

                            {/* Search Box */}
                            <div className="w-full max-w-5xl overflow-hidden rounded-2xl border border-[#31334c] bg-[#1e1f29] shadow-2xl">
                                {/* Box Header */}
                                <div className="border-b border-[#31334c] bg-white/10 px-6 py-4">
                                    <h2 className="text-lg font-bold text-white">
                                        Nomor Invoice
                                    </h2>
                                </div>

                                {/* Box Body */}
                                <div className="p-6 md:p-8">
                                    <form
                                        onSubmit={submit}
                                        className="flex flex-col gap-6"
                                    >
                                        <div>
                                            <input
                                                type="text"
                                                value={data.invoice_id}
                                                onChange={(e) =>
                                                    setData(
                                                        'invoice_id',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Masukkan Nomor Invoice"
                                                className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-4 text-white placeholder-gray-500 transition outline-none focus:border-primary focus:ring-primary"
                                                required
                                            />
                                            {errors.invoice_id && (
                                                <p className="mt-2 text-sm text-red-500">
                                                    {errors.invoice_id}
                                                </p>
                                            )}
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] px-6 py-4 text-lg font-bold text-white shadow-[0_0_20px_rgba(168,85,247,0.3)] transition hover:opacity-90 disabled:opacity-50"
                                        >
                                            Cari Pesanan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </>
                    )}

                    {/* DETAIL INVOICE SECTION (Tampil jika ada data invoice) */}
                    {invoiceData && (
                        <div className="animate-fade-in-up mt-10 flex w-full max-w-5xl flex-col gap-6">
                            {/* Status Bar Card */}
                            <div className="relative overflow-hidden rounded-2xl border border-[#31334c] bg-[#1e1f29] p-6 shadow-lg md:p-10">
                                <h2 className="mb-12 text-center text-2xl font-bold text-white">
                                    Detail Invoice
                                </h2>

                                <div className="relative mx-auto max-w-3xl px-8 pt-6 pb-20 md:px-12">
                                    {/* The Line Container itself is the anchor */}
                                    <div className="relative z-0 h-1.5 w-full rounded-full bg-[#31334c]">
                                        {/* Animated Progress Line Foreground */}
                                        <div
                                            className="absolute top-0 left-0 z-0 h-full rounded-full bg-[#4ade80] transition-all duration-700 ease-in-out"
                                            style={{
                                                width: `${Math.max(0, ((animatedStatus - 1) / 3) * 100)}%`,
                                            }}
                                        ></div>

                                        {/* Step 1: Transaksi Dibuat */}
                                        <div className="absolute top-1/2 left-[0%] z-10 flex w-24 -translate-x-1/2 -translate-y-1/2 flex-col items-center md:w-32">
                                            <div
                                                className={`flex h-10 w-10 items-center justify-center rounded-xl transition-all delay-100 duration-500 ${animatedStatus >= 1 ? 'bg-[#4ade80] text-[#1e1f29]' : 'bg-[#31334c] text-gray-400'}`}
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
                                            </div>
                                            <span
                                                className={`absolute top-12 w-full text-center text-[10px] font-semibold transition-colors duration-500 md:top-14 md:text-sm ${animatedStatus >= 1 ? 'text-white' : 'text-gray-400'}`}
                                            >
                                                Transaksi Dibuat
                                            </span>
                                        </div>

                                        {/* Step 2: Pembayaran */}
                                        <div className="absolute top-1/2 left-[33.333%] z-10 flex w-24 -translate-x-1/2 -translate-y-1/2 flex-col items-center md:w-32">
                                            <div
                                                className={`flex h-10 w-10 items-center justify-center rounded-xl transition-all delay-100 duration-500 ${animatedStatus >= 2 ? 'bg-[#4ade80] text-[#1e1f29]' : 'bg-[#31334c] text-gray-400'}`}
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
                                                    <rect
                                                        width="20"
                                                        height="14"
                                                        x="2"
                                                        y="5"
                                                        rx="2"
                                                    />
                                                    <line
                                                        x1="2"
                                                        x2="22"
                                                        y1="10"
                                                        y2="10"
                                                    />
                                                </svg>
                                            </div>
                                            <span
                                                className={`absolute top-12 w-full text-center text-[10px] font-semibold transition-colors duration-500 md:top-14 md:text-sm ${animatedStatus >= 2 ? 'text-white' : 'text-gray-400'}`}
                                            >
                                                Pembayaran
                                            </span>
                                        </div>

                                        {/* Step 3: Sedang di Proses */}
                                        <div className="absolute top-1/2 left-[66.666%] z-10 flex w-24 -translate-x-1/2 -translate-y-1/2 flex-col items-center md:w-32">
                                            <div
                                                className={`flex h-10 w-10 items-center justify-center rounded-xl transition-all delay-100 duration-500 ${animatedStatus >= 3 ? 'bg-[#4ade80] text-[#1e1f29]' : 'bg-[#31334c] text-gray-400'}`}
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
                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="3"
                                                    />
                                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                                </svg>
                                            </div>
                                            <span
                                                className={`absolute top-12 w-full text-center text-[10px] font-semibold transition-colors duration-500 md:top-14 md:text-sm ${animatedStatus >= 3 ? 'text-white' : 'text-gray-400'}`}
                                            >
                                                Sedang di Proses
                                            </span>
                                        </div>

                                        {/* Step 4: Transaksi Selesai */}
                                        <div className="absolute top-1/2 left-[100%] z-10 flex w-24 -translate-x-1/2 -translate-y-1/2 flex-col items-center md:w-32">
                                            <div
                                                className={`flex h-10 w-10 items-center justify-center rounded-xl shadow-[0_0_15px_rgba(74,222,128,0.3)] transition-all delay-100 duration-500 ${animatedStatus >= 4 ? 'bg-[#4ade80] text-[#1e1f29]' : 'bg-[#31334c] text-gray-400'}`}
                                            >
                                                <svg
                                                    width="24"
                                                    height="24"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    strokeWidth="3"
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                >
                                                    <polyline points="20 6 9 17 4 12" />
                                                </svg>
                                            </div>
                                            <span
                                                className={`absolute top-12 w-full text-center text-[10px] font-semibold transition-colors duration-500 md:top-14 md:text-sm ${animatedStatus >= 4 ? 'text-[#4ade80]' : 'text-gray-400'}`}
                                            >
                                                Transaksi Selesai
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Account Info Card */}
                            <div className="relative z-10 mt-26 flex min-h-[140px] flex-col items-center gap-8 overflow-visible rounded-2xl border border-[#31334c] bg-[#242533] p-6 shadow-lg md:flex-row md:items-stretch">
                                {/* Game Card Component - Overlapping Top */}
                                <div className="relative -mt-20 flex shrink-0 justify-center md:-mt-32 md:mb-0 md:w-40">
                                    <GameCard
                                        cardSize="sm"
                                        title={invoiceData.game.name}
                                        subTitle={invoiceData.game.publisher}
                                        imgSrc={
                                            'storage/' + invoiceData.game.image
                                        }
                                        active={true}
                                        slug={invoiceData.game.slug}
                                        customClass="!m-0"
                                    />
                                </div>

                                {/* Content Grid */}
                                <div className="relative grid w-full flex-1 grid-cols-1 gap-6 md:grid-cols-2">
                                    {/* Success Badge (Top Right) */}
                                    {invoiceData.status.toLowerCase() ===
                                        'success' && (
                                        <div className="absolute top-0 right-0 z-20 hidden md:block">
                                            <span className="rounded-full border border-[#4ade80]/50 bg-[#2e603a] px-4 py-1.5 text-xs font-bold text-[#4ade80] shadow-[0_0_10px_rgba(74,222,128,0.2)]">
                                                Pesanan telah selesai.
                                            </span>
                                        </div>
                                    )}

                                    {/* Informasi Akun */}
                                    <div className="flex flex-col pt-2">
                                        <h3 className="mb-4 text-lg font-bold text-white">
                                            Informasi Akun
                                        </h3>
                                        <div className="grid grid-cols-[100px_10px_1fr] gap-y-2 text-sm text-gray-300">
                                            <span className="font-semibold text-white">
                                                Username
                                            </span>
                                            <span>:</span>
                                            <span>
                                                {invoiceData.account.username}
                                            </span>

                                            <span className="font-semibold text-white">
                                                ID
                                            </span>
                                            <span>:</span>
                                            <span>
                                                {invoiceData.account.id}
                                            </span>

                                            <span className="font-semibold text-white">
                                                Server
                                            </span>
                                            <span>:</span>
                                            <span>
                                                {invoiceData.account.server}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Jenis Pembelian */}
                                    <div className="relative flex flex-col border-[#31334c] pt-2 md:border-l md:pl-6">
                                        <p className="mb-4 text-sm text-gray-400">
                                            {invoiceData.created_at}
                                        </p>

                                        <h3 className="mb-2 text-sm font-bold text-white">
                                            Jenis Pembelian
                                        </h3>
                                        <div className="flex items-center gap-2">
                                            <div>
                                                <p className="text-base leading-tight font-bold text-[#FFC107]">
                                                    {invoiceData.product.name}
                                                </p>
                                                <p className="text-xs text-gray-400">
                                                    {invoiceData.product.extra}
                                                </p>
                                            </div>
                                            <div className="ml-auto">
                                                <img
                                                    src="https://cdns.iconmonstr.com/wp-content/releases/preview/2012/240/iconmonstr-diamond-1.png"
                                                    alt="Diamond"
                                                    className="h-8 w-8 opacity-90 hue-rotate-[180deg] invert-[0.8] saturate-[3] sepia-[1]"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Rincian Pembayaran (Sembunyikan jika status success / Selesai) */}
                            {invoiceData.status.toLowerCase() !== 'success' && (
                                <div className="overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29] shadow-lg">
                                    {/* Accordion Header */}
                                    <div
                                        className="flex cursor-pointer items-center justify-between border-b border-[#31334c] bg-white/5 px-6 py-4"
                                        onClick={() =>
                                            setIsPaymentOpen((prev) => !prev)
                                        }
                                    >
                                        <h3 className="font-bold text-gray-300">
                                            Rincian Pembayaran
                                        </h3>
                                        <svg
                                            width="20"
                                            height="20"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            className={`text-gray-400 transition-transform duration-300 ${isPaymentOpen ? 'rotate-0' : 'rotate-180'}`}
                                        >
                                            <path d="m18 15-6-6-6 6" />
                                        </svg>
                                    </div>

                                    {isPaymentOpen && (
                                        <div className="flex flex-col gap-6 p-6 text-sm md:p-8">
                                            <div className="grid grid-cols-1 gap-x-4 gap-y-4 border-b border-[#31334c] pb-6 sm:grid-cols-2">
                                                <div className="flex items-center gap-2 font-semibold text-white">
                                                    Nomor Invoice
                                                </div>
                                                <div className="flex items-center gap-2 text-gray-300 sm:justify-end sm:text-right">
                                                    {invoiceData.invoice_no}
                                                    <button
                                                        className="text-gray-400 transition hover:text-white"
                                                        title="Copy"
                                                    >
                                                        <svg
                                                            width="14"
                                                            height="14"
                                                            viewBox="0 0 24 24"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            strokeWidth="2"
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                        >
                                                            <rect
                                                                width="14"
                                                                height="14"
                                                                x="8"
                                                                y="8"
                                                                rx="2"
                                                                ry="2"
                                                            />
                                                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <div className="font-semibold text-white">
                                                    Metode Pembayaran
                                                </div>
                                                <div className="text-gray-300 sm:text-right">
                                                    {invoiceData.method}
                                                </div>

                                                <div className="font-semibold text-white">
                                                    Status Pembayaran
                                                </div>
                                                <div className="sm:text-right">
                                                    {/* <span className="bg-[#4ade80] text-[#1e1f29] font-bold text-[10px] px-2 py-0.5 rounded uppercase">{invoiceData.status.toLowerCase() === 'success' ? 'PAID' : invoiceData.status}</span> */}
                                                    <span
                                                        className={`${paymentBadge.className} rounded px-2 py-0.5 text-[10px] font-bold uppercase`}
                                                    >
                                                        {paymentBadge.label}
                                                    </span>
                                                </div>

                                                <div className="font-semibold text-white">
                                                    Status Transaksi
                                                </div>
                                                <div className="sm:text-right">
                                                    {/* <span className="bg-[#4ade80] text-[#1e1f29] font-bold text-[10px] px-2 py-0.5 rounded uppercase">{invoiceData.status}</span> */}
                                                    <span
                                                        className={`${transactionBadge.className} rounded px-2 py-0.5 text-[10px] font-bold uppercase`}
                                                    >
                                                        {transactionBadge.label}
                                                    </span>
                                                </div>

                                                <div className="font-semibold text-white">
                                                    Pesan
                                                </div>
                                                <div className="text-gray-300 sm:text-right">
                                                    Transaksi berhasil pada{' '}
                                                    {invoiceData.paid_at}
                                                </div>
                                            </div>

                                            <div className="mt-2 grid grid-cols-2 gap-y-4 border-b border-[#31334c] pb-6">
                                                <div className="font-semibold text-white">
                                                    Harga
                                                </div>
                                                <div className="text-right text-gray-300">
                                                    Rp{' '}
                                                    {invoiceData.price.toLocaleString(
                                                        'id-ID',
                                                    )}
                                                </div>

                                                <div className="font-semibold text-white">
                                                    Jumlah
                                                </div>
                                                <div className="text-right text-gray-300">
                                                    x{invoiceData.qty}
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 gap-y-4 pb-2">
                                                <div className="font-semibold text-white">
                                                    Subtotal
                                                </div>
                                                <div className="text-right text-gray-300">
                                                    Rp{' '}
                                                    {(
                                                        invoiceData.price *
                                                        invoiceData.qty
                                                    ).toLocaleString('id-ID')}
                                                </div>

                                                <div className="font-semibold text-white">
                                                    Biaya Layanan
                                                </div>
                                                <div className="text-right text-gray-300">
                                                    Rp{' '}
                                                    {invoiceData.fee.toLocaleString(
                                                        'id-ID',
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Total Pembayaran Box */}
                            <div className="flex items-center justify-between rounded-xl border border-[#31334c] bg-[#1e1f29] px-6 py-4 shadow-lg">
                                <span className="text-sm font-bold text-white md:text-base">
                                    Total Pembayaran
                                </span>
                                <div className="flex items-center gap-3">
                                    <span className="text-lg font-black text-[#FFC107] md:text-xl">
                                        Rp.{' '}
                                        {invoiceData.total.toLocaleString(
                                            'id-ID',
                                        )}
                                    </span>
                                    <button
                                        className="text-gray-400 transition hover:text-white"
                                        title="Copy"
                                    >
                                        <svg
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
                                                width="14"
                                                height="14"
                                                x="8"
                                                y="8"
                                                rx="2"
                                                ry="2"
                                            />
                                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {/* Beli Lagi Banner/Button ATAU Bayar Sekarang */}
                            {invoiceData.status.toLowerCase() === 'pending' ? (
                                <a
                                    href={invoiceData.payment_url}
                                    className="w-full"
                                >
                                    <div className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#4ade80] px-6 py-4 font-bold text-[#1e1f29] shadow-[0_0_20px_rgba(74,222,128,0.3)] transition hover:bg-[#34d399]">
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
                                            <circle cx="12" cy="12" r="10" />
                                            <path d="M12 8v4l3 3" />
                                        </svg>
                                        Bayar Sekarang
                                    </div>
                                </a>
                            ) : (
                                <Link href="/">
                                    <div className="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary to-[#9b4dec] px-6 py-4 font-bold text-white shadow-[0_0_20px_rgba(168,85,247,0.3)] transition hover:opacity-90">
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
                                        Beli Lagi
                                    </div>
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </GuestLayout>
    );
}
