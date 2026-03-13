import React, { useState, useEffect } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import axios from 'axios';
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
    const [remainingSeconds, setRemainingSeconds] = useState<number | null>(null);

    // Review state
    const [hasReviewed, setHasReviewed] = useState<boolean>(initialInvoiceData?.has_reviewed ?? false);
    const [reviewRating, setReviewRating] = useState<number>(0);
    const [reviewTags, setReviewTags] = useState<string[]>([]);
    const [isSubmittingReview, setIsSubmittingReview] = useState(false);
    const [reviewDone, setReviewDone] = useState(false);
    const [showReviewModal, setShowReviewModal] = useState(false);

    const REVIEW_TAGS = ['Proses Cepat', 'Terpercaya', 'Harga Terjangkau', 'Direkomendasikan', 'Pelayanan Ramah'];

    const toggleReviewTag = (tag: string) => {
        setReviewTags(prev => prev.includes(tag) ? prev.filter(t => t !== tag) : [...prev, tag]);
    };

    const submitReview = async () => {
        if (reviewRating === 0 || isSubmittingReview) return;
        setIsSubmittingReview(true);
        try {
            await axios.post('/api/review', {
                invoice_id: invoiceData.invoice_no,
                rating: reviewRating,
                tags: reviewTags,
            });
            setReviewDone(true);
            setHasReviewed(true);
        } catch (error: any) {
            alert(error.response?.data?.message || 'Gagal mengirim ulasan.');
        } finally {
            setIsSubmittingReview(false);
        }
    };

    // Sync newly grabbed server data
    useEffect(() => {
        setInvoiceData(initialInvoiceData);
    }, [initialInvoiceData]);

    // Auto-open review modal when status becomes success
    useEffect(() => {
        if (
            invoiceData?.status?.toLowerCase() === 'success' &&
            invoiceData?.type === 'transaction' &&
            !hasReviewed &&
            !reviewDone
        ) {
            setShowReviewModal(true);
        }
    }, [invoiceData?.status]);

    // Auto-polling effect for real-time updates
    useEffect(() => {
        let pollInterval: ReturnType<typeof setInterval> | undefined;

        // Only poll if we have an active invoice displayed and it's not yet successful/failed
        if (
            invoiceData &&
            !['success', 'failed', 'expired', 'canceled'].includes(
                invoiceData.status.toLowerCase(),
            )
        ) {
            pollInterval = setInterval(() => {
                axios
                    .get('/invoice/data', {
                        params: { invoice_id: invoiceData.invoice_no },
                    })
                    .then((response) => {
                        if (response.data?.success && response.data?.data) {
                            setInvoiceData(response.data.data);
                        }
                    })
                    .catch(() => {
                        // Silently ignore polling errors and retry on next tick.
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

    const copyToClipboard = async (value: string | number) => {
        if (!navigator?.clipboard) return;

        try {
            await navigator.clipboard.writeText(String(value));
        } catch {
            // Ignore clipboard errors silently.
        }
    };

    const parseInvoiceDate = (dateString?: string | null): Date | null => {
        if (!dateString) return null;

        const monthMap: Record<string, number> = {
            jan: 0,
            feb: 1,
            mar: 2,
            apr: 3,
            may: 4,
            mei: 4,
            jun: 5,
            jul: 6,
            aug: 7,
            agu: 7,
            sep: 8,
            oct: 9,
            okt: 9,
            nov: 10,
            dec: 11,
            des: 11,
        };

        const normalized = dateString.trim();
        const match = normalized.match(
            /^(\d{1,2})\s+([A-Za-z]{3})\s+(\d{4})\s+(\d{2}):(\d{2}):(\d{2})$/,
        );

        if (!match) {
            const fallbackDate = new Date(normalized);
            return Number.isNaN(fallbackDate.getTime()) ? null : fallbackDate;
        }

        const [, day, monthText, year, hour, minute, second] = match;
        const monthIndex = monthMap[monthText.toLowerCase()];
        if (monthIndex === undefined) return null;

        return new Date(
            Number(year),
            monthIndex,
            Number(day),
            Number(hour),
            Number(minute),
            Number(second),
        );
    };

    const formatCountdown = (totalSeconds: number) => {
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        return `${hours} Jam ${minutes} Menit ${seconds} Detik`;
    };

    const formatCountdownCompact = (totalSeconds: number) => {
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        return [hours, minutes, seconds]
            .map((value) => String(value).padStart(2, '0'))
            .join(':');
    };

    const lastInvoiceNoRef = React.useRef<string>('');
    const animationIntervalRef = React.useRef<ReturnType<
        typeof setInterval
    > | null>(null);

    const paymentBadge = getPaymentStatusBadge(invoiceData?.payment_status);
    const transactionBadge = getTransactionStatusBadge(invoiceData?.status);
    const isCoinTopup = invoiceData?.type === 'coin_topup';

    // Animate progress bar incrementally when invoice status changes
    useEffect(() => {
        if (!invoiceData) {
            setAnimatedStatus(0);
            lastInvoiceNoRef.current = '';
            if (animationIntervalRef.current) {
                clearInterval(animationIntervalRef.current);
                animationIntervalRef.current = null;
            }
            return;
        }

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
            default:
                targetStep = 0;
        }

        // If it's a completely different invoice, reset animation to 0
        const isNewInvoice =
            invoiceData.invoice_no !== lastInvoiceNoRef.current;
        if (isNewInvoice) {
            lastInvoiceNoRef.current = invoiceData.invoice_no;
            setAnimatedStatus(0);
            if (animationIntervalRef.current) {
                clearInterval(animationIntervalRef.current);
                animationIntervalRef.current = null;
            }
        }

        // Animate if target is higher than current animated status
        // Use a small delay for new invoices to ensure state is updated
        const animateDelay = isNewInvoice ? 50 : 0;
        const timeoutId = setTimeout(() => {
            setAnimatedStatus((currentStatus) => {
                // Clear previous interval if exists
                if (animationIntervalRef.current) {
                    clearInterval(animationIntervalRef.current);
                }

                if (targetStep > currentStatus) {
                    // Start animation from current to target
                    animationIntervalRef.current = setInterval(() => {
                        setAnimatedStatus((prev) => {
                            if (prev < targetStep) {
                                return prev + 1;
                            } else {
                                if (animationIntervalRef.current) {
                                    clearInterval(animationIntervalRef.current);
                                    animationIntervalRef.current = null;
                                }
                                return prev;
                            }
                        });
                    }, 500); // animate every 500ms

                    return currentStatus; // Return current before animation starts
                } else if (targetStep < currentStatus) {
                    // Reset if target is lower
                    return targetStep;
                }
                return currentStatus;
            });
        }, animateDelay);

        // Cleanup interval and timeout on unmount or status change
        return () => {
            clearTimeout(timeoutId);
            if (animationIntervalRef.current) {
                clearInterval(animationIntervalRef.current);
                animationIntervalRef.current = null;
            }
        };
    }, [invoiceData?.status, invoiceData?.invoice_no]);

    useEffect(() => {
        if (!invoiceData?.status) {
            setRemainingSeconds(null);
            return;
        }

        const statusLower = String(invoiceData.status).toLowerCase();
        if (['success', 'failed', 'canceled'].includes(statusLower)) {
            setRemainingSeconds(null);
            return;
        }

        const expiredUnix = Number(invoiceData?.expired_at_unix ?? 0);
        const expiredMs =
            expiredUnix > 0
                ? expiredUnix * 1000
                : (parseInvoiceDate(invoiceData?.expired_at)?.getTime() ?? 0);

        if (!expiredMs) {
            setRemainingSeconds(null);
            return;
        }

        const updateCountdown = () => {
            const diffSeconds = Math.max(
                0,
                Math.floor((expiredMs - Date.now()) / 1000),
            );
            setRemainingSeconds(diffSeconds);
        };

        updateCountdown();
        const intervalId = setInterval(updateCountdown, 1000);

        return () => clearInterval(intervalId);
    }, [
        invoiceData?.expired_at,
        invoiceData?.expired_at_unix,
        invoiceData?.status,
        invoiceData?.invoice_no,
    ]);

    return (
        <GuestLayout>
            <Head title="Cek Invoice" />

            <div className="relative flex min-h-[calc(100vh-106px)] items-center justify-center overflow-hidden py-10 md:py-20">
                {/* Dotted Texture Background - Top Right */}
                <div className="pointer-events-none absolute top-0 right-0 h-96 w-96 bg-[radial-gradient(#fff_2px,transparent_2px)] [mask-image:radial-gradient(ellipse_at_top_right,black_10%,transparent_70%)] [background-size:24px_24px] opacity-[0.03]"></div>

                {/* Dotted Texture Background - Bottom Left */}
                <div className="pointer-events-none absolute bottom-0 left-0 h-96 w-96 bg-[radial-gradient(#fff_2px,transparent_2px)] [mask-image:radial-gradient(ellipse_at_bottom_left,black_10%,transparent_70%)] [background-size:24px_24px] opacity-[0.03]"></div>

                <div className="relative z-10 mx-auto flex w-full max-w-5xl flex-col items-center px-3 sm:px-6 lg:px-8">
                    {/* Tampilkan Header & Pencarian hanya jika TIDAK ada invoiceData */}
                    {!invoiceData && (
                        <>
                            {/* Header Texts */}
                            <div className="mb-6 text-center md:mb-10">
                                <h1 className="mb-3 text-2xl font-bold text-white md:mb-4 md:text-4xl">
                                    Periksa Invoice Anda dengan{' '}
                                    <span className="text-[#FFC107]">
                                        Mudah dan Cepat
                                    </span>
                                </h1>
                                <p className="text-xs text-gray-300 md:text-base">
                                    Lihat detail pembelian anda menggunakan
                                    nomor Invoice.
                                </p>
                            </div>

                            {/* Search Box */}
                            <div className="w-full max-w-5xl overflow-hidden rounded-2xl border border-[#31334c] bg-[#1e1f29] shadow-2xl">
                                <div className="border-b border-[#31334c] bg-white/10 px-6 py-4">
                                    <h2 className="text-lg font-bold text-white">
                                        Nomor Invoice
                                    </h2>
                                </div>

                                {/* Box Body */}
                                <div className="p-4 md:p-8">
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
                        <div className="animate-fade-in-up mt-6 flex w-full max-w-5xl flex-col gap-4 md:mt-10 md:gap-6">
                            {/* Status Bar Card */}
                            <div className="relative overflow-hidden rounded-2xl border border-[#31334c] bg-[#1e1f29] p-4 shadow-lg md:p-10">
                                <h2 className="mb-8 text-center text-lg font-bold text-white md:mb-12 md:text-2xl">
                                    Detail Invoice
                                </h2>

                                <div className="relative mx-auto max-w-3xl px-4 pt-4 pb-16 md:px-12 md:pt-6 md:pb-20">
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
                                        <div className="absolute top-1/2 left-[0%] z-10 flex w-16 -translate-x-1/2 -translate-y-1/2 flex-col items-center md:w-32">
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
                                        <div className="absolute top-1/2 left-[33.333%] z-10 flex w-16 -translate-x-1/2 -translate-y-1/2 flex-col items-center md:w-32">
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
                                        <div className="absolute top-1/2 left-[66.666%] z-10 flex w-16 -translate-x-1/2 -translate-y-1/2 flex-col items-center md:w-32">
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
                                        <div className="absolute top-1/2 left-[100%] z-10 flex w-16 -translate-x-1/2 -translate-y-1/2 flex-col items-center md:w-32">
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

                            {(() => {
                                const statusLower = String(
                                    invoiceData.status,
                                ).toLowerCase();
                                const paymentStatusLower = String(
                                    invoiceData.payment_status,
                                ).toLowerCase();

                                const showTimerBadge =
                                    ['pending', 'expired'].includes(
                                        statusLower,
                                    ) && remainingSeconds !== null;
                                const showPaidBadge =
                                    statusLower === 'success' ||
                                    paymentStatusLower === 'paid';

                                if (!showTimerBadge && !showPaidBadge) {
                                    return null;
                                }

                                return (
                                    <div className="mt-8 flex w-full justify-end md:mt-10">
                                        <span
                                            className={`inline-flex max-w-full items-center gap-1 rounded-full border px-2.5 py-1 text-[10px] font-semibold whitespace-nowrap md:gap-1.5 md:px-4.5 md:py-2 md:text-sm ${showPaidBadge ? 'border-[#4ade80]/40 bg-[#4ade80]/15 text-[#7ff7b1]' : remainingSeconds! > 0 ? 'border-[#ef4b9a]/40 bg-[#ef4b9a]/15 text-[#ffb3d7]' : 'border-red-500/40 bg-red-500/15 text-red-300'}`}
                                        >
                                            <svg
                                                width="12"
                                                height="12"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                className="md:h-[14px] md:w-[14px]"
                                            >
                                                {showPaidBadge ? (
                                                    <polyline points="20 6 9 17 4 12" />
                                                ) : (
                                                    <>
                                                        <circle
                                                            cx="12"
                                                            cy="12"
                                                            r="10"
                                                        />
                                                        <path d="M12 6v6l4 2" />
                                                    </>
                                                )}
                                            </svg>
                                            {showPaidBadge ? (
                                                'Sudah Lunas'
                                            ) : remainingSeconds! > 0 ? (
                                                <>
                                                    <span className="md:hidden">
                                                        {formatCountdownCompact(
                                                            remainingSeconds!,
                                                        )}
                                                    </span>
                                                    <span className="hidden md:inline">
                                                        {formatCountdown(
                                                            remainingSeconds!,
                                                        )}
                                                    </span>
                                                </>
                                            ) : (
                                                'Waktu Habis'
                                            )}
                                        </span>
                                    </div>
                                );
                            })()}

                            {/* Account Info Card */}
                            <div className="relative z-10 mt-3 flex min-h-[140px] flex-col items-center gap-4 overflow-visible rounded-2xl border border-[#31334c] bg-[#242533] p-4 shadow-lg md:mt-5 md:flex-row md:items-stretch md:gap-8 md:p-6">
                                {/* Game Card Component - Overlapping Top */}
                                <div className="relative -mt-16 flex shrink-0 justify-center md:-mt-32 md:mb-0 md:w-40">
                                    <GameCard
                                        cardSize="sm"
                                        title={invoiceData.game.name}
                                        subTitle={invoiceData.game.publisher}
                                        imgSrc={
                                            String(
                                                invoiceData.game.image,
                                            ).startsWith('/')
                                                ? invoiceData.game.image
                                                : 'storage/' +
                                                  invoiceData.game.image
                                        }
                                        active={true}
                                        slug={invoiceData.game.slug || '#'}
                                        customClass="!m-0 !w-24 !h-[140px] md:!w-auto md:!h-auto"
                                    />
                                </div>

                                {/* Content Grid */}
                                <div className="relative grid w-full flex-1 grid-cols-1 gap-6 md:grid-cols-2">
                                    {/* Success Badge (Top Right) */}
                                    {invoiceData.status.toLowerCase() ===
                                        'success' && (
                                        <div className="absolute top-0 right-0 z-20">
                                            <span className="rounded-full border border-[#4ade80]/50 bg-[#2e603a] px-4 py-1.5 text-xs font-bold text-[#4ade80] shadow-[0_0_10px_rgba(74,222,128,0.2)]">
                                                Pesanan telah selesai.
                                            </span>
                                        </div>
                                    )}

                                    {/* Informasi Akun */}
                                    <div className="flex flex-col pt-0 md:pt-2">
                                        <h3 className="mb-3 text-base font-bold text-white md:mb-4 md:text-lg">
                                            Informasi Akun
                                        </h3>
                                        <div className="grid grid-cols-[80px_10px_1fr] gap-y-1.5 text-xs text-gray-300 md:grid-cols-[100px_10px_1fr] md:gap-y-2 md:text-sm">
                                            <span className="font-semibold text-white">
                                                {isCoinTopup
                                                    ? 'Nama'
                                                    : 'Username'}
                                            </span>
                                            <span>:</span>
                                            <span>
                                                {invoiceData.account.username}
                                            </span>

                                            <span className="font-semibold text-white">
                                                {isCoinTopup ? 'Tipe' : 'ID'}
                                            </span>
                                            <span>:</span>
                                            <span>
                                                {invoiceData.account.id}
                                            </span>

                                            <span className="font-semibold text-white">
                                                {isCoinTopup
                                                    ? 'Channel'
                                                    : 'Server'}
                                            </span>
                                            <span>:</span>
                                            <span>
                                                {isCoinTopup
                                                    ? 'QRIS'
                                                    : invoiceData.account
                                                          .server}
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
                                                    src={
                                                        invoiceData.product
                                                            .icon_url ||
                                                        'https://cdns.iconmonstr.com/wp-content/releases/preview/2012/240/iconmonstr-diamond-1.png'
                                                    }
                                                    alt={
                                                        invoiceData.product.name
                                                    }
                                                    className={`h-8 w-8 opacity-90 ${invoiceData.product.icon_url ? 'object-contain' : 'hue-rotate-[180deg] invert-[0.8] saturate-[3] sepia-[1]'}`}
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
                                        <div className="flex flex-col gap-4 p-4 text-sm md:gap-6 md:p-8">
                                            <div className="grid grid-cols-1 gap-x-4 gap-y-4 border-b border-[#31334c] pb-6 sm:grid-cols-2">
                                                <div className="flex items-center gap-2 font-semibold text-white">
                                                    Nomor Invoice
                                                </div>
                                                <div className="flex items-center gap-2 text-gray-300 sm:justify-end sm:text-right">
                                                    <span className="break-all">
                                                        {invoiceData.invoice_no}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            copyToClipboard(
                                                                invoiceData.invoice_no,
                                                            )
                                                        }
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
                            <div className="flex flex-col gap-2 rounded-xl border border-[#31334c] bg-[#1e1f29] px-4 py-3 shadow-lg sm:flex-row sm:items-center sm:justify-between md:px-6 md:py-4">
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
                                        type="button"
                                        onClick={() =>
                                            copyToClipboard(invoiceData.total)
                                        }
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

                            {/* Panel Pembayaran / Beli Lagi */}
                            {invoiceData.status.toLowerCase() === 'pending' ? (
                                <div className="flex w-full flex-col gap-3">
                                    {/* Panel Pembayaran Langsung Tampil */}
                                    <div className="flex flex-col gap-4">
                                        {/* ===== QRIS ===== */}
                                        {!invoiceData.pay_url &&
                                            invoiceData.qr_url && (
                                                <div className="overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29]">
                                                    {/* Header */}
                                                    <div className="border-b border-[#31334c] bg-white/5 px-4 py-3 md:px-6">
                                                        <p className="font-bold text-gray-300">
                                                            Cara Pembayaran
                                                        </p>
                                                    </div>
                                                    {/* Body — instruksi kiri, QR kanan */}
                                                    <div className="flex flex-col gap-6 p-4 md:flex-row md:items-start md:gap-8 md:p-6">
                                                        {/* Instruksi */}
                                                        <div className="flex-1">
                                                            {invoiceData.instructions &&
                                                                invoiceData.instructions.map(
                                                                    (
                                                                        inst: any,
                                                                        i: number,
                                                                    ) => (
                                                                        <div
                                                                            key={
                                                                                i
                                                                            }
                                                                            className="mb-4"
                                                                        >
                                                                            <p className="mb-2 text-sm font-semibold text-gray-300">
                                                                                {
                                                                                    inst.title
                                                                                }
                                                                            </p>
                                                                            <ul className="flex flex-col gap-1.5">
                                                                                {inst.steps.map(
                                                                                    (
                                                                                        step: string,
                                                                                        j: number,
                                                                                    ) => (
                                                                                        <li
                                                                                            key={
                                                                                                j
                                                                                            }
                                                                                            className="flex items-start gap-2 text-xs text-gray-400"
                                                                                        >
                                                                                            <span className="mt-0.5 shrink-0 text-[#a855f7]">
                                                                                                •
                                                                                            </span>
                                                                                            <span
                                                                                                dangerouslySetInnerHTML={{
                                                                                                    __html: step,
                                                                                                }}
                                                                                            />
                                                                                        </li>
                                                                                    ),
                                                                                )}
                                                                            </ul>
                                                                        </div>
                                                                    ),
                                                                )}
                                                        </div>
                                                        {/* QR Code */}
                                                        <div className="flex shrink-0 flex-col items-center gap-3">
                                                            <div className="rounded-xl bg-white p-3 shadow-lg">
                                                                <img
                                                                    src={
                                                                        invoiceData.qr_url
                                                                    }
                                                                    alt="QR Code Pembayaran"
                                                                    className="h-44 w-44 object-contain"
                                                                />
                                                            </div>
                                                            <a
                                                                href={
                                                                    invoiceData.qr_url
                                                                }
                                                                download="qrcode-pembayaran.png"
                                                                className="w-full rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] px-4 py-2.5 text-center text-sm font-bold text-white transition hover:opacity-90"
                                                            >
                                                                Unduh Kode QR
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                        {/* ===== eWallet — ada pay_url ===== */}
                                        {invoiceData.pay_url && (
                                            <div className="overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29]">
                                                <div className="border-b border-[#31334c] bg-white/5 px-4 py-3 md:px-6">
                                                    <p className="font-bold text-gray-300">
                                                        Cara Pembayaran
                                                    </p>
                                                </div>
                                                <div className="flex flex-col gap-4 p-4 md:flex-row md:items-start md:gap-8 md:p-6">
                                                    {/* Instruksi */}
                                                    <div className="flex-1">
                                                        {invoiceData.instructions &&
                                                            invoiceData.instructions.map(
                                                                (
                                                                    inst: any,
                                                                    i: number,
                                                                ) => (
                                                                    <div
                                                                        key={i}
                                                                        className="mb-4"
                                                                    >
                                                                        <p className="mb-2 text-sm font-semibold text-gray-300">
                                                                            {
                                                                                inst.title
                                                                            }
                                                                        </p>
                                                                        <ul className="flex flex-col gap-1.5">
                                                                            {inst.steps.map(
                                                                                (
                                                                                    step: string,
                                                                                    j: number,
                                                                                ) => (
                                                                                    <li
                                                                                        key={
                                                                                            j
                                                                                        }
                                                                                        className="flex items-start gap-2 text-xs text-gray-400"
                                                                                    >
                                                                                        <span className="mt-0.5 shrink-0 text-[#a855f7]">
                                                                                            •
                                                                                        </span>
                                                                                        <span
                                                                                            dangerouslySetInnerHTML={{
                                                                                                __html: step,
                                                                                            }}
                                                                                        />
                                                                                    </li>
                                                                                ),
                                                                            )}
                                                                        </ul>
                                                                    </div>
                                                                ),
                                                            )}
                                                    </div>
                                                    {/* Tombol Bayar */}
                                                    <div className="flex shrink-0 flex-col items-center justify-center gap-3 md:min-w-[180px]">
                                                        <a
                                                            href={
                                                                invoiceData.pay_url
                                                            }
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="w-full rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] px-4 py-3 text-center font-bold text-white shadow-[0_0_20px_rgba(168,85,247,0.3)] transition hover:opacity-90"
                                                        >
                                                            Buka Aplikasi
                                                            Pembayaran
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* ===== Virtual Account / mBanking ===== */}
                                        {!invoiceData.pay_url &&
                                            !invoiceData.qr_url &&
                                            invoiceData.pay_code && (
                                                <div className="overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29]">
                                                    <div className="border-b border-[#31334c] bg-white/5 px-4 py-3 md:px-6">
                                                        <p className="font-bold text-gray-300">
                                                            Cara Pembayaran
                                                        </p>
                                                    </div>
                                                    {/* Instruksi tiga kolom */}
                                                    <div className="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 md:grid-cols-3 md:gap-6 md:p-6">
                                                        {invoiceData.instructions &&
                                                            invoiceData.instructions.map(
                                                                (
                                                                    inst: any,
                                                                    i: number,
                                                                ) => (
                                                                    <div
                                                                        key={i}
                                                                    >
                                                                        <p className="mb-2 text-sm font-semibold text-gray-300">
                                                                            {
                                                                                inst.title
                                                                            }
                                                                        </p>
                                                                        <ul className="flex flex-col gap-1.5">
                                                                            {inst.steps.map(
                                                                                (
                                                                                    step: string,
                                                                                    j: number,
                                                                                ) => (
                                                                                    <li
                                                                                        key={
                                                                                            j
                                                                                        }
                                                                                        className="flex items-start gap-2 text-xs text-gray-400"
                                                                                    >
                                                                                        <span className="mt-0.5 shrink-0 text-[#a855f7]">
                                                                                            •
                                                                                        </span>
                                                                                        <span
                                                                                            dangerouslySetInnerHTML={{
                                                                                                __html: step,
                                                                                            }}
                                                                                        />
                                                                                    </li>
                                                                                ),
                                                                            )}
                                                                        </ul>
                                                                    </div>
                                                                ),
                                                            )}
                                                    </div>
                                                </div>
                                            )}

                                        {/* ===== Fallback ===== */}
                                        {!invoiceData.pay_url &&
                                            !invoiceData.qr_url &&
                                            !invoiceData.pay_code && (
                                                <div className="overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29] p-4 md:p-6">
                                                    <p className="mb-3 text-sm text-gray-400">
                                                        Lanjutkan pembayaran
                                                        melalui halaman Tripay.
                                                    </p>
                                                    <a
                                                        href={
                                                            invoiceData.payment_url
                                                        }
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="block w-full rounded-lg bg-[#4ade80] px-4 py-3 text-center font-bold text-[#1e1f29] transition hover:bg-[#34d399]"
                                                    >
                                                        Buka Halaman Pembayaran
                                                    </a>
                                                </div>
                                            )}

                                        {/* ===== Nomor VA di bawah (seperti screenshot) ===== */}
                                        {!invoiceData.pay_url &&
                                            !invoiceData.qr_url &&
                                            invoiceData.pay_code && (
                                                <div className="flex flex-col gap-2 rounded-xl border border-[#31334c] bg-[#1e1f29] px-4 py-3 sm:flex-row sm:items-center sm:justify-between md:px-6 md:py-4">
                                                    <span className="text-sm font-semibold text-gray-300">
                                                        Nomor Pembayaran —{' '}
                                                        {invoiceData.method}
                                                    </span>
                                                    <div className="flex items-center gap-3">
                                                        <span className="font-mono font-bold break-all text-[#FFC107]">
                                                            {
                                                                invoiceData.pay_code
                                                            }
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                copyToClipboard(
                                                                    invoiceData.pay_code,
                                                                )
                                                            }
                                                            className="text-gray-400 transition hover:text-white"
                                                            title="Salin"
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
                                            )}

                                        {/* ===== Pesan menunggu pembayaran ===== */}
                                        <div className="flex items-center gap-2 rounded-lg border border-yellow-500/20 bg-yellow-500/5 px-4 py-3 text-xs text-yellow-400">
                                            <svg
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                className="shrink-0"
                                            >
                                                <circle
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                />
                                                <path d="M12 8v4l3 3" />
                                            </svg>
                                            Menunggu pembayaran anda. Silahkan
                                            selesaikan pembayaran sebelum batas
                                            waktu berakhir.
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex flex-col gap-4">
                                    <Link href="/">
                                        <div className="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary to-[#9b4dec] px-4 py-3 font-bold text-white shadow-[0_0_20px_rgba(168,85,247,0.3)] transition hover:opacity-90 md:px-6 md:py-4">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                                <path d="M3 6h18" />
                                                <path d="M16 10a4 4 0 0 1-8 0" />
                                            </svg>
                                            Beli Lagi
                                        </div>
                                    </Link>

                                    {/* Tombol Beri Ulasan — hanya untuk transaksi game yang sudah success */}
                                    {invoiceData.status.toLowerCase() === 'success' && invoiceData.type === 'transaction' && (
                                        reviewDone || hasReviewed ? (
                                            <div className="flex items-center justify-center gap-2 rounded-xl border border-green-500/20 bg-green-500/5 px-4 py-3 text-sm font-medium text-green-400">
                                                <span>🎉</span>
                                                <span>Terima kasih atas ulasanmu!</span>
                                            </div>
                                        ) : (
                                            <button
                                                onClick={() => setShowReviewModal(true)}
                                                className="flex w-full items-center justify-center gap-2 rounded-xl border border-primary/30 bg-primary/10 px-4 py-3 font-bold text-primary transition hover:bg-primary/20"
                                            >
                                                <span className="text-lg">★</span>
                                                Beri Ulasan
                                            </button>
                                        )
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
            {/* Review Modal */}
            {showReviewModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 backdrop-blur-sm">
                    <div className="w-full max-w-sm rounded-3xl border border-[#31334c] bg-[#242533] shadow-2xl">
                        {/* Header */}
                        <div className="flex flex-col items-center p-6 pb-4 text-center">
                            {/* Icon */}
                            <div className="relative mb-4 h-20 w-20">
                                <div className="absolute inset-0 flex items-center justify-center rounded-full bg-gradient-to-tr from-[#1e1f29] to-[#31334c] shadow-[0_0_30px_rgba(74,222,128,0.2)]">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4ade80" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                </div>
                                <div className="absolute -right-1 -bottom-1 rounded-full border-4 border-[#242533] bg-white p-1.5">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a855f7" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                        <path d="M3 6h18" />
                                        <path d="M16 10a4 4 0 0 1-8 0" />
                                    </svg>
                                </div>
                            </div>
                            <h2 className="mb-1 text-xl font-bold text-white">Pesanan Selesai</h2>
                            <p className="text-xs text-gray-400">Terimakasih telah mempercayai layanan kami.</p>
                        </div>

                        {/* Body */}
                        <div className="px-6 pb-6">
                            {reviewDone ? (
                                <div className="flex flex-col items-center gap-2 py-6 text-center">
                                    <span className="text-4xl">🎉</span>
                                    <p className="font-semibold text-green-400">Terima kasih atas ulasanmu!</p>
                                    <p className="text-xs text-gray-500">Ulasanmu membantu pembeli lain.</p>
                                </div>
                            ) : (
                                <div className="flex flex-col gap-4">
                                    {/* Stars */}
                                    <div>
                                        <p className="mb-2 text-sm font-semibold text-gray-300">Berikan penilaian tentang transaksi ini</p>
                                        <div className="flex gap-2">
                                            {[1, 2, 3, 4, 5].map((star) => (
                                                <button
                                                    key={star}
                                                    onClick={() => setReviewRating(star)}
                                                    className="text-3xl transition-transform hover:scale-110"
                                                >
                                                    <span className={star <= reviewRating ? 'text-[#FFC107]' : 'text-gray-600'}>★</span>
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Tags */}
                                    <div>
                                        <p className="mb-2 text-sm font-semibold text-gray-300">Tambahkan ulasan kamu</p>
                                        <div className="flex flex-wrap gap-2">
                                            {REVIEW_TAGS.map((tag) => (
                                                <button
                                                    key={tag}
                                                    onClick={() => toggleReviewTag(tag)}
                                                    className={`rounded-full border px-3 py-1.5 text-xs font-medium transition ${
                                                        reviewTags.includes(tag)
                                                            ? 'border-primary bg-primary/20 text-primary'
                                                            : 'border-[#31334c] text-gray-400 hover:border-gray-500'
                                                    }`}
                                                >
                                                    {tag}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Buttons */}
                            <div className="mt-5 grid grid-cols-2 gap-3">
                                <button
                                    onClick={() => setShowReviewModal(false)}
                                    className="rounded-xl border border-[#31334c] bg-transparent px-4 py-3 font-bold text-gray-300 transition hover:bg-white/5"
                                >
                                    Tutup
                                </button>
                                {!reviewDone && (
                                    <button
                                        onClick={submitReview}
                                        disabled={reviewRating === 0 || isSubmittingReview}
                                        className={`rounded-xl px-4 py-3 font-bold text-white transition ${
                                            reviewRating === 0 || isSubmittingReview
                                                ? 'cursor-not-allowed bg-gray-600'
                                                : 'bg-gradient-to-r from-primary to-[#9b4dec] hover:opacity-90'
                                        }`}
                                    >
                                        {isSubmittingReview ? 'Mengirim...' : 'Kirim'}
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </GuestLayout>
    );
}
