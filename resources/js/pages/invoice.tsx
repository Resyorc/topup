import { Head, useForm, Link, router } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';
import axios from 'axios';
import DOMPurify from 'dompurify';
import React, { useState, useEffect, useCallback } from 'react';
import Swal from 'sweetalert2';
import GameCard from '@/components/game-card';
import { useGuestInvoice } from '@/contexts/guest-invoice-context';
import GuestLayout from '@/layouts/guest-layout';
import {
    getPaymentStatusBadge,
    getTransactionStatusBadge,
} from '@/lib';
import { swalError } from '@/lib/swal';

function InvoiceRealtimeListener({
    invoiceNo,
    onUpdate,
}: {
    invoiceNo: string;
    onUpdate: (data: { status: string; payment_status: string }) => void;
}) {
    useEchoPublic(`invoice.${invoiceNo}`, '.InvoiceStatusUpdated', onUpdate);
    return null;
}

interface InvoiceSearchProps {
    initialInvoiceData?: any;
    searchedInvoiceId?: string;
}

export default function InvoiceSearch({
    initialInvoiceData = null,
    searchedInvoiceId = '',
}: InvoiceSearchProps) {
    const {
        hasReviewed: guestHasReviewed,
        markReviewed,
        getGuestToken,
    } = useGuestInvoice();

    const { data, setData, processing, errors } = useForm({
        invoice_id: searchedInvoiceId,
    });

    const [searchMode, setSearchMode] = useState<'invoice' | 'phone'>('invoice');
    const [phoneInput, setPhoneInput] = useState('');
    const [phoneResults, setPhoneResults] = useState<Array<{
        invoice_id: string;
        type: string;
        status: string;
        amount: number;
        created_at: string;
    }> | null>(null);
    const [phoneSearching, setPhoneSearching] = useState(false);
    const [phoneError, setPhoneError] = useState<string | null>(null);

    const searchByPhone = async (e: React.SyntheticEvent) => {
        e.preventDefault();
        if (!phoneInput.trim()) return;
        setPhoneSearching(true);
        setPhoneError(null);
        setPhoneResults(null);
        try {
            const res = await axios.get('/invoice/by-phone', {
                params: { phone: phoneInput.trim() },
            });
            setPhoneResults(res.data.data);
        } catch (err: any) {
            setPhoneError(err.response?.data?.message ?? 'Terjadi kesalahan.');
        } finally {
            setPhoneSearching(false);
        }
    };

    const [invoiceData, setInvoiceData] = useState<any>(initialInvoiceData);
    const [animatedStatus, setAnimatedStatus] = useState<number>(0);
    const [isPaymentOpen, setIsPaymentOpen] = useState<boolean>(true);
    const [remainingSeconds, setRemainingSeconds] = useState<number | null>(
        null,
    );

    // Review state
    const [hasReviewed, setHasReviewed] = useState<boolean>(
        initialInvoiceData?.has_reviewed ||
        (!!initialInvoiceData?.invoice_no && guestHasReviewed(initialInvoiceData.invoice_no)),
    );
    const [reviewRating, setReviewRating] = useState<number>(0);
    const [reviewTags, setReviewTags] = useState<string[]>([]);
    const [isSubmittingReview, setIsSubmittingReview] = useState(false);
    const [reviewDone, setReviewDone] = useState(false);
    const [showReviewModal, setShowReviewModal] = useState(false);

    const REVIEW_TAGS = [
        'Proses Cepat',
        'Terpercaya',
        'Harga Terjangkau',
        'Direkomendasikan',
        'Pelayanan Ramah',
    ];

    const toggleReviewTag = (tag: string) => {
        setReviewTags((prev) =>
            prev.includes(tag) ? prev.filter((t) => t !== tag) : [...prev, tag],
        );
    };

    const cancelOrder = async () => {
        const result = await Swal.fire({
            title: 'Batalkan Pesanan?',
            text: 'Pesanan akan dibatalkan dan kamu bisa membuat pesanan baru.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Batalkan',
            cancelButtonText: 'Kembali',
            confirmButtonColor: 'var(--color-danger)',
            cancelButtonColor: 'var(--color-border-light)',
            background: 'var(--color-bg-card)',
            color: 'var(--color-text-primary)',
            customClass: {
                popup: 'rounded-2xl border border-[var(--color-border-light)]',
                confirmButton: 'rounded-xl px-5 py-2.5 font-bold text-sm',
                cancelButton: 'rounded-xl px-5 py-2.5 font-bold text-sm',
            },
        });

        if (!result.isConfirmed) return;

        try {
            await axios.post('/api/cancel', {
                invoice_id: invoiceData.invoice_no,
            });
            setInvoiceData((prev: any) => ({ ...prev, status: 'canceled' }));
        } catch (error: any) {
            swalError(
                error.response?.data?.message || 'Gagal membatalkan pesanan.',
            );
        }
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
            markReviewed(invoiceData?.invoice_no ?? '');
        } catch (error: any) {
            swalError(
                error.response?.data?.message || 'Gagal mengirim ulasan.',
            );
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

    const isTerminal = ['success', 'failed', 'expired', 'canceled'].includes(
        invoiceData?.status?.toLowerCase() ?? '',
    );

    const resolveGuestToken = useCallback(
        (invoiceNo?: string) => {
            if (!invoiceNo) return null;

            const localToken = getGuestToken(invoiceNo);
            if (localToken) return localToken;

            if (typeof window === 'undefined') return null;

            return new URLSearchParams(window.location.search).get('guest_token');
        },
        [getGuestToken],
    );

    const handleInvoiceUpdate = useCallback(
        (data: { status: string; payment_status: string }) => {
            setInvoiceData((prev: any) => ({
                ...prev,
                status: data.status,
                payment_status: data.payment_status,
            }));
        },
        [],
    );


    const submit = (e: React.SyntheticEvent) => {
        e.preventDefault();

        const guestToken = resolveGuestToken(data.invoice_id);

        router.get('/invoice', {
            invoice_id: data.invoice_id,
            ...(guestToken ? { guest_token: guestToken } : {}),
        }, {
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
                                    <span className="text-[var(--color-warning)]">
                                        Mudah dan Cepat
                                    </span>
                                </h1>
                                <p className="text-xs text-gray-300 md:text-base">
                                    Lihat detail pembelian anda menggunakan
                                    nomor Invoice.
                                </p>
                            </div>

                            {/* Search Box */}
                            <div className="w-full max-w-5xl overflow-hidden rounded-2xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)] shadow-2xl">
                                {/* Tabs */}
                                <div className="flex border-b border-[var(--color-border-light)]">
                                    <button
                                        onClick={() => setSearchMode('invoice')}
                                        className={`flex-1 px-6 py-4 text-sm font-bold transition ${searchMode === 'invoice' ? 'border-b-2 border-primary bg-white/5 text-white' : 'text-gray-400 hover:text-white'}`}
                                    >
                                        Nomor Invoice
                                    </button>
                                    <button
                                        onClick={() => { setSearchMode('phone'); setPhoneResults(null); setPhoneError(null); }}
                                        className={`flex-1 px-6 py-4 text-sm font-bold transition ${searchMode === 'phone' ? 'border-b-2 border-primary bg-white/5 text-white' : 'text-gray-400 hover:text-white'}`}
                                    >
                                        Nomor WhatsApp
                                    </button>
                                </div>

                                {/* Box Body */}
                                <div className="p-4 md:p-8">
                                    {searchMode === 'invoice' ? (
                                        <form onSubmit={submit} className="flex flex-col gap-6">
                                            <div>
                                                <input
                                                    type="text"
                                                    value={data.invoice_id}
                                                    onChange={(e) => setData('invoice_id', e.target.value)}
                                                    placeholder="Contoh: INV-1234567890"
                                                    className="block w-full rounded-lg border border-[var(--color-border-light)] bg-[var(--color-bg-secondary)] p-4 text-white placeholder-gray-500 transition outline-none focus:border-primary focus:ring-primary"
                                                    required
                                                />
                                                <p className="mt-2 text-xs text-gray-500">
                                                    Masukkan nomor Invoice yang didapatkan saat transaksi.
                                                </p>
                                                {errors.invoice_id && (
                                                    <p className="mt-2 text-sm text-red-500">{errors.invoice_id}</p>
                                                )}
                                            </div>
                                            <button
                                                type="submit"
                                                disabled={processing}
                                                className="w-full rounded-lg bg-gradient-to-r from-primary to-[var(--color-primary-light)] px-6 py-4 text-lg font-bold text-white shadow-[var(--shadow-glow)] transition hover:opacity-90 disabled:opacity-50"
                                            >
                                                Cari Pesanan
                                            </button>
                                        </form>
                                    ) : (
                                        <form onSubmit={searchByPhone} className="flex flex-col gap-6">
                                            <div>
                                                <input
                                                    type="text"
                                                    value={phoneInput}
                                                    onChange={(e) => { setPhoneInput(e.target.value); setPhoneResults(null); setPhoneError(null); }}
                                                    placeholder="Contoh: 08123456789"
                                                    className="block w-full rounded-lg border border-[var(--color-border-light)] bg-[var(--color-bg-secondary)] p-4 text-white placeholder-gray-500 transition outline-none focus:border-primary"
                                                    required
                                                />
                                                <p className="mt-2 text-xs text-gray-500">
                                                    Masukkan nomor WhatsApp yang digunakan saat transaksi.
                                                </p>
                                                {phoneError && (
                                                    <p className="mt-2 text-sm text-red-400">{phoneError}</p>
                                                )}
                                            </div>

                                            <button
                                                type="submit"
                                                disabled={phoneSearching}
                                                className="w-full rounded-lg bg-gradient-to-r from-primary to-[var(--color-primary-light)] px-6 py-4 text-lg font-bold text-white shadow-[var(--shadow-glow)] transition hover:opacity-90 disabled:opacity-50"
                                            >
                                                {phoneSearching ? 'Mencari...' : 'Cari Transaksi'}
                                            </button>

                                            {/* Phone search results */}
                                            {phoneResults && phoneResults.length > 0 && (
                                                <div className="flex flex-col gap-2">
                                                    <p className="text-xs font-semibold text-gray-400 uppercase">
                                                        {phoneResults.length} transaksi ditemukan
                                                    </p>
                                                    {phoneResults.map((r) => (
                                                        <Link
                                                            key={r.invoice_id}
                                                            href={
                                                                `/invoice?invoice_id=${encodeURIComponent(r.invoice_id)}` +
                                                                (getGuestToken(r.invoice_id)
                                                                    ? `&guest_token=${encodeURIComponent(getGuestToken(r.invoice_id) ?? '')}`
                                                                    : '')
                                                            }
                                                            className="flex items-center justify-between rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-secondary)] px-4 py-3 transition hover:border-primary"
                                                        >
                                                            <div>
                                                                <div className="text-xs font-bold text-white">{r.invoice_id}</div>
                                                                <div className="mt-0.5 text-[11px] text-gray-400">{r.created_at}</div>
                                                            </div>
                                                            <div className="text-right">
                                                                <div className="text-sm font-bold text-white">
                                                                    Rp {r.amount.toLocaleString('id-ID')}
                                                                </div>
                                                                <span className={`mt-0.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize ${
                                                                    r.status === 'success' ? 'bg-status-success-bg text-status-success' :
                                                                    r.status === 'canceled' ? 'bg-status-canceled-bg text-status-canceled' :
                                                                    r.status === 'refunded' ? 'bg-status-refunded-bg text-status-refunded' :
                                                                    r.status === 'failed' || r.status === 'expired' ? 'bg-status-failed-bg text-status-failed' :
                                                                    r.status === 'pending' ? 'bg-status-pending-bg text-status-pending' :
                                                                    'bg-status-processing-bg text-status-processing'
                                                                }`}>
                                                                    {r.status}
                                                                </span>
                                                            </div>
                                                        </Link>
                                                    ))}
                                                </div>
                                            )}
                                        </form>
                                    )}
                                </div>
                            </div>
                        </>
                    )}

                    {/* DETAIL INVOICE SECTION (Tampil jika ada data invoice) */}
                    {invoiceData && (
                        <div className="animate-fade-in-up mt-6 flex w-full max-w-5xl flex-col gap-4 md:mt-10 md:gap-6">
                            {/* Status Bar Card */}
                            <div className="relative overflow-hidden rounded-2xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)] p-4 shadow-lg md:p-10">
                                <h2 className="mb-8 text-center text-lg font-bold text-white md:mb-12 md:text-2xl">
                                    Detail Invoice
                                </h2>

                                <div className="relative mx-auto max-w-3xl px-4 pt-4 pb-16 md:px-12 md:pt-6 md:pb-20">
                                    {/* The Line Container itself is the anchor */}
                                    <div className="relative z-0 h-1.5 w-full rounded-full bg-[var(--color-border-light)]">
                                        {/* Animated Progress Line Foreground */}
                                        <div
                                            className="absolute top-0 left-0 z-0 h-full rounded-full bg-[var(--color-success)] transition-all duration-700 ease-in-out"
                                            style={{
                                                width: `${Math.max(0, ((animatedStatus - 1) / 3) * 100)}%`,
                                            }}
                                        ></div>

                                        {/* Step 1: Transaksi Dibuat */}
                                        <div className="absolute top-1/2 left-[0%] z-10 flex w-16 -translate-x-1/2 -translate-y-1/2 flex-col items-center md:w-32">
                                            <div
                                                className={`flex h-10 w-10 items-center justify-center rounded-xl transition-all delay-100 duration-500 ${animatedStatus >= 1 ? 'bg-[var(--color-success)] text-[var(--color-bg-card)]' : 'bg-[var(--color-border-light)] text-gray-400'}`}
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
                                                className={`flex h-10 w-10 items-center justify-center rounded-xl transition-all delay-100 duration-500 ${animatedStatus >= 2 ? 'bg-[var(--color-success)] text-[var(--color-bg-card)]' : 'bg-[var(--color-border-light)] text-gray-400'}`}
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
                                                className={`flex h-10 w-10 items-center justify-center rounded-xl transition-all delay-100 duration-500 ${animatedStatus >= 3 ? 'bg-[var(--color-success)] text-[var(--color-bg-card)]' : 'bg-[var(--color-border-light)] text-gray-400'}`}
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
                                                className={`flex h-10 w-10 items-center justify-center rounded-xl shadow-[0_0_15px_rgba(74,222,128,0.3)] transition-all delay-100 duration-500 ${animatedStatus >= 4 ? 'bg-[var(--color-success)] text-[var(--color-bg-card)]' : 'bg-[var(--color-border-light)] text-gray-400'}`}
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
                                                className={`absolute top-12 w-full text-center text-[10px] font-semibold transition-colors duration-500 md:top-14 md:text-sm ${animatedStatus >= 4 ? 'text-[var(--color-success)]' : 'text-gray-400'}`}
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

                                const showTimer =
                                    ['pending', 'expired'].includes(
                                        statusLower,
                                    ) &&
                                    remainingSeconds !== null &&
                                    remainingSeconds > 0;

                                if (!showTimer) {
                                    return (
                                        <div className="mt-8 h-7 md:mt-10 md:h-9" />
                                    );
                                }

                                return (
                                    <div className="mt-8 flex w-full justify-end md:mt-10">
                                        <span className="text-[#ffb3d7] inline-flex max-w-full items-center gap-1 whitespace-nowrap rounded-full border border-[#ef4b9a]/40 bg-[#ef4b9a]/15 px-2.5 py-1 text-[10px] font-semibold md:gap-1.5 md:px-4.5 md:py-2 md:text-sm">
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
                                                <circle
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                />
                                                <path d="M12 6v6l4 2" />
                                            </svg>
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
                                        </span>
                                    </div>
                                );
                            })()}

                            {/* Account Info Card */}
                            <div className="relative z-10 mt-3 flex min-h-[140px] flex-col items-center gap-4 overflow-visible rounded-2xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)] p-4 shadow-lg md:mt-5 md:flex-row md:items-stretch md:gap-8 md:p-6">
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
                                    {/* Status Badge (Top Right) */}
                                    {invoiceData.status.toLowerCase() === 'success' && (
                                        <div className="absolute top-0 right-0 z-20">
                                            <span className="rounded-full border border-[var(--color-success)]/50 bg-bg-[color-mix(in_srgb,var(--color-success)_20%,transparent)] px-4 py-1.5 text-xs font-bold text-[var(--color-success)] shadow-[0_0_10px_rgba(74,222,128,0.2)]">
                                                Pesanan telah selesai.
                                            </span>
                                        </div>
                                    )}
                                    {['failed', 'canceled', 'expired'].includes(invoiceData.status.toLowerCase()) && (
                                        <div className="absolute top-0 right-0 z-20">
                                            <span className="rounded-full border border-red-500/50 bg-red-950/60 px-4 py-1.5 text-xs font-bold text-red-400 shadow-[0_0_10px_rgba(239,68,68,0.2)]">
                                                {invoiceData.status.toLowerCase() === 'expired' ? 'Pesanan kadaluarsa.' : 'Pesanan dibatalkan.'}
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
                                    <div className="relative flex flex-col border-[var(--color-border-light)] pt-2 md:border-l md:pl-6">
                                        <p className="mb-4 text-sm text-gray-400">
                                            {invoiceData.created_at}
                                        </p>

                                        <h3 className="mb-2 text-sm font-bold text-white">
                                            Jenis Pembelian
                                        </h3>
                                        <div className="flex items-center gap-2">
                                            <div>
                                                <p className="text-base leading-tight font-bold text-[var(--color-warning)]">
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
                                <div className="overflow-hidden rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)] shadow-lg">
                                    {/* Accordion Header */}
                                    <div
                                        className="flex cursor-pointer items-center justify-between border-b border-[var(--color-border-light)] bg-white/5 px-6 py-4"
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
                                            <div className="grid grid-cols-1 gap-x-4 gap-y-4 border-b border-[var(--color-border-light)] pb-6 sm:grid-cols-2">
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
                                                    {/* <span className="bg-[var(--color-success)] text-[var(--color-bg-card)] font-bold text-[10px] px-2 py-0.5 rounded uppercase">{invoiceData.status.toLowerCase() === 'success' ? 'PAID' : invoiceData.status}</span> */}
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
                                                    {/* <span className="bg-[var(--color-success)] text-[var(--color-bg-card)] font-bold text-[10px] px-2 py-0.5 rounded uppercase">{invoiceData.status}</span> */}
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
                                                    Transaksi berhasil dibuat
                                                    pada {invoiceData.paid_at}
                                                </div>
                                            </div>

                                            <div className="mt-2 grid grid-cols-2 gap-y-4 border-b border-[var(--color-border-light)] pb-6">
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
                            <div className="flex flex-col gap-2 rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)] px-4 py-3 shadow-lg sm:flex-row sm:items-center sm:justify-between md:px-6 md:py-4">
                                <span className="text-sm font-bold text-white md:text-base">
                                    Total Pembayaran
                                </span>
                                <div className="flex items-center gap-3">
                                    <span className="text-lg font-black text-[var(--color-warning)] md:text-xl">
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
                                                <div className="overflow-hidden rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)]">
                                                    {/* Header */}
                                                    <div className="border-b border-[var(--color-border-light)] bg-white/5 px-4 py-3 md:px-6">
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
                                                                                            <span className="mt-0.5 shrink-0 text-[var(--color-accent)]">
                                                                                                •
                                                                                            </span>
                                                                                            <span
                                                                                                dangerouslySetInnerHTML={{
                                                                                                    __html: DOMPurify.sanitize(
                                                                                                        step,
                                                                                                    ),
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
                                                                className="w-full rounded-lg bg-gradient-to-r from-primary to-[var(--color-primary-light)] px-4 py-2.5 text-center text-sm font-bold text-white transition hover:opacity-90"
                                                            >
                                                                Unduh Kode QR
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                        {/* ===== eWallet — ada pay_url ===== */}
                                        {invoiceData.pay_url && (
                                            <div className="overflow-hidden rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)]">
                                                <div className="border-b border-[var(--color-border-light)] bg-white/5 px-4 py-3 md:px-6">
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
                                                                                        <span className="mt-0.5 shrink-0 text-[var(--color-accent)]">
                                                                                            •
                                                                                        </span>
                                                                                        <span
                                                                                            dangerouslySetInnerHTML={{
                                                                                                __html: DOMPurify.sanitize(
                                                                                                    step,
                                                                                                ),
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
                                                            className="w-full rounded-lg bg-gradient-to-r from-primary to-[var(--color-primary-light)] px-4 py-3 text-center font-bold text-white shadow-[var(--shadow-glow)] transition hover:opacity-90"
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
                                                <div className="overflow-hidden rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)]">
                                                    <div className="border-b border-[var(--color-border-light)] bg-white/5 px-4 py-3 md:px-6">
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
                                                                                        <span className="mt-0.5 shrink-0 text-[var(--color-accent)]">
                                                                                            •
                                                                                        </span>
                                                                                        <span
                                                                                            dangerouslySetInnerHTML={{
                                                                                                __html: DOMPurify.sanitize(
                                                                                                    step,
                                                                                                ),
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
                                                <div className="overflow-hidden rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)] p-4 md:p-6">
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
                                                        className="block w-full rounded-lg bg-[var(--color-success)] px-4 py-3 text-center font-bold text-[var(--color-bg-card)] transition hover:bg-[var(--color-success)]"
                                                    >
                                                        Buka Halaman Pembayaran
                                                    </a>
                                                </div>
                                            )}

                                        {/* ===== Nomor VA di bawah (seperti screenshot) ===== */}
                                        {!invoiceData.pay_url &&
                                            !invoiceData.qr_url &&
                                            invoiceData.pay_code && (
                                                <div className="flex flex-col gap-2 rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between md:px-6 md:py-4">
                                                    <span className="text-sm font-semibold text-gray-300">
                                                        Nomor Pembayaran —{' '}
                                                        {invoiceData.method}
                                                    </span>
                                                    <div className="flex items-center gap-3">
                                                        <span className="font-mono font-bold break-all text-[var(--color-warning)]">
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
                                        <button
                                            onClick={cancelOrder}
                                            className="w-full rounded-xl border border-red-500/30 bg-transparent py-3 text-sm font-bold text-red-400 transition hover:bg-red-500/10"
                                        >
                                            Batalkan Pesanan
                                        </button>
                                    </div>
                                </div>
                            ) : invoiceData.status.toLowerCase() ===
                              'processing' ? (
                                <div className="flex flex-col gap-3">
                                    <div className="flex items-center gap-3 rounded-xl border border-blue-500/20 bg-blue-500/5 px-4 py-3.5 text-sm text-blue-300">
                                        <svg
                                            width="18"
                                            height="18"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            className="shrink-0 animate-spin"
                                        >
                                            <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                                        </svg>
                                        Transaksi sedang diproses. Harap tunggu
                                        konfirmasi dari provider.
                                    </div>
                                </div>
                            ) : (
                                <div className="flex flex-col gap-4">
                                    {/* Banner Reward Krysta Coin */}
                                    {invoiceData.status?.toLowerCase() === 'success' &&
                                        invoiceData.type === 'transaction' &&
                                        (invoiceData.loyalty_coins ?? 0) > 0 && (
                                        <div className="flex items-center gap-3 rounded-xl border border-yellow-500/30 bg-gradient-to-r from-yellow-500/10 to-amber-500/10 px-4 py-3">
                                            <img src="/coin.png" alt="Coin" className="h-8 w-8 object-contain" />
                                            <div>
                                                <p className="text-sm font-bold text-yellow-400">
                                                    +{invoiceData.loyalty_coins.toLocaleString('id-ID')} Krysta Coin
                                                </p>
                                                <p className="text-xs text-yellow-400/70">
                                                    Reward loyalitas sudah ditambahkan ke akunmu!
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    <Link href="/">
                                        <div className="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary to-[var(--color-primary-light)] px-4 py-3 font-bold text-white shadow-[var(--shadow-glow)] transition hover:opacity-90 md:px-6 md:py-4">
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

                                    {/* Tombol Beri Ulasan — hanya untuk transaksi game yang sudah success */}
                                    {invoiceData.status.toLowerCase() ===
                                        'success' &&
                                        invoiceData.type === 'transaction' &&
                                        (reviewDone || hasReviewed ? (
                                            <div className="flex items-center justify-center gap-2 rounded-xl border border-green-500/20 bg-green-500/5 px-4 py-3 text-sm font-medium text-green-400">
                                                <span>🎉</span>
                                                <span>
                                                    Terima kasih atas ulasanmu!
                                                </span>
                                            </div>
                                        ) : (
                                            <button
                                                onClick={() =>
                                                    setShowReviewModal(true)
                                                }
                                                className="flex w-full items-center justify-center gap-2 rounded-xl border border-primary/30 bg-primary/10 px-4 py-3 font-bold text-primary transition hover:bg-primary/20"
                                            >
                                                <span className="text-lg">
                                                    ★
                                                </span>
                                                Beri Ulasan
                                            </button>
                                        ))}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
            {/* Review Modal */}
            {showReviewModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 backdrop-blur-sm">
                    <div className="w-full max-w-sm rounded-3xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)] shadow-2xl">
                        {/* Header */}
                        <div className="flex flex-col items-center p-6 pb-4 text-center">
                            {/* Icon */}
                            <div className="relative mb-4 h-20 w-20">
                                <div className="absolute inset-0 flex items-center justify-center rounded-full bg-gradient-to-tr from-[var(--color-bg-card)] to-[var(--color-border-light)] shadow-[0_0_30px_rgba(74,222,128,0.2)]">
                                    <svg
                                        width="40"
                                        height="40"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="var(--color-success)"
                                        strokeWidth="3"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                </div>
                                <div className="absolute -right-1 -bottom-1 rounded-full border-4 border-[var(--color-bg-card)] bg-white p-1.5">
                                    <svg
                                        width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="var(--color-accent)"
                                        strokeWidth="2"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                        <path d="M3 6h18" />
                                        <path d="M16 10a4 4 0 0 1-8 0" />
                                    </svg>
                                </div>
                            </div>
                            <h2 className="mb-1 text-xl font-bold text-white">
                                Pesanan Selesai
                            </h2>
                            <p className="text-xs text-gray-400">
                                Terimakasih telah mempercayai layanan kami.
                            </p>
                        </div>

                        {/* Body */}
                        <div className="px-6 pb-6">
                            {reviewDone ? (
                                <div className="flex flex-col items-center gap-2 py-6 text-center">
                                    <span className="text-4xl">🎉</span>
                                    <p className="font-semibold text-green-400">
                                        Terima kasih atas ulasanmu!
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        Ulasanmu membantu pembeli lain.
                                    </p>
                                </div>
                            ) : (
                                <div className="flex flex-col gap-4">
                                    {/* Stars */}
                                    <div>
                                        <p className="mb-2 text-sm font-semibold text-gray-300">
                                            Berikan penilaian tentang transaksi
                                            ini
                                        </p>
                                        <div className="flex gap-2">
                                            {[1, 2, 3, 4, 5].map((star) => (
                                                <button
                                                    key={star}
                                                    onClick={() =>
                                                        setReviewRating(star)
                                                    }
                                                    className="text-3xl transition-transform hover:scale-110"
                                                >
                                                    <span
                                                        className={
                                                            star <= reviewRating
                                                                ? 'text-[var(--color-warning)]'
                                                                : 'text-gray-600'
                                                        }
                                                    >
                                                        ★
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Tags */}
                                    <div>
                                        <p className="mb-2 text-sm font-semibold text-gray-300">
                                            Tambahkan ulasan kamu
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {REVIEW_TAGS.map((tag) => (
                                                <button
                                                    key={tag}
                                                    onClick={() =>
                                                        toggleReviewTag(tag)
                                                    }
                                                    className={`rounded-full border px-3 py-1.5 text-xs font-medium transition ${
                                                        reviewTags.includes(tag)
                                                            ? 'border-primary bg-primary/20 text-primary'
                                                            : 'border-[var(--color-border-light)] text-gray-400 hover:border-gray-500'
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
                                    className="rounded-xl border border-[var(--color-border-light)] bg-transparent px-4 py-3 font-bold text-gray-300 transition hover:bg-white/5"
                                >
                                    Tutup
                                </button>
                                {!reviewDone && (
                                    <button
                                        onClick={submitReview}
                                        disabled={
                                            reviewRating === 0 ||
                                            isSubmittingReview
                                        }
                                        className={`rounded-xl px-4 py-3 font-bold text-white transition ${
                                            reviewRating === 0 ||
                                            isSubmittingReview
                                                ? 'cursor-not-allowed bg-gray-600'
                                                : 'bg-gradient-to-r from-primary to-[var(--color-primary-light)] hover:opacity-90'
                                        }`}
                                    >
                                        {isSubmittingReview
                                            ? 'Mengirim...'
                                            : 'Kirim'}
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}
            {invoiceData?.invoice_no && !isTerminal && (
                <InvoiceRealtimeListener
                    invoiceNo={invoiceData.invoice_no}
                    onUpdate={handleInvoiceUpdate}
                />
            )}
        </GuestLayout>
    );
}
