import React, { useState, useEffect } from 'react';
import { Head, useForm, Link, router } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';
import GameCard from '@/components/game-card';

interface InvoiceSearchProps {
    initialInvoiceData?: any;
    searchedInvoiceId?: string;
}

export default function InvoiceSearch({ initialInvoiceData = null, searchedInvoiceId = '' }: InvoiceSearchProps) {
    const { data, setData, get, processing, errors } = useForm({
        invoice_id: searchedInvoiceId,
    });

    const [invoiceData, setInvoiceData] = useState<any>(initialInvoiceData);
    const [animatedStatus, setAnimatedStatus] = useState<number>(0);

    // Sync newly grabbed server data
    useEffect(() => {
        setInvoiceData(initialInvoiceData);
    }, [initialInvoiceData]);
    
    // Auto-polling effect for real-time updates
    useEffect(() => {
        let pollInterval: NodeJS.Timeout;
        
        // Only poll if we have an active invoice displayed and it's not yet successful/failed
        if (invoiceData && !['success', 'failed'].includes(invoiceData.status.toLowerCase())) {
            pollInterval = setInterval(() => {
                router.reload({ only: ['initialInvoiceData'] });
            }, 5000); // Poll every 5 seconds
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

    // Animate progress bar incrementally when invoice is loaded
    useEffect(() => {
        if (invoiceData) {
            let targetStep = 0;
            const statusLower = invoiceData.status.toLowerCase();
            switch(statusLower) {
                case 'pending': targetStep = 1; break;
                case 'paid': targetStep = 2; break;
                case 'processing': targetStep = 3; break;
                case 'success': targetStep = 4; break;
                // 'failed' might remain at step 0 or step 1
                default: targetStep = 0;
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
                    setAnimatedStatus(prev => {
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

            <div className="relative min-h-[calc(100vh-106px)] flex items-center justify-center py-20 overflow-hidden">
                {/* Dotted Texture Background - Top Right */}
                <div className="absolute top-0 right-0 w-96 h-96 opacity-[0.03] bg-[radial-gradient(#fff_2px,transparent_2px)] [background-size:24px_24px] [mask-image:radial-gradient(ellipse_at_top_right,black_10%,transparent_70%)] pointer-events-none"></div>
                
                {/* Dotted Texture Background - Bottom Left */}
                <div className="absolute bottom-0 left-0 w-96 h-96 opacity-[0.03] bg-[radial-gradient(#fff_2px,transparent_2px)] [background-size:24px_24px] [mask-image:radial-gradient(ellipse_at_bottom_left,black_10%,transparent_70%)] pointer-events-none"></div>

                <div className="max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col items-center">
                    
                    {/* Tampilkan Header & Pencarian hanya jika TIDAK ada invoiceData */}
                    {!invoiceData && (
                        <>
                            {/* Header Texts */}
                            <div className="text-center mb-10">
                                <h1 className="text-3xl md:text-4xl font-bold text-white mb-4">
                                    Periksa Invoice Anda dengan <span className="text-[#FFC107]">Mudah dan Cepat</span>
                                </h1>
                                <p className="text-gray-300 text-sm md:text-base">
                                    Lihat detail pembelian anda menggunakan nomor Invoice.
                                </p>
                            </div>

                            {/* Search Box */}
                            <div className="w-full max-w-5xl bg-[#1e1f29] rounded-2xl overflow-hidden border border-[#31334c] shadow-2xl">
                                {/* Box Header */}
                                <div className="bg-white/10 px-6 py-4 border-b border-[#31334c]">
                                    <h2 className="text-white font-bold text-lg">Nomor Invoice</h2>
                                </div>
                                
                                {/* Box Body */}
                                <div className="p-6 md:p-8">
                                    <form onSubmit={submit} className="flex flex-col gap-6">
                                        <div>
                                            <input
                                                type="text"
                                                value={data.invoice_id}
                                                onChange={(e) => setData('invoice_id', e.target.value)}
                                                placeholder="Masukkan Nomor Invoice"
                                                className="w-full bg-[#1A1A24] text-white border border-[#31334c] rounded-lg focus:ring-primary focus:border-primary block p-4 outline-none transition placeholder-gray-500"
                                                required
                                            />
                                            {errors.invoice_id && (
                                                <p className="mt-2 text-sm text-red-500">{errors.invoice_id}</p>
                                            )}
                                        </div>
                                        
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full bg-gradient-to-r from-primary to-[#9b4dec] text-white font-bold text-lg py-4 px-6 rounded-lg hover:opacity-90 transition shadow-[0_0_20px_rgba(168,85,247,0.3)] disabled:opacity-50"
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
                        <div className="w-full max-w-5xl mt-10 flex flex-col gap-6 animate-fade-in-up">
                            
                            {/* Status Bar Card */}
                            <div className="bg-[#1e1f29] rounded-2xl border border-[#31334c] p-6 md:p-10 shadow-lg relative overflow-hidden">
                                <h2 className="text-2xl font-bold text-white text-center mb-12">Detail Invoice</h2>
                                
                                <div className="relative max-w-3xl mx-auto pt-6 pb-20 px-8 md:px-12">
                                    {/* The Line Container itself is the anchor */}
                                    <div className="relative w-full h-1.5 bg-[#31334c] rounded-full z-0">
                                        
                                        {/* Animated Progress Line Foreground */}
                                        <div 
                                            className="absolute top-0 left-0 h-full bg-[#4ade80] rounded-full z-0 transition-all duration-700 ease-in-out"
                                            style={{ width: `${Math.max(0, ((animatedStatus - 1) / 3) * 100)}%` }}
                                        ></div>
                                        
                                        {/* Step 1: Transaksi Dibuat */}
                                        <div className="absolute top-1/2 left-[0%] -translate-x-1/2 -translate-y-1/2 flex flex-col items-center z-10 w-24 md:w-32">
                                            <div className={`w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-500 delay-100 ${animatedStatus >= 1 ? 'bg-[#4ade80] text-[#1e1f29]' : 'bg-[#31334c] text-gray-400'}`}>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                            </div>
                                            <span className={`absolute top-12 md:top-14 w-full text-center text-[10px] md:text-sm font-semibold transition-colors duration-500 ${animatedStatus >= 1 ? 'text-white' : 'text-gray-400'}`}>Transaksi Dibuat</span>
                                        </div>

                                        {/* Step 2: Pembayaran */}
                                        <div className="absolute top-1/2 left-[33.333%] -translate-x-1/2 -translate-y-1/2 flex flex-col items-center z-10 w-24 md:w-32">
                                            <div className={`w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-500 delay-100 ${animatedStatus >= 2 ? 'bg-[#4ade80] text-[#1e1f29]' : 'bg-[#31334c] text-gray-400'}`}>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                            </div>
                                            <span className={`absolute top-12 md:top-14 w-full text-center text-[10px] md:text-sm font-semibold transition-colors duration-500 ${animatedStatus >= 2 ? 'text-white' : 'text-gray-400'}`}>Pembayaran</span>
                                        </div>

                                        {/* Step 3: Sedang di Proses */}
                                        <div className="absolute top-1/2 left-[66.666%] -translate-x-1/2 -translate-y-1/2 flex flex-col items-center z-10 w-24 md:w-32">
                                            <div className={`w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-500 delay-100 ${animatedStatus >= 3 ? 'bg-[#4ade80] text-[#1e1f29]' : 'bg-[#31334c] text-gray-400'}`}>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                            </div>
                                            <span className={`absolute top-12 md:top-14 w-full text-center text-[10px] md:text-sm font-semibold transition-colors duration-500 ${animatedStatus >= 3 ? 'text-white' : 'text-gray-400'}`}>Sedang di Proses</span>
                                        </div>

                                        {/* Step 4: Transaksi Selesai */}
                                        <div className="absolute top-1/2 left-[100%] -translate-x-1/2 -translate-y-1/2 flex flex-col items-center z-10 w-24 md:w-32">
                                            <div className={`w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-500 delay-100 shadow-[0_0_15px_rgba(74,222,128,0.3)] ${animatedStatus >= 4 ? 'bg-[#4ade80] text-[#1e1f29]' : 'bg-[#31334c] text-gray-400'}`}>
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            </div>
                                            <span className={`absolute top-12 md:top-14 w-full text-center text-[10px] md:text-sm font-semibold transition-colors duration-500 ${animatedStatus >= 4 ? 'text-[#4ade80]' : 'text-gray-400'}`}>Transaksi Selesai</span>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            {/* Account Info Card */}
                            <div className="bg-[#242533] rounded-2xl border border-[#31334c] p-6 mt-26 shadow-lg relative flex flex-col md:flex-row gap-8 items-center md:items-stretch overflow-visible min-h-[140px] z-10">
                                
                                {/* Game Card Component - Overlapping Top */}
                                <div className="md:w-40 md:-mt-32 md:mb-0 -mt-20 shrink-0 relative flex justify-center">
                                    <GameCard 
                                        cardSize="sm"
                                        title={invoiceData.game.name}
                                        subTitle={invoiceData.game.publisher}
                                        imgSrc={'storage/' + invoiceData.game.image}
                                        active={true}
                                        slug={invoiceData.game.slug}
                                        customClass="!m-0"
                                    />
                                </div>

                                {/* Content Grid */}
                                <div className="flex-1 w-full grid grid-cols-1 md:grid-cols-2 gap-6 relative">
                                    
                                    {/* Success Badge (Top Right) */}
                                    {invoiceData.status.toLowerCase() === 'success' && (
                                        <div className="absolute top-0 right-0 hidden md:block z-20">
                                            <span className="bg-[#2e603a] text-[#4ade80] text-xs font-bold px-4 py-1.5 rounded-full border border-[#4ade80]/50 shadow-[0_0_10px_rgba(74,222,128,0.2)]">
                                                Pesanan telah selesai.
                                            </span>
                                        </div>
                                    )}

                                    {/* Informasi Akun */}
                                    <div className="flex flex-col pt-2">
                                        <h3 className="text-lg font-bold text-white mb-4">Informasi Akun</h3>
                                        <div className="grid grid-cols-[100px_10px_1fr] gap-y-2 text-sm text-gray-300">
                                            <span className="font-semibold text-white">Username</span>
                                            <span>:</span>
                                            <span>{invoiceData.account.username}</span>

                                            <span className="font-semibold text-white">ID</span>
                                            <span>:</span>
                                            <span>{invoiceData.account.id}</span>

                                            <span className="font-semibold text-white">Server</span>
                                            <span>:</span>
                                            <span>{invoiceData.account.server}</span>
                                        </div>
                                    </div>

                                    {/* Jenis Pembelian */}
                                    <div className="flex flex-col pt-2 md:border-l border-[#31334c] md:pl-6 relative">
                                        <p className="text-sm text-gray-400 mb-4">{invoiceData.created_at}</p>
                                        
                                        <h3 className="text-sm font-bold text-white mb-2">Jenis Pembelian</h3>
                                        <div className="flex items-center gap-2">
                                            <div>
                                                <p className="text-[#FFC107] font-bold text-base leading-tight">{invoiceData.product.name}</p>
                                                <p className="text-gray-400 text-xs">{invoiceData.product.extra}</p>
                                            </div>
                                            <div className="ml-auto">
                                                <img src="https://cdns.iconmonstr.com/wp-content/releases/preview/2012/240/iconmonstr-diamond-1.png" alt="Diamond" className="w-8 h-8 opacity-90 invert-[0.8] sepia-[1] hue-rotate-[180deg] saturate-[3]" />
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            {/* Rincian Pembayaran (Sembunyikan jika status success / Selesai) */}
                            {invoiceData.status.toLowerCase() !== 'success' && (
                                <div className="bg-[#1e1f29] rounded-xl border border-[#31334c] overflow-hidden shadow-lg">
                                    {/* Accordion Header */}
                                    <div className="px-6 py-4 bg-white/5 border-b border-[#31334c] flex justify-between items-center cursor-pointer">
                                        <h3 className="font-bold text-gray-300">Rincian Pembayaran</h3>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400"><path d="m18 15-6-6-6 6"/></svg>
                                    </div>
                                    
                                    <div className="p-6 md:p-8 flex flex-col gap-6 text-sm">
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-4 border-b border-[#31334c] pb-6">
                                            <div className="text-white font-semibold flex items-center gap-2">Nomor Invoice</div>
                                            <div className="text-gray-300 sm:text-right flex items-center sm:justify-end gap-2">
                                                {invoiceData.invoice_no}
                                                <button className="text-gray-400 hover:text-white transition" title="Copy"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg></button>
                                            </div>

                                            <div className="text-white font-semibold">Metode Pembayaran</div>
                                            <div className="text-gray-300 sm:text-right">{invoiceData.method}</div>

                                            <div className="text-white font-semibold">Status Pembayaran</div>
                                            <div className="sm:text-right">
                                                <span className="bg-[#4ade80] text-[#1e1f29] font-bold text-[10px] px-2 py-0.5 rounded uppercase">{invoiceData.status.toLowerCase() === 'success' ? 'PAID' : invoiceData.status}</span>
                                            </div>

                                            <div className="text-white font-semibold">Status Transaksi</div>
                                            <div className="sm:text-right">
                                                <span className="bg-[#4ade80] text-[#1e1f29] font-bold text-[10px] px-2 py-0.5 rounded uppercase">{invoiceData.status}</span>
                                            </div>

                                            <div className="text-white font-semibold">Pesan</div>
                                            <div className="text-gray-300 sm:text-right">Transaksi berhasil pada {invoiceData.paid_at}</div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-y-4 border-b border-[#31334c] pb-6 mt-2">
                                            <div className="text-white font-semibold">Harga</div>
                                            <div className="text-gray-300 text-right">Rp {invoiceData.price.toLocaleString('id-ID')}</div>

                                            <div className="text-white font-semibold">Jumlah</div>
                                            <div className="text-gray-300 text-right">x{invoiceData.qty}</div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-y-4 pb-2">
                                            <div className="text-white font-semibold">Subtotal</div>
                                            <div className="text-gray-300 text-right">Rp {(invoiceData.price * invoiceData.qty).toLocaleString('id-ID')}</div>

                                            <div className="text-white font-semibold">Biaya Layanan</div>
                                            <div className="text-gray-300 text-right">Rp {invoiceData.fee.toLocaleString('id-ID')}</div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Total Pembayaran Box */}
                            <div className="bg-[#1e1f29] rounded-xl border border-[#31334c] px-6 py-4 flex justify-between items-center shadow-lg">
                                <span className="text-white font-bold text-sm md:text-base">Total Pembayaran</span>
                                <div className="flex items-center gap-3">
                                    <span className="text-[#FFC107] font-black text-lg md:text-xl">Rp. {invoiceData.total.toLocaleString('id-ID')}</span>
                                    <button className="text-gray-400 hover:text-white transition" title="Copy"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg></button>
                                </div>
                            </div>

                            {/* Beli Lagi Banner/Button ATAU Bayar Sekarang */}
                            {invoiceData.status.toLowerCase() === 'pending' ? (
                                <a href={invoiceData.payment_url} className="w-full">
                                    <div className="w-full bg-[#4ade80] text-[#1e1f29] font-bold py-4 px-6 rounded-xl hover:bg-[#34d399] transition shadow-[0_0_20px_rgba(74,222,128,0.3)] flex justify-center items-center gap-2">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                                        Bayar Sekarang
                                    </div>
                                </a>
                            ) : (
                                <Link href="/">
                                    <div className="w-full bg-gradient-to-r from-primary to-[#9b4dec] text-white font-bold py-4 px-6 rounded-xl hover:opacity-90 transition shadow-[0_0_20px_rgba(168,85,247,0.3)] flex justify-center items-center gap-2">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
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
