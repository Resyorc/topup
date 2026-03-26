import { usePage, Link } from '@inertiajs/react';
import { useState } from 'react';
import UserLayout from '@/layouts/user-layout';
import { formatCurrency, formatDate, getTransactionStatusBadge } from '@/lib';

interface DashboardStats {
    pending: number;
    processing: number;
    success: number;
    failed: number;
}

interface RecentTransaction {
    invoice_id: string;
    game_name: string;
    product_name: string;
    amount: number;
    created_at: string | null;
    status: string;
}

export default function Dashboard() {
    const pageProps = usePage().props as any;
    const auth = pageProps.auth;
    const dashboardStats: DashboardStats = pageProps.dashboardStats ?? {
        pending: 0,
        processing: 0,
        success: 0,
        failed: 0,
    };
    const coinsBalance: number = pageProps.coinsBalance ?? 0;
    const recentTransactions: RecentTransaction[] =
        pageProps.recentTransactions ?? [];
    const promoVouchers: Array<{
        code: string;
        type: string;
        value: number;
        max_discount: number | null;
        min_amount: number;
        valid_until: string | null;
        min_tier: string | null;
        used: boolean;
    }> = pageProps.promoVouchers ?? [];
    const [copiedCode, setCopiedCode] = useState<string | null>(null);
    const tierInfo = pageProps.tierInfo as
        | {
              current: string;
              multiplier: number;
              total_spending: number;
              next_threshold: number | null;
          }
        | undefined;
    const user = auth?.user;

    const tierBadgeClass: Record<string, string> = {
        platinum: 'bg-purple-500/20 text-purple-300',
        gold: 'bg-yellow-500/20 text-yellow-300',
        silver: 'bg-blue-500/20 text-blue-300',
        bronze: 'bg-orange-500/20 text-orange-300',
    };
    const tierIcon: Record<string, string> = {
        platinum: '💎',
        gold: '🥇',
        silver: '🥈',
        bronze: '🥉',
    };

    const handleCopy = (code: string) => {
        navigator.clipboard.writeText(code);
        setCopiedCode(code);
        setTimeout(() => setCopiedCode(null), 2000);
    };

    const tierColor = (tier: string) =>
        ({
            platinum: {
                border: 'border-purple-500/40',
                bg: 'from-purple-900/30 to-purple-800/10',
                badge: 'bg-purple-500/20 text-purple-300',
                bar: 'bg-purple-400',
            },
            gold: {
                border: 'border-yellow-500/40',
                bg: 'from-yellow-900/30 to-yellow-800/10',
                badge: 'bg-yellow-500/20 text-yellow-300',
                bar: 'bg-yellow-400',
            },
            silver: {
                border: 'border-blue-500/40',
                bg: 'from-blue-900/30 to-blue-800/10',
                badge: 'bg-blue-500/20 text-blue-300',
                bar: 'bg-blue-400',
            },
            bronze: {
                border: 'border-orange-500/40',
                bg: 'from-orange-900/20 to-orange-800/10',
                badge: 'bg-orange-500/20 text-orange-300',
                bar: 'bg-orange-400',
            },
        })[tier] ?? {
            border: 'border-orange-500/40',
            bg: 'from-orange-900/20 to-orange-800/10',
            badge: 'bg-orange-500/20 text-orange-300',
            bar: 'bg-orange-400',
        };

    const tierThresholds: Record<string, number> = {
        bronze: 0,
        silver: 500_000,
        gold: 2_000_000,
        platinum: 10_000_000,
    };
    const currentTier = tierInfo?.current ?? 'bronze';
    const tc = tierColor(currentTier);
    const progressPercent = tierInfo?.next_threshold
        ? Math.min(
              100,
              Math.round(
                  ((tierInfo.total_spending -
                      (tierThresholds[currentTier] ?? 0)) /
                      (tierInfo.next_threshold -
                          (tierThresholds[currentTier] ?? 0))) *
                      100,
              ),
          )
        : 100;

    return (
        <UserLayout title="Dashboard">
            {/* 1. Profil Section */}
            <section>
                <h2 className="mb-6 text-2xl font-bold text-white">Profil</h2>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {/* Profile Card (Polygon) */}
                    <div className="group relative p-[1px]">
                        {/* The border / background wrapper */}
                        <div className="absolute inset-0 bg-primary [clip-path:polygon(0_20px,20px_0,100%_0,100%_calc(100%-20px),calc(100%-20px)_100%,0_100%)]"></div>
                        {/* Inner dark container */}
                        <div className="relative flex h-full flex-col items-center justify-center bg-[#1A1A24] p-6 [clip-path:polygon(0_20px,20px_0,100%_0,100%_calc(100%-20px),calc(100%-20px)_100%,0_100%)] md:p-8">
                            <div className="mb-4 flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-4 border-gray-400 bg-white shadow-lg">
                                {user?.avatar_url ? (
                                    <img
                                        src={user.avatar_url}
                                        alt={user.name}
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    <svg
                                        className="mt-4 h-16 w-16 text-gray-300"
                                        fill="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                )}
                            </div>
                            <h3 className="text-lg font-bold text-white">
                                {user?.name || 'Ferry Oktariansyah'}
                            </h3>
                            <div
                                className={`mt-1 mb-6 rounded-full border px-4 py-0.5 text-xs font-bold ${
                                    user?.tier === 'platinum'
                                        ? 'border-purple-400 text-purple-400'
                                        : user?.tier === 'gold'
                                          ? 'border-yellow-400 text-yellow-400'
                                          : user?.tier === 'silver'
                                            ? 'border-blue-400 text-blue-400'
                                            : 'border-primary text-primary'
                                }`}
                            >
                                {user?.tier
                                    ? user.tier.charAt(0).toUpperCase() +
                                      user.tier.slice(1)
                                    : 'Bronze'}{' '}
                                Member
                            </div>

                            <div className="mb-4 h-px w-full bg-white/10"></div>

                            <div className="flex w-full flex-col gap-3 text-sm text-gray-300">
                                <div className="flex items-center gap-3">
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
                                            width="20"
                                            height="16"
                                            x="2"
                                            y="4"
                                            rx="2"
                                        />
                                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                    </svg>
                                    {user?.email || 'ferrygaming@gmail.com'}
                                </div>
                                <div className="flex items-center gap-3">
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
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                                    </svg>
                                    {user?.phone || 'Belum diisi'}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Krysta Coins Card */}
                    <div className="relative flex h-full flex-col justify-between overflow-hidden rounded-2xl border border-white/5 bg-gradient-to-br from-[#1b1c2a] via-[#212338] to-[#12101e] p-6 shadow-2xl md:p-8">
                        {/* Dotted texture background simulate */}
                        <div className="absolute top-0 right-0 h-40 w-40 bg-[radial-gradient(#fff_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_top_right,black,transparent_70%)] [background-size:10px_10px] opacity-10"></div>

                        <div className="relative z-10 flex items-center gap-4">
                            <div className="h-12 w-12 overflow-hidden">
                                <img
                                    src="/coin.png"
                                    alt="Krysta"
                                    className="h-full w-full object-contain"
                                />
                            </div>
                            <div>
                                <h3 className="text-xl font-bold text-white">
                                    Krysta Coins
                                </h3>
                                <p className="text-xs text-gray-400">
                                    (Bebas Biaya Admin)
                                </p>
                            </div>
                        </div>

                        <div className="relative z-10 mt-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                            <div>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-black text-yellow-400">
                                        {coinsBalance.toLocaleString('id-ID')}
                                    </span>
                                    <span className="text-2xl font-bold text-white">
                                        Coins
                                    </span>
                                </div>
                                <div className="mt-2 flex items-center gap-1.5 text-xs text-gray-300 italic">
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
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 16v-4" />
                                        <path d="M12 8h.01" />
                                    </svg>
                                    1 Krysta Coins = 1 Rupiah
                                </div>
                            </div>

                            <Link
                                href="/dashboard/topup-saldo"
                                className="w-full rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] px-6 py-2.5 text-center text-sm font-bold text-white shadow-lg transition hover:opacity-90 md:w-auto"
                            >
                                Top Up
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* 2. Nuvelo Member Club */}
            <section>
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-2xl font-bold text-white">
                        Nuvelo Member Club
                    </h2>
                    <Link
                        href="/dashboard/member-club"
                        className="text-sm text-primary hover:underline"
                    >
                        Info selengkapnya →
                    </Link>
                </div>

                <div
                    className={`rounded-2xl border bg-gradient-to-br ${tc.border} ${tc.bg} p-6 md:p-8`}
                >
                    <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        {/* Tier & multiplier */}
                        <div className="flex items-center gap-4">
                            <div
                                className={`flex h-14 w-14 items-center justify-center rounded-full ${tc.badge} text-2xl font-black`}
                            >
                                {currentTier === 'platinum'
                                    ? '💎'
                                    : currentTier === 'gold'
                                      ? '🥇'
                                      : currentTier === 'silver'
                                        ? '🥈'
                                        : '🥉'}
                            </div>
                            <div>
                                <div
                                    className={`inline-flex items-center rounded-full px-3 py-0.5 text-xs font-bold ${tc.badge}`}
                                >
                                    {currentTier.charAt(0).toUpperCase() +
                                        currentTier.slice(1)}{' '}
                                    Member
                                </div>
                                <p className="mt-1 text-sm text-gray-400">
                                    Multiplier coins:{' '}
                                    <span className="font-bold text-white">
                                        {tierInfo?.multiplier ?? 1}x
                                    </span>
                                </p>
                            </div>
                        </div>

                        {/* Progress ke tier berikutnya */}
                        <div className="flex-1 md:max-w-xs">
                            {tierInfo?.next_threshold ? (
                                <>
                                    <div className="mb-2 flex justify-between text-xs text-gray-400">
                                        <span>Total belanja</span>
                                        <span>
                                            {formatCurrency(
                                                tierInfo.total_spending,
                                            )}{' '}
                                            /{' '}
                                            {formatCurrency(
                                                tierInfo.next_threshold,
                                            )}
                                        </span>
                                    </div>
                                    <div className="h-2 w-full overflow-hidden rounded-full bg-white/10">
                                        <div
                                            className={`h-full rounded-full transition-all ${tc.bar}`}
                                            style={{
                                                width: `${progressPercent}%`,
                                            }}
                                        />
                                    </div>
                                    <p className="mt-2 text-xs text-gray-400">
                                        {formatCurrency(
                                            Math.max(
                                                0,
                                                tierInfo.next_threshold -
                                                    tierInfo.total_spending,
                                            ),
                                        )}{' '}
                                        lagi ke tier berikutnya
                                    </p>
                                </>
                            ) : (
                                <div
                                    className={`rounded-xl px-4 py-3 text-center text-sm font-semibold ${tc.badge}`}
                                >
                                    Tier tertinggi telah dicapai! 🎉
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            {/* 3. Promo & Voucher */}
            {promoVouchers.length > 0 && (
                <section>
                    <h2 className="mb-6 text-2xl font-bold text-white">
                        Promo & Voucher
                    </h2>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {promoVouchers.map((v) => {
                            const discountLabel =
                                v.type === 'percent'
                                    ? `${v.value}%${v.max_discount ? ` (maks. ${formatCurrency(v.max_discount)})` : ''}`
                                    : formatCurrency(v.value);
                            const isCopied = copiedCode === v.code;
                            return (
                                <div
                                    key={v.code}
                                    className={`relative overflow-hidden rounded-2xl border p-5 transition ${v.used ? 'border-[#31334c] bg-[#16171f] opacity-60' : 'border-[#31334c] bg-[#1e1f29]'}`}
                                >
                                    {/* Decorative stripe */}
                                    <div className="absolute top-0 left-0 h-full w-1 bg-gradient-to-b from-primary to-[#9b4dec]" />

                                    <div className="pl-3">
                                        {/* Header row */}
                                        <div className="mb-3 flex items-start justify-between gap-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <code className="rounded-lg bg-white/5 px-3 py-1 font-mono text-sm font-bold tracking-widest text-white">
                                                    {v.code}
                                                </code>
                                                {v.min_tier && (
                                                    <span
                                                        className={`rounded-full px-2 py-0.5 text-xs font-semibold ${tierBadgeClass[v.min_tier] ?? 'bg-gray-500/20 text-gray-300'}`}
                                                    >
                                                        {tierIcon[v.min_tier]}{' '}
                                                        {v.min_tier
                                                            .charAt(0)
                                                            .toUpperCase() +
                                                            v.min_tier.slice(1)}
                                                        +
                                                    </span>
                                                )}
                                            </div>
                                            {v.used ? (
                                                <span className="shrink-0 rounded-full bg-gray-600/30 px-3 py-1 text-xs font-semibold text-gray-400">
                                                    Sudah Dipakai
                                                </span>
                                            ) : (
                                                <button
                                                    onClick={() =>
                                                        handleCopy(v.code)
                                                    }
                                                    className="shrink-0 rounded-lg bg-primary/20 px-3 py-1 text-xs font-bold text-primary transition hover:bg-primary/30"
                                                >
                                                    {isCopied
                                                        ? '✓ Tersalin'
                                                        : 'Salin'}
                                                </button>
                                            )}
                                        </div>

                                        {/* Discount info */}
                                        <p className="text-base font-bold text-white">
                                            Diskon {discountLabel}
                                        </p>

                                        {/* Details */}
                                        <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-400">
                                            {v.min_amount > 0 && (
                                                <span>
                                                    Min.{' '}
                                                    {formatCurrency(
                                                        v.min_amount,
                                                    )}
                                                </span>
                                            )}
                                            {v.valid_until && (
                                                <span>
                                                    s/d{' '}
                                                    {formatDate(v.valid_until)}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>
            )}

            {/* 4. Transaksi Hari Ini */}
            <section>
                <h2 className="mb-6 text-2xl font-bold text-white">
                    Transaksi Hari Ini
                </h2>

                <div className="grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-4">
                    {/* Menunggu */}
                    <div className="flex min-h-[120px] flex-col justify-between rounded-xl border border-[#31334c] bg-[#1e1f29]/80 p-5">
                        <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-600/20 text-yellow-500">
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
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                        </div>
                        <div className="flex w-full items-end justify-between">
                            <span className="text-xs font-medium text-gray-400">
                                Menunggu
                            </span>
                            <span className="text-2xl leading-none font-black text-white">
                                {dashboardStats.pending}
                            </span>
                        </div>
                    </div>

                    {/* Proses */}
                    <div className="flex min-h-[120px] flex-col justify-between rounded-xl border border-[#31334c] bg-[#1e1f29]/80 p-5">
                        <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/20 text-blue-500">
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
                                    width="18"
                                    height="18"
                                    x="3"
                                    y="3"
                                    rx="2"
                                />
                                <path d="M7 7h.01" />
                                <path d="M17 7h.01" />
                                <path d="M7 17h.01" />
                                <path d="M17 17h.01" />
                                <path d="M12 2v2" />
                                <path d="M12 20v2" />
                                <path d="M2 12h2" />
                                <path d="M20 12h2" />
                            </svg>
                        </div>
                        <div className="flex w-full items-end justify-between">
                            <span className="text-xs font-medium text-gray-400">
                                Proses
                            </span>
                            <span className="text-2xl leading-none font-black text-white">
                                {dashboardStats.processing}
                            </span>
                        </div>
                    </div>

                    {/* Selesai */}
                    <div className="flex min-h-[120px] flex-col justify-between rounded-xl border border-[#31334c] bg-[#1e1f29]/80 p-5">
                        <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-green-600/20 text-green-500">
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
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </div>
                        <div className="flex w-full items-end justify-between">
                            <span className="text-xs font-medium text-gray-400">
                                Selesai
                            </span>
                            <span className="text-2xl leading-none font-black text-white">
                                {dashboardStats.success}
                            </span>
                        </div>
                    </div>

                    {/* Gagal */}
                    <div className="flex min-h-[120px] flex-col justify-between rounded-xl border border-[#31334c] bg-[#1e1f29]/80 p-5">
                        <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-red-600/20 text-red-500">
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
                                <line x1="15" x2="9" y1="9" y2="15" />
                                <line x1="9" x2="15" y1="9" y2="15" />
                            </svg>
                        </div>
                        <div className="flex w-full items-end justify-between">
                            <span className="text-xs font-medium text-gray-400">
                                Gagal
                            </span>
                            <span className="text-2xl leading-none font-black text-white">
                                {dashboardStats.failed}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            {/* 5. List Transaksi Terbaru */}
            <section>
                <h2 className="mb-6 text-2xl font-bold text-white">
                    List Transaksi Terbaru
                </h2>

                <div className="overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29]">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-[#31334c] bg-white/10 text-xs font-bold text-gray-300 uppercase">
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
                                {recentTransactions.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-6 py-8 text-center text-gray-400"
                                        >
                                            Belum ada transaksi.
                                        </td>
                                    </tr>
                                )}
                                {recentTransactions.map((transaction) => {
                                    const statusBadge =
                                        getTransactionStatusBadge(
                                            transaction.status,
                                        );

                                    return (
                                        <tr
                                            key={transaction.invoice_id}
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
                                                {formatCurrency(
                                                    transaction.amount,
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-gray-300">
                                                {transaction.created_at
                                                    ? formatDate(
                                                          transaction.created_at,
                                                      )
                                                    : '-'}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span
                                                    className={`${statusBadge.className} rounded px-2 py-1 text-[10px] font-bold uppercase`}
                                                >
                                                    {statusBadge.label}
                                                </span>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </UserLayout>
    );
}
