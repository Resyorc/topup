import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh items-center justify-center overflow-hidden bg-background px-4 py-8 text-foreground sm:px-6 lg:px-8">
            <div className="pointer-events-none absolute inset-0 bg-[linear-gradient(145deg,rgba(56,189,248,0.10),transparent_34%),linear-gradient(315deg,rgba(124,58,237,0.12),transparent_38%),linear-gradient(180deg,#070b14_0%,#0b1020_52%,#111827_100%)]" />
            <div className="pointer-events-none absolute inset-0 [background-image:linear-gradient(rgba(255,255,255,0.6)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.6)_1px,transparent_1px)] [background-size:72px_72px] opacity-[0.08]" />

            <div className="relative w-full max-w-5xl">
                <div className="overflow-hidden rounded-3xl border border-[var(--color-border-strong)] bg-[rgba(15,23,42,0.90)] shadow-[0_28px_90px_rgba(0,0,0,0.42)] backdrop-blur-xl">
                    <div className="h-px bg-[linear-gradient(90deg,transparent,var(--color-accent),var(--color-highlight),transparent)]" />

                    <div className="grid lg:grid-cols-[0.92fr_1.08fr]">
                        <aside className="relative hidden min-h-[620px] flex-col justify-between overflow-hidden border-r border-white/10 bg-[linear-gradient(145deg,rgba(56,189,248,0.10),rgba(124,58,237,0.08)_48%,rgba(245,158,11,0.08))] p-10 lg:flex">
                            <div className="pointer-events-none absolute inset-0 [background-image:linear-gradient(135deg,rgba(255,255,255,0.42)_1px,transparent_1px)] [background-size:28px_28px] opacity-[0.13]" />

                            <Link
                                href={home()}
                                className="relative z-10 inline-flex items-center"
                            >
                                <img
                                    src="/logo.png"
                                    alt="Nuvelo"
                                    className="h-13 w-auto"
                                />
                            </Link>

                            <div className="relative z-10 space-y-5">
                                <div className="inline-flex rounded-full border border-[var(--color-accent-border)] bg-[var(--color-accent-soft)] px-3 py-1 text-[11px] font-bold tracking-[0.18em] text-[var(--color-accent)] uppercase">
                                    Nuvelo Account
                                </div>
                                <div>
                                    <h2 className="max-w-sm text-3xl leading-tight font-black tracking-tight text-white">
                                        Masuk, pilih game, lanjut top up.
                                    </h2>
                                    <p className="mt-3 max-w-sm text-sm leading-6 text-gray-300">
                                        Akun menyimpan riwayat transaksi,
                                        voucher, dan akses member dalam satu
                                        tempat.
                                    </p>
                                </div>
                            </div>

                            <div className="relative z-10 grid gap-3">
                                {[
                                    ['01', 'Riwayat rapi'],
                                    ['02', 'Checkout cepat'],
                                    ['03', 'Promo member'],
                                ].map(([num, label]) => (
                                    <div
                                        key={num}
                                        className="flex items-center justify-between rounded-xl border border-white/10 bg-white/[0.055] px-4 py-3"
                                    >
                                        <span className="font-mono text-xs font-bold text-[var(--color-highlight)]">
                                            {num}
                                        </span>
                                        <span className="text-sm font-semibold text-gray-200">
                                            {label}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </aside>

                        <section className="p-6 sm:p-8 lg:p-10">
                            <div className="mx-auto w-full max-w-md">
                                <div className="mb-8 flex flex-col gap-5">
                                    <Link
                                        href={home()}
                                        className="inline-flex items-center lg:hidden"
                                    >
                                        <img
                                            src="/logo.png"
                                            alt="Nuvelo"
                                            className="h-12 w-auto"
                                        />
                                    </Link>

                                    <div className="space-y-2">
                                        <h1 className="text-2xl leading-tight font-black tracking-tight text-white md:text-3xl">
                                            {title}
                                        </h1>
                                        <p className="text-sm leading-6 text-gray-300">
                                            {description}
                                        </p>
                                    </div>
                                </div>

                                <div className="[&_.text-muted-foreground]:text-gray-400 [&_a]:font-semibold [&_a]:text-[var(--color-accent)] [&_a:hover]:text-[var(--color-accent-hover)] [&_button[type='submit']]:h-11 [&_button[type='submit']]:rounded-xl [&_button[type='submit']]:bg-[var(--gradient-primary)] [&_button[type='submit']]:font-bold [&_button[type='submit']]:text-white [&_button[type='submit']]:shadow-[var(--shadow-glow)] [&_button[type='submit']:hover]:opacity-90 [&_input]:h-11 [&_input]:rounded-xl [&_input]:border-[var(--color-border-strong)] [&_input]:bg-[var(--color-bg-secondary)] [&_input]:px-4 [&_input]:text-white [&_input]:placeholder:text-gray-500 [&_input:focus-visible]:border-[var(--color-accent)] [&_input:focus-visible]:ring-[var(--color-accent)] [&_label]:text-sm [&_label]:font-semibold [&_label]:text-gray-200">
                                    {children}
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    );
}
