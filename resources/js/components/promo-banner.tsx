import { Link } from '@inertiajs/react';

export default function PromoBanner() {
    return (
        <section className="mx-auto mb-16 max-w-7xl px-4 py-8">
            <div className="relative overflow-hidden rounded-3xl border border-[var(--color-highlight-border)] bg-[#070b1a]/80 shadow-[0_24px_80px_rgba(0,0,0,0.34)] backdrop-blur-md">
                <div className="absolute inset-x-0 top-0 h-px bg-[linear-gradient(90deg,transparent,rgba(245,158,11,0.78),rgba(56,189,248,0.55),transparent)]" />
                <div className="absolute inset-y-0 right-0 w-2/3 bg-[radial-gradient(circle_at_65%_40%,rgba(245,158,11,0.18),transparent_34%),radial-gradient(circle_at_82%_70%,rgba(56,189,248,0.16),transparent_38%)]" />
                <div className="absolute right-8 bottom-8 hidden h-36 w-36 rounded-full border border-[var(--color-accent-border)] md:block" />
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
                        <div className="mb-4 inline-flex w-fit items-center gap-2 rounded-full border border-[var(--color-highlight-border)] bg-[var(--color-highlight-soft)] px-3 py-1 text-xs font-black tracking-[0.18em] text-[var(--color-highlight)] uppercase">
                            Member Promo
                        </div>

                        <h2 className="max-w-3xl text-2xl leading-tight font-black tracking-tight text-white uppercase md:text-4xl">
                            Gabung & Nikmati Promo Eksklusif
                        </h2>

                        <p className="mt-3 max-w-xl text-sm leading-6 text-gray-300 md:text-base">
                            Daftar akun di{' '}
                            <strong className="text-[var(--color-accent)]">
                                Nuvelo
                            </strong>{' '}
                            untuk membuka akses diskon khusus, bonus voucher,
                            dan promo mingguan yang hanya tersedia untuk member.
                        </p>

                        <Link
                            href="/register"
                            className="mt-7 inline-flex w-fit items-center gap-2 rounded-full border border-[var(--color-brand-border)] bg-[var(--gradient-primary)] px-7 py-3 text-sm font-bold text-white shadow-[var(--shadow-glow)] transition hover:-translate-y-1 hover:border-[var(--color-brand-light)] hover:shadow-[0_0_36px_rgba(168,85,247,0.35)]"
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
                    <span className="text-[var(--color-highlight)]">
                        Nuvelo Rewards
                    </span>
                </div>
            </div>
        </section>
    );
}
