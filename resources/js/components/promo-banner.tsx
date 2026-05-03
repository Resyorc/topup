import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface FlashSaleInfo {
    id: string;
    name: string;
    clean_name: string;
    game_name: string;
    game_slug: string;
    flash_sale_price: number;
    regular_price: number;
    discount_percent: number;
    flash_sale_ends_at: number;
}

interface PromoBannerProps {
    activeFlashSale?: FlashSaleInfo | null;
}

export default function PromoBanner({
    activeFlashSale = null,
}: PromoBannerProps) {
    const [countdown, setCountdown] = useState<{
        h: string;
        m: string;
        s: string;
    } | null>(null);

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
                <Link
                    href={`/order/${activeFlashSale.game_slug}`}
                    className="group block"
                >
                    <div className="relative overflow-hidden rounded-3xl border border-orange-400/30 bg-[#070b1a]/80 shadow-[0_24px_80px_rgba(0,0,0,0.38)] backdrop-blur-md transition duration-300 hover:border-orange-300/60">
                        <div className="absolute inset-x-0 top-0 h-px bg-[linear-gradient(90deg,transparent,rgba(249,115,22,0.85),rgba(14,165,233,0.55),transparent)]" />
                        <div className="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_70%_35%,rgba(249,115,22,0.22),transparent_34%),radial-gradient(circle_at_70%_80%,rgba(14,165,233,0.18),transparent_38%)]" />
                        <div className="absolute -top-24 -right-20 h-72 w-72 rounded-full border border-orange-400/20" />
                        <div className="absolute -bottom-28 -left-20 h-80 w-80 rounded-full border border-cyan-400/15" />
                        <div
                            className="absolute inset-0 opacity-[0.08]"
                            style={{
                                backgroundImage:
                                    'linear-gradient(135deg, #fff 1px, transparent 1px)',
                                backgroundSize: '26px 26px',
                            }}
                        />

                        <div className="relative z-10 grid gap-8 px-5 py-7 sm:px-7 md:grid-cols-[1.15fr_0.85fr] md:px-9 md:py-9">
                            <div className="flex flex-col justify-center">
                                <div className="mb-4 inline-flex w-fit items-center gap-2 rounded-full border border-orange-300/30 bg-orange-500/15 px-3 py-1 text-xs font-black tracking-[0.18em] text-orange-300 uppercase">
                                    Flash Sale Live
                                </div>

                                <h2 className="max-w-3xl text-2xl leading-tight font-black tracking-tight text-white uppercase md:text-4xl">
                                    {activeFlashSale.clean_name}
                                </h2>

                                <p className="mt-3 max-w-xl text-sm leading-6 text-gray-300 md:text-base">
                                    {activeFlashSale.game_name} sedang masuk
                                    periode harga spesial dengan kuota dan waktu
                                    promo terbatas.
                                </p>

                                <div className="mt-6 flex flex-wrap items-end gap-x-4 gap-y-2">
                                    <span className="text-3xl font-black text-white md:text-5xl">
                                        Rp{' '}
                                        {activeFlashSale.flash_sale_price.toLocaleString(
                                            'id-ID',
                                        )}
                                    </span>
                                    <span className="pb-1 text-sm font-semibold text-gray-500 line-through md:text-base">
                                        Rp{' '}
                                        {activeFlashSale.regular_price.toLocaleString(
                                            'id-ID',
                                        )}
                                    </span>
                                </div>

                                <div className="mt-5 flex flex-wrap gap-2">
                                    <span className="rounded-md border border-orange-300/25 bg-orange-500/10 px-3 py-2 text-xs font-bold text-orange-200">
                                        Hemat {activeFlashSale.discount_percent}
                                        %
                                    </span>
                                    <span className="rounded-md border border-cyan-300/20 bg-cyan-400/10 px-3 py-2 text-xs font-bold text-cyan-200">
                                        Stok & waktu terbatas
                                    </span>
                                </div>
                            </div>

                            <div className="relative flex items-center">
                                <div className="w-full rounded-2xl border border-white/10 bg-white/[0.06] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.08)]">
                                    <div className="mb-4 flex items-center justify-between gap-3">
                                        <p className="text-xs font-bold tracking-[0.2em] text-gray-400 uppercase">
                                            Berakhir Dalam
                                        </p>
                                        <span className="h-2 w-2 rounded-full bg-orange-400 shadow-[0_0_18px_rgba(251,146,60,0.95)]" />
                                    </div>

                                    <div className="grid grid-cols-3 gap-2">
                                        {[
                                            { val: countdown.h, label: 'Jam' },
                                            {
                                                val: countdown.m,
                                                label: 'Menit',
                                            },
                                            {
                                                val: countdown.s,
                                                label: 'Detik',
                                            },
                                        ].map(({ val, label }) => (
                                            <div
                                                key={label}
                                                className="rounded-xl border border-orange-300/25 bg-black/25 px-2 py-4 text-center"
                                            >
                                                <span className="block text-2xl leading-none font-black text-orange-300 md:text-3xl">
                                                    {val}
                                                </span>
                                                <span className="mt-2 block text-[10px] font-bold tracking-widest text-orange-100/60 uppercase">
                                                    {label}
                                                </span>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="mt-4 rounded-xl border border-cyan-300/15 bg-cyan-300/10 px-4 py-3">
                                        <p className="text-xs leading-5 font-medium text-cyan-100/80">
                                            Promo dapat berubah saat waktu
                                            berakhir atau kuota habis.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="relative z-10 flex items-center justify-between border-t border-white/10 px-5 py-3 text-[11px] font-bold tracking-[0.18em] text-gray-500 uppercase sm:px-7 md:px-9">
                            <span>Limited Time Deal</span>
                            <span className="text-orange-300 transition group-hover:text-cyan-200">
                                Promo Aktif
                            </span>
                        </div>
                    </div>
                </Link>
            </section>
        );
    }

    return (
        <section className="mx-auto mb-16 max-w-7xl px-4 py-8">
            <div className="relative overflow-hidden rounded-3xl border border-cyan-300/20 bg-[#070b1a]/80 shadow-[0_24px_80px_rgba(0,0,0,0.34)] backdrop-blur-md">
                <div className="absolute inset-x-0 top-0 h-px bg-[linear-gradient(90deg,transparent,rgba(168,85,247,0.85),rgba(14,165,233,0.65),transparent)]" />
                <div className="absolute inset-y-0 right-0 w-2/3 bg-[radial-gradient(circle_at_65%_40%,rgba(168,85,247,0.22),transparent_34%),radial-gradient(circle_at_82%_70%,rgba(14,165,233,0.18),transparent_38%)]" />
                <div className="absolute right-8 bottom-8 hidden h-36 w-36 rounded-full border border-cyan-300/15 md:block" />
                <div
                    className="absolute inset-0 opacity-[0.08]"
                    style={{
                        backgroundImage:
                            'linear-gradient(135deg, #fff 1px, transparent 1px)',
                        backgroundSize: '26px 26px',
                    }}
                />

                <div className="relative z-10 grid gap-8 px-5 py-7 sm:px-7 md:grid-cols-[1.15fr_0.85fr] md:px-9 md:py-9">
                    <div className="flex flex-col justify-center">
                        <div className="mb-4 inline-flex w-fit items-center gap-2 rounded-full border border-cyan-300/25 bg-cyan-400/10 px-3 py-1 text-xs font-black tracking-[0.18em] text-cyan-200 uppercase">
                            Member Promo
                        </div>

                        <h2 className="max-w-3xl text-2xl leading-tight font-black tracking-tight text-white uppercase md:text-4xl">
                            Gabung & Nikmati Promo Eksklusif
                        </h2>

                        <p className="mt-3 max-w-xl text-sm leading-6 text-gray-300 md:text-base">
                            Daftar akun di{' '}
                            <strong className="text-cyan-200">Nuvelo</strong>{' '}
                            untuk membuka akses diskon khusus, bonus voucher,
                            dan promo mingguan yang hanya tersedia untuk member.
                        </p>

                        <Link
                            href="/register"
                            className="mt-7 inline-flex w-fit items-center gap-2 rounded-full border border-cyan-300/35 bg-[linear-gradient(135deg,rgba(124,58,237,0.96),rgba(14,165,233,0.9))] px-7 py-3 text-sm font-bold text-white shadow-[0_0_28px_rgba(168,85,247,0.3)] transition hover:-translate-y-1 hover:border-cyan-300/65 hover:shadow-[0_0_36px_rgba(14,165,233,0.35)]"
                        >
                            Daftar Sekarang
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M5 12h14" />
                                <path d="m12 5 7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-3 md:grid-cols-1">
                        {[
                            {
                                label: 'Promo Mingguan',
                                detail: 'Penawaran khusus untuk akun member.',
                            },
                            {
                                label: 'Bonus Voucher',
                                detail: 'Voucher pilihan saat promo aktif.',
                            },
                            {
                                label: 'Transaksi Rapi',
                                detail: 'Riwayat order tersimpan otomatis.',
                            },
                        ].map((item) => (
                            <div
                                key={item.label}
                                className="rounded-2xl border border-white/10 bg-white/[0.06] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.08)]"
                            >
                                <p className="text-sm font-bold text-white">
                                    {item.label}
                                </p>
                                <p className="mt-1 text-xs leading-5 text-gray-400">
                                    {item.detail}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="relative z-10 flex items-center justify-between border-t border-white/10 px-5 py-3 text-[11px] font-bold tracking-[0.18em] text-gray-500 uppercase sm:px-7 md:px-9">
                    <span>Exclusive Member Access</span>
                    <span className="text-cyan-200">Nuvelo Rewards</span>
                </div>
            </div>
        </section>
    );
}
