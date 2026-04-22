import { Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';

interface FlashSaleInfo {
    id: string;
    name: string;
    clean_name: string;
    game_name: string;
    game_slug: string;
    flash_sale_price: number;
    regular_price: number;
    discount_percent: number;
    flash_sale_ends_at: number; // unix timestamp
}

interface PromoBannerProps {
    activeFlashSale?: FlashSaleInfo | null;
}

export default function PromoBanner({ activeFlashSale = null }: PromoBannerProps) {
    const [countdown, setCountdown] = useState<{ h: string; m: string; s: string } | null>(null);

    useEffect(() => {
        if (!activeFlashSale?.flash_sale_ends_at) {
            setCountdown(null);
            return;
        }

        const endMs = activeFlashSale.flash_sale_ends_at * 1000;

        const update = () => {
            const diff = Math.max(0, Math.floor((endMs - Date.now()) / 1000));
            setCountdown({
                h: String(Math.floor(diff / 3600)).padStart(2, '0'),
                m: String(Math.floor((diff % 3600) / 60)).padStart(2, '0'),
                s: String(diff % 60).padStart(2, '0'),
            });
        };

        update();
        const id = setInterval(update, 1000);
        return () => clearInterval(id);
    }, [activeFlashSale?.flash_sale_ends_at]);

    if (activeFlashSale && countdown) {
        return (
            <section className="mx-auto mb-16 max-w-7xl px-4 py-8">
                <Link href={`/order/${activeFlashSale.game_slug}`}>
                    <div className="relative overflow-hidden rounded-3xl border border-orange-500/30 bg-linear-to-r from-[var(--color-bg-main)] via-[var(--color-bg-secondary)] to-[var(--color-bg-main)] p-1 shadow-xl transition hover:border-orange-500/60">
                        {/* Dots Pattern */}
                        <div
                            className="absolute inset-0 opacity-10"
                            style={{
                                backgroundImage: 'radial-gradient(circle, #fff 1px, transparent 1px)',
                                backgroundSize: '16px 16px',
                            }}
                        />

                        <div className="relative z-10 flex flex-col items-center px-6 py-12 text-center">
                            <div className="mb-2 inline-flex items-center gap-1.5 rounded-full bg-orange-500/20 px-3 py-1 text-xs font-bold text-orange-400 uppercase tracking-wider">
                                ⚡ Flash Sale
                            </div>

                            <h2 className="mt-3 bg-linear-to-r from-orange-300 via-yellow-400 to-orange-500 bg-clip-text text-2xl font-black text-transparent uppercase italic drop-shadow-[0_2px_2px_rgba(0,0,0,0.8)] md:text-4xl">
                                {activeFlashSale.clean_name}
                            </h2>

                            <p className="mt-2 text-sm text-gray-300 md:text-base">
                                {activeFlashSale.game_name} &middot; Hemat{' '}
                                <span className="font-bold text-orange-400">{activeFlashSale.discount_percent}%</span>
                            </p>

                            <div className="mt-4 flex items-center gap-3">
                                <span className="text-2xl font-black text-white md:text-3xl">
                                    Rp {activeFlashSale.flash_sale_price.toLocaleString('id-ID')}
                                </span>
                                <span className="text-sm text-gray-500 line-through">
                                    Rp {activeFlashSale.regular_price.toLocaleString('id-ID')}
                                </span>
                            </div>

                            {/* Countdown Timer */}
                            <div className="mt-6">
                                <p className="mb-3 text-xs uppercase tracking-widest text-gray-400">Berakhir dalam</p>
                                <div className="flex items-center justify-center gap-2">
                                    {[
                                        { val: countdown.h, label: 'Jam' },
                                        { val: countdown.m, label: 'Menit' },
                                        { val: countdown.s, label: 'Detik' },
                                    ].map(({ val, label }, i) => (
                                        <div key={i} className="flex items-center gap-2">
                                            <div className="flex h-16 w-16 flex-col items-center justify-center rounded-xl border border-orange-500/30 bg-orange-500/10">
                                                <span className="text-2xl font-black leading-none text-orange-400">{val}</span>
                                                <span className="mt-0.5 text-[9px] text-orange-300/70 uppercase">{label}</span>
                                            </div>
                                            {i < 2 && (
                                                <span className="text-xl font-black text-orange-300/60">:</span>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="mt-8 rounded-full bg-orange-500 px-8 py-3 font-bold text-white shadow-lg shadow-orange-500/30 transition hover:bg-orange-400">
                                Beli Sekarang →
                            </div>
                        </div>
                    </div>
                </Link>
            </section>
        );
    }

    return (
        <section className="mx-auto mb-16 max-w-7xl px-4 py-8">
            <div className="relative overflow-hidden rounded-3xl border border-border bg-linear-to-r from-[var(--color-bg-secondary)] via-[var(--color-bg-card)] to-[var(--color-bg-secondary)] p-1 shadow-xl">
                {/* Dots Pattern */}
                <div
                    className="absolute inset-0 opacity-10"
                    style={{
                        backgroundImage:
                            'radial-gradient(circle, #fff 1px, transparent 1px)',
                        backgroundSize: '16px 16px',
                    }}
                ></div>

                <div className="relative z-10 flex flex-col items-center px-6 py-12 text-center">
                    <h2
                        className="border-b-2 border-transparent bg-linear-to-r from-yellow-300 via-yellow-400 to-amber-500 bg-clip-text text-2xl font-black text-transparent uppercase italic drop-shadow-[0_2px_2px_rgba(0,0,0,0.8)] md:text-4xl"
                        style={{ WebkitTextStroke: '1px rgba(0,0,0,0.3)' }}
                    >
                        Gabung & Nikmati Promo Eksklusif!
                    </h2>
                    <p className="mx-auto mt-4 max-w-xl text-sm text-gray-300 md:text-base">
                        Daftar akun di{' '}
                        <strong className="text-yellow-400">Nuvelo</strong> dan
                        dapatkan akses ke diskon khusus, bonus voucher, dan
                        promo mingguan yang hanya tersedia untuk member.
                    </p>
                    <Link
                        href="/register"
                        className="hover:bg-primary-hover mt-8 transform rounded-full bg-primary px-8 py-3 font-bold text-white shadow-lg transition hover:-translate-y-1 hover:shadow-primary/50"
                    >
                        Daftar Sekarang
                    </Link>
                </div>
            </div>
        </section>
    );
}


