import { usePage } from '@inertiajs/react';
import UserLayout from '@/layouts/user-layout';
import { formatCurrency } from '@/lib';

const TIERS = [
    {
        key: 'bronze',
        label: 'Bronze',
        icon: '🥉',
        threshold: 0,
        next: 500_000,
        multiplier: 1.0,
        color: { badge: 'bg-orange-500/20 text-orange-300 border-orange-500/40', bar: 'bg-orange-400', ring: 'ring-orange-500/40' },
    },
    {
        key: 'silver',
        label: 'Silver',
        icon: '🥈',
        threshold: 500_000,
        next: 2_000_000,
        multiplier: 1.25,
        color: { badge: 'bg-blue-500/20 text-blue-300 border-blue-500/40', bar: 'bg-blue-400', ring: 'ring-blue-500/40' },
    },
    {
        key: 'gold',
        label: 'Gold',
        icon: '🥇',
        threshold: 2_000_000,
        next: 10_000_000,
        multiplier: 1.5,
        color: { badge: 'bg-yellow-500/20 text-yellow-300 border-yellow-500/40', bar: 'bg-yellow-400', ring: 'ring-yellow-500/40' },
    },
    {
        key: 'platinum',
        label: 'Platinum',
        icon: '💎',
        threshold: 10_000_000,
        next: null,
        multiplier: 2.0,
        color: { badge: 'bg-purple-500/20 text-purple-300 border-purple-500/40', bar: 'bg-purple-400', ring: 'ring-purple-500/40' },
    },
];

const BENEFITS = [
    {
        icon: '🪙',
        title: 'Bonus Krysta Coins',
        description: 'Dapatkan coins lebih banyak setiap transaksi sukses sesuai tier kamu.',
        tiers: { bronze: '1x', silver: '1.25x', gold: '1.5x', platinum: '2x' },
    },
    {
        icon: '🎟️',
        title: 'Voucher Eksklusif Member',
        description: 'Silver ke atas mendapat akses voucher diskon khusus yang tidak tersedia untuk umum.',
        tiers: { bronze: '—', silver: '✅', gold: '✅', platinum: '✅' },
    },
];

