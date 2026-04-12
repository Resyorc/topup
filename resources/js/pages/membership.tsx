import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import GuestLayout from '@/layouts/guest-layout';

interface MembershipProps {
    currentTier: string;
    upgradable: Record<string, number>;
    prices: Record<string, number>;
}

const TIER_INFO = {
    bronze: {
        label: 'Bronze',
        icon: '🥉',
        color: 'from-amber-800 to-amber-600',
        ring: 'ring-amber-600',
        text: 'text-amber-400',
        bg: 'bg-amber-900/20',
        perks: ['Harga standar member', 'Akses program loyalitas', 'Mulai kumpulkan poin'],
    },
    silver: {
        label: 'Silver',
        icon: '🥈',
        color: 'from-slate-500 to-slate-300',
        ring: 'ring-slate-400',
        text: 'text-slate-300',
        bg: 'bg-slate-700/20',
        perks: ['Harga lebih murah dari Bronze', 'Multiplier coin 1.25×', 'Akses voucher eksklusif Silver'],
    },
    gold: {
        label: 'Gold',
        icon: '🥇',
        color: 'from-yellow-600 to-yellow-300',
        ring: 'ring-yellow-400',
        text: 'text-yellow-400',
        bg: 'bg-yellow-900/20',
        perks: ['Harga terbaik kedua', 'Multiplier coin 1.5×', 'Prioritas CS', 'Akses voucher eksklusif Gold'],
    },
    platinum: {
        label: 'Platinum',
        icon: '💎',
        color: 'from-cyan-600 to-cyan-200',
        ring: 'ring-cyan-400',
        text: 'text-cyan-400',
        bg: 'bg-cyan-900/20',
        perks: ['Harga termurah untuk semua produk', 'Multiplier coin 2×', 'Prioritas CS tertinggi', 'Semua voucher eksklusif'],
    },
} as const;

type Tier = keyof typeof TIER_INFO;

function fmt(n: number) {
    return 'Rp ' + n.toLocaleString('id-ID');
}

export default function Membership({ currentTier, upgradable, prices }: MembershipProps) {
    const [loading, setLoading] = useState<string | null>(null);

    const handleUpgrade = (tier: string) => {
        setLoading(tier);
        router.post(
            '/membership/checkout',
            { to_tier: tier },
            {
                onError: () => setLoading(null),
                onFinish: () => setLoading(null),
            }
        );
    };

    const tiers = (['bronze', 'silver', 'gold', 'platinum'] as Tier[]);

    return (
        <GuestLayout>
            <Head title="Upgrade Membership" />

            <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-10 text-center">
                    <h1 className="text-3xl font-black text-white md:text-4xl">Upgrade Membership</h1>
                    <p className="mt-2 text-gray-400">
                        Bayar sekali, nikmati harga member selamanya.
                    </p>
                    <div className="mt-4 inline-flex items-center gap-2 rounded-full border border-[#31334c] bg-[#1e1f29] px-4 py-2 text-sm">
                        <span className="text-gray-400">Tier Anda saat ini:</span>
                        <span className={`font-bold ${TIER_INFO[currentTier as Tier]?.text ?? 'text-white'}`}>
                            {TIER_INFO[currentTier as Tier]?.icon} {TIER_INFO[currentTier as Tier]?.label ?? currentTier}
                        </span>
                    </div>
                </div>

                {/* Tier Cards */}
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {tiers.map((tier) => {
                        const info = TIER_INFO[tier];
                        const price = prices[tier];
                        const isUpgradable = tier in upgradable;
                        const isCurrent = tier === currentTier;
                        const isOwned = !isUpgradable && !isCurrent;

                        return (
                            <div
                                key={tier}
                                className={`relative flex flex-col overflow-hidden rounded-2xl border transition ${
                                    isCurrent
                                        ? `border-2 ${info.ring} ${info.bg}`
                                        : 'border-[#31334c] bg-[#1e1f29] opacity-90'
                                }`}
                            >
                                {/* Badge */}
                                {isCurrent && (
                                    <div className={`absolute right-3 top-3 rounded-full bg-gradient-to-r ${info.color} px-2 py-0.5 text-[10px] font-bold text-white`}>
                                        Tier Anda
                                    </div>
                                )}
                                {isOwned && (
                                    <div className="absolute right-3 top-3 rounded-full bg-green-500/20 px-2 py-0.5 text-[10px] font-bold text-green-400">
                                        ✓ Dimiliki
                                    </div>
                                )}

                                <div className="p-5 flex-1">
                                    {/* Icon & Title */}
                                    <div className="mb-3 text-3xl">{info.icon}</div>
                                    <h3 className={`text-lg font-black ${info.text}`}>{info.label}</h3>

                                    {/* Price */}
                                    {price !== undefined && (
                                        <div className="mt-2">
                                            {isUpgradable ? (
                                                <span className="text-2xl font-black text-white">{fmt(price)}</span>
                                            ) : (
                                                <span className="text-sm text-gray-500">—</span>
                                            )}
                                            {isUpgradable && (
                                                <span className="ml-1 text-xs text-gray-400">sekali bayar</span>
                                            )}
                                            {tier === 'bronze' && (
                                                <span className="text-sm font-bold text-green-400">GRATIS</span>
                                            )}
                                        </div>
                                    )}

                                    {/* Perks */}
                                    <ul className="mt-4 space-y-1.5">
                                        {info.perks.map((p) => (
                                            <li key={p} className="flex items-start gap-2 text-xs text-gray-300">
                                                <span className={`mt-0.5 shrink-0 text-xs ${info.text}`}>✓</span>
                                                {p}
                                            </li>
                                        ))}
                                    </ul>
                                </div>

                                {/* CTA */}
                                <div className="p-4 pt-0">
                                    {isUpgradable ? (
                                        <button
                                            onClick={() => handleUpgrade(tier)}
                                            disabled={loading !== null}
                                            className={`w-full rounded-xl bg-gradient-to-r ${info.color} py-2.5 text-sm font-bold text-white shadow transition hover:opacity-90 disabled:opacity-50`}
                                        >
                                            {loading === tier ? 'Memproses...' : `Upgrade ke ${info.label}`}
                                        </button>
                                    ) : isCurrent ? (
                                        <div className={`w-full rounded-xl py-2.5 text-center text-sm font-bold ${info.text} ${info.bg}`}>
                                            Tier Aktif
                                        </div>
                                    ) : (
                                        <div className="w-full rounded-xl py-2.5 text-center text-xs text-gray-500">
                                            Sudah dimiliki
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Info Box */}
                <div className="mt-10 rounded-2xl border border-[#31334c] bg-[#1e1f29] p-6">
                    <h4 className="mb-3 font-bold text-white">ℹ️ Tentang Upgrade Membership</h4>
                    <ul className="space-y-2 text-sm text-gray-400">
                        <li>• Upgrade bersifat <strong className="text-white">permanen</strong> — tidak ada masa aktif atau berlangganan.</li>
                        <li>• Pembayaran dilakukan via <strong className="text-white">QRIS</strong>, berlaku 24 jam untuk menyelesaikan pembayaran.</li>
                        <li>• Harga member langsung aktif setelah pembayaran dikonfirmasi.</li>
                        <li>• Anda hanya bisa upgrade ke tier yang lebih tinggi dari tier saat ini.</li>
                    </ul>
                </div>
            </div>
        </GuestLayout>
    );
}