export default function MemberClub() {
    const { auth } = usePage().props as any;
    const currentTier: string = auth?.user?.tier ?? 'bronze';
    const currentTierData = TIERS.find(t => t.key === currentTier) ?? TIERS[0];
    const isTopTier = currentTier === 'platinum';

    return (
        <UserLayout title="Nuvelo Member Club">
            {/* Header */}
            <div>
                <h2 className="text-2xl font-bold text-white">Nuvelo Member Club</h2>
                <p className="mt-1 text-sm text-gray-400">
                    Semakin banyak belanja, semakin besar reward yang kamu dapatkan.
                </p>
            </div>

            {/* Status kamu sekarang */}
            <div className={`rounded-2xl border bg-[#1e1f29] p-6 md:p-8 ${currentTierData.color.badge.split(' ')[2]}`}>
                <p className="mb-4 text-xs font-semibold uppercase tracking-widest text-gray-500">Status kamu saat ini</p>
                <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div className="flex items-center gap-4">
                        <div className={`flex h-16 w-16 items-center justify-center rounded-full ring-2 text-3xl ${currentTierData.color.ring}`}>
                            {currentTierData.icon}
                        </div>
                        <div>
                            <div className={`inline-flex items-center rounded-full border px-3 py-0.5 text-sm font-bold ${currentTierData.color.badge}`}>
                                {currentTierData.label} Member
                            </div>
                            <p className="mt-1 text-sm text-gray-400">
                                Multiplier coins aktif: <span className="font-bold text-white">{currentTierData.multiplier}x</span>
                            </p>
                        </div>
                    </div>

                    <div className="flex-1 md:max-w-xs">
                        {isTopTier ? (
                            <div className={`rounded-xl border px-6 py-3 text-center text-sm font-semibold ${currentTierData.color.badge}`}>
                                Tier tertinggi telah dicapai! 🎉
                            </div>
                        ) : (
                            <div className="flex flex-col gap-2">
                                <p className="text-xs text-gray-400">
                                    Upgrade ke tier lebih tinggi untuk mendapatkan harga lebih murah &amp; multiplier coin lebih besar.
                                </p>
                                <a
                                    href="/membership"
                                    className="w-full rounded-xl bg-gradient-to-r from-primary to-[#9b4dec] py-2.5 text-center text-sm font-bold text-white shadow transition hover:opacity-90"
                                >
                                    Upgrade Membership →
                                </a>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Tabel tier */}
            <div>
                <h3 className="mb-4 text-lg font-bold text-white">Tingkatan Tier</h3>
                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {TIERS.map((tier) => (
                        <div
                            key={tier.key}
                            className={`rounded-2xl border bg-[#1e1f29] p-5 transition ${tier.key === currentTier ? `ring-2 ${tier.color.ring}` : 'border-[#31334c]'}`}
                        >
                            <div className="mb-3 text-3xl">{tier.icon}</div>
                            <div className={`mb-1 inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold ${tier.color.badge}`}>
                                {tier.label}
                            </div>
                            <p className="mt-3 text-xs text-gray-500">Akses via</p>
                            <p className="text-sm font-bold text-white">
                                {tier.key === 'bronze' ? 'Gratis (default)' : 'Upgrade Membership'}
                            </p>
                            <p className="mt-2 text-xs text-gray-500">Multiplier coins</p>
                            <p className="text-lg font-black text-white">{tier.multiplier}x</p>
                            {tier.key === currentTier && (
                                <div className="mt-3 rounded-lg bg-white/5 px-2 py-1 text-center text-xs font-semibold text-gray-300">
                                    Tier kamu sekarang
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            {/* Benefit per Tier */}
            <div>
                <h3 className="mb-4 text-lg font-bold text-white">Benefit per Tier</h3>

                {/* Mobile: card per benefit */}
                <div className="flex flex-col gap-4 md:hidden">
                    {BENEFITS.map((benefit, i) => (
                        <div key={i} className="rounded-2xl border border-[#31334c] bg-[#1e1f29] p-4">
                            <div className="mb-3 flex items-start gap-2">
                                <span className="text-xl">{benefit.icon}</span>
                                <div>
                                    <p className="font-semibold text-white">{benefit.title}</p>
                                    <p className="mt-0.5 text-xs text-gray-500">{benefit.description}</p>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                {TIERS.map(tier => (
                                    <div key={tier.key} className={`flex items-center justify-between rounded-lg px-3 py-2 ${tier.key === currentTier ? tier.color.badge : 'bg-white/5 text-gray-400'}`}>
                                        <span className="text-xs font-semibold">{tier.icon} {tier.label}</span>
                                        <span className="text-xs font-bold">{benefit.tiers[tier.key as keyof typeof benefit.tiers]}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>

                {/* Desktop: tabel */}
                <div className="hidden overflow-hidden rounded-2xl border border-[#31334c] md:block">
                    <div className="grid grid-cols-6 gap-px bg-[#31334c]">
                        <div className="col-span-2 bg-[#1e1f29] px-4 py-3 text-xs font-semibold text-gray-400">Benefit</div>
                        {TIERS.map(tier => (
                            <div key={tier.key} className={`bg-[#1e1f29] px-3 py-3 text-center text-xs font-bold ${tier.key === currentTier ? tier.color.badge.split(' ')[1] : 'text-gray-400'}`}>
                                {tier.icon} {tier.label}
                            </div>
                        ))}
                    </div>
                    {BENEFITS.map((benefit, i) => (
                        <div key={i} className="grid grid-cols-6 gap-px bg-[#31334c]">
                            <div className="col-span-2 bg-[#1A1A24] px-4 py-4">
                                <div className="flex items-start gap-2">
                                    <span className="text-lg">{benefit.icon}</span>
                                    <div>
                                        <p className="text-sm font-semibold text-white">{benefit.title}</p>
                                        <p className="mt-0.5 text-xs text-gray-500">{benefit.description}</p>
                                    </div>
                                </div>
                            </div>
                            {TIERS.map(tier => (
                                <div key={tier.key} className={`flex items-center justify-center bg-[#1A1A24] px-3 py-4 text-sm font-semibold ${tier.key === currentTier ? tier.color.badge.split(' ')[1] : 'text-gray-400'}`}>
                                    {benefit.tiers[tier.key as keyof typeof benefit.tiers]}
                                </div>
                            ))}
                        </div>
                    ))}
                </div>
            </div>

            {/* Catatan */}
            <div className="rounded-xl border border-[#31334c] bg-[#1e1f29] p-5 text-sm text-gray-400">
                <p className="mb-1 font-semibold text-white">📋 Cara kerja tier membership</p>
                <ul className="list-inside list-disc space-y-1">
                    <li>Tier diupgrade dengan hanya <span className="text-white">satu kali bayar</span> secara permanen.</li>
                    <li>Harga member (lebih murah) langsung aktif setelah pembayaran dikonfirmasi.</li>
                    <li>1 Krysta Coin = Rp 1, bisa digunakan sebagai saldo metode pembayaran.</li>
                    <li>Coins tidak didapatkan untuk transaksi yang dibayar menggunakan saldo Krysta Coin.</li>
                </ul>
            </div>
        </UserLayout>
    );
}
