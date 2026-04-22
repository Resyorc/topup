import { Head } from '@inertiajs/react';
import { useState, useRef } from 'react';
import GuestLayout from '@/layouts/guest-layout';

interface ResellerProps {
    waNumber: string;
}

const BENEFITS = [
    {
        icon: (
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
            </svg>
        ),
        title: 'Platform Siap Pakai',
        desc: 'Website top up game lengkap sudah siap — tidak perlu bangun dari nol. Langsung jualan hari ini.',
    },
    {
        icon: (
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
            </svg>
        ),
        title: 'Produk Lengkap & Update Otomatis',
        desc: 'Ratusan produk dari berbagai game populer. Harga dan ketersediaan sync otomatis dari provider.',
    },
    {
        icon: (
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z"/><path d="M12 8v4l3 3"/>
            </svg>
        ),
        title: 'Atur Margin Sendiri',
        desc: 'Kamu yang menentukan harga jual. Margin bebas — semakin kompetitif harga, semakin banyak pembeli.',
    },
    {
        icon: (
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/>
            </svg>
        ),
        title: 'Pembayaran Lengkap',
        desc: 'QRIS, E-Wallet, Virtual Account, hingga COD sudah tersedia. Pelanggan tidak perlu ribet.',
    },
    {
        icon: (
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/>
            </svg>
        ),
        title: 'Branding Kamu Sendiri',
        desc: 'Logo, nama toko, domain — semua pakai identitas kamu. Pelanggan tidak tahu kamu pakai platform kami.',
    },
    {
        icon: (
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/>
            </svg>
        ),
        title: 'Dashboard & Laporan',
        desc: 'Pantau transaksi, pendapatan, dan performa produk secara real-time lewat dashboard admin.',
    },
];

const STEPS = [
    { num: '01', title: 'Isi Formulir', desc: 'Lengkapi form di bawah dengan nama, nomor WhatsApp, dan nama brand yang kamu inginkan.' },
    { num: '02', title: 'Diskusi dengan Admin', desc: 'Tim kami akan menghubungi kamu via WhatsApp untuk mendiskusikan kebutuhan dan paket yang sesuai.' },
    { num: '03', title: 'Website Aktif & Jualan', desc: 'Setelah setup selesai, website kamu langsung aktif dan siap menerima transaksi.' },
];

export default function Reseller({ waNumber }: ResellerProps) {
    const formRef = useRef<HTMLDivElement>(null);
    const [form, setForm] = useState({
        name: '',
        phone: '',
        brand: '',
        domain: '',
        city: '',
        message: '',
    });
    const [submitted, setSubmitted] = useState(false);

    function scrollToForm() {
        formRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) {
        setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        const text = [
            `Halo Admin Nuvelo, saya tertarik menjadi reseller! 🙌`,
            ``,
            `*Nama:* ${form.name}`,
            `*No. WhatsApp:* ${form.phone}`,
            `*Nama Brand:* ${form.brand}`,
            `*Domain Diinginkan:* ${form.domain || '-'}`,
            `*Kota:* ${form.city || '-'}`,
            form.message ? `*Pesan:* ${form.message}` : '',
        ].filter(Boolean).join('\n');

        const wa = waNumber.replace(/\D/g, '');
        window.open(`https://wa.me/${wa}?text=${encodeURIComponent(text)}`, '_blank');
        setSubmitted(true);
    }

    return (
        <GuestLayout>
            <Head title="Jadilah Reseller Nuvelo — Buka Bisnis Top Up Game Sendiri" />

            {/* ===== HERO ===== */}
            <section className="relative overflow-hidden bg-[var(--color-bg-main)] py-20 md:py-28">
                {/* background glow */}
                <div className="pointer-events-none absolute -top-32 left-1/2 h-[500px] w-[700px] -translate-x-1/2 rounded-full bg-primary/10 blur-[120px]" />

                <div className="relative mx-auto max-w-4xl px-4 text-center sm:px-6">
                    <span className="mb-4 inline-block rounded-full border border-primary/30 bg-primary/10 px-4 py-1 text-xs font-semibold tracking-widest text-primary uppercase">
                        Program Reseller
                    </span>
                    <h1 className="mt-2 text-3xl font-black leading-tight text-white sm:text-5xl md:text-6xl">
                        Buka Bisnis{' '}
                        <span className="bg-gradient-to-r from-primary to-[var(--color-primary-light)] bg-clip-text text-transparent">
                            Top Up Game
                        </span>{' '}
                        Sendiri
                    </h1>
                    <p className="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-gray-400 md:text-base">
                        Dapatkan platform top up game siap pakai dengan branding kamu sendiri.
                        Ratusan produk, pembayaran lengkap, dan dashboard admin — tanpa ribet bangun dari nol.
                    </p>
                    <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <button
                            onClick={scrollToForm}
                            className="rounded-xl bg-gradient-to-r from-primary to-[var(--color-primary-light)] px-8 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/30 transition hover:opacity-90"
                        >
                            Daftar Sekarang — Gratis
                        </button>
                        <button
                            onClick={scrollToForm}
                            className="rounded-xl border border-white/10 bg-white/5 px-8 py-3.5 text-sm font-semibold text-gray-300 transition hover:bg-white/10"
                        >
                            Pelajari Lebih Lanjut ↓
                        </button>
                    </div>

                    {/* Quick stats */}
                    <div className="mt-12 flex flex-wrap items-center justify-center gap-6 md:gap-12">
                        {[
                            { val: '100+', label: 'Produk Game' },
                            { val: '24/7', label: 'Proses Otomatis' },
                            { val: '100%', label: 'White Label' },
                        ].map((s) => (
                            <div key={s.label} className="text-center">
                                <p className="text-2xl font-black text-white md:text-3xl">{s.val}</p>
                                <p className="mt-0.5 text-xs text-gray-500">{s.label}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ===== BENEFITS ===== */}
            <section className="bg-[var(--color-bg-main)] py-16 md:py-20">
                <div className="mx-auto max-w-6xl px-4 sm:px-6">
                    <div className="mb-10 text-center">
                        <h2 className="text-2xl font-black text-white md:text-3xl">Kenapa Jadi Reseller Nuvelo?</h2>
                        <p className="mt-2 text-sm text-gray-400">Semua yang kamu butuhkan untuk mulai bisnis top up sudah tersedia.</p>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {BENEFITS.map((b) => (
                            <div key={b.title} className="rounded-2xl border border-white/5 bg-[var(--color-bg-card)] p-6 transition hover:border-primary/30">
                                <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    {b.icon}
                                </div>
                                <h3 className="text-sm font-bold text-white">{b.title}</h3>
                                <p className="mt-1.5 text-xs leading-relaxed text-gray-400">{b.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ===== HOW IT WORKS ===== */}
            <section className="bg-[var(--color-bg-main)] py-16 md:py-20">
                <div className="mx-auto max-w-4xl px-4 sm:px-6">
                    <div className="mb-10 text-center">
                        <h2 className="text-2xl font-black text-white md:text-3xl">Cara Bergabung</h2>
                        <p className="mt-2 text-sm text-gray-400">Tiga langkah mudah untuk memiliki website top up kamu sendiri.</p>
                    </div>
                    <div className="relative flex flex-col gap-6 md:flex-row">
                        {/* connector line desktop */}
                        <div className="absolute top-8 left-0 hidden h-0.5 w-full bg-gradient-to-r from-primary/30 via-primary/10 to-transparent md:block" />
                        {STEPS.map((s) => (
                            <div key={s.num} className="relative flex-1 rounded-2xl border border-white/5 bg-[var(--color-bg-card)] p-6">
                                <span className="mb-4 block text-4xl font-black text-primary/20">{s.num}</span>
                                <h3 className="text-sm font-bold text-white">{s.title}</h3>
                                <p className="mt-1.5 text-xs leading-relaxed text-gray-400">{s.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ===== FORM ===== */}
            <section ref={formRef} className="bg-[var(--color-bg-main)] py-16 md:py-20">
                <div className="mx-auto max-w-xl px-4 sm:px-6">
                    <div className="mb-8 text-center">
                        <h2 className="text-2xl font-black text-white md:text-3xl">Mulai Sekarang</h2>
                        <p className="mt-2 text-sm text-gray-400">
                            Isi formulir di bawah — tim kami akan menghubungi kamu via WhatsApp.
                        </p>
                    </div>

                    {submitted ? (
                        <div className="rounded-2xl border border-green-500/20 bg-green-500/5 p-8 text-center">
                            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-500/10">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-green-400">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                            </div>
                            <h3 className="text-base font-bold text-white">Formulir Terkirim!</h3>
                            <p className="mt-2 text-sm text-gray-400">
                                WhatsApp sudah terbuka dengan pesan yang sudah diisi. Kirimkan ke admin kami dan kami akan segera membalas.
                            </p>
                            <button
                                onClick={() => setSubmitted(false)}
                                className="mt-5 text-xs text-primary underline underline-offset-2"
                            >
                                Kirim ulang
                            </button>
                        </div>
                    ) : (
                        <form onSubmit={handleSubmit} className="flex flex-col gap-4 rounded-2xl border border-white/5 bg-[var(--color-bg-card)] p-6 md:p-8">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <label className="text-xs font-semibold text-gray-300">Nama Lengkap <span className="text-red-400">*</span></label>
                                    <input
                                        name="name"
                                        value={form.name}
                                        onChange={handleChange}
                                        required
                                        placeholder="Nama kamu"
                                        className="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-primary"
                                    />
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <label className="text-xs font-semibold text-gray-300">Nomor WhatsApp <span className="text-red-400">*</span></label>
                                    <input
                                        name="phone"
                                        value={form.phone}
                                        onChange={handleChange}
                                        required
                                        placeholder="08xxxxxxxxxx"
                                        className="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-primary"
                                    />
                                </div>
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <label className="text-xs font-semibold text-gray-300">Nama Brand / Toko <span className="text-red-400">*</span></label>
                                <input
                                    name="brand"
                                    value={form.brand}
                                    onChange={handleChange}
                                    required
                                    placeholder="Contoh: Gaming Store, TopUp Jaya"
                                    className="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-primary"
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <label className="text-xs font-semibold text-gray-300">Domain yang Diinginkan</label>
                                    <input
                                        name="domain"
                                        value={form.domain}
                                        onChange={handleChange}
                                        placeholder="Contoh: gamingstore.id"
                                        className="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-primary"
                                    />
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <label className="text-xs font-semibold text-gray-300">Kota</label>
                                    <input
                                        name="city"
                                        value={form.city}
                                        onChange={handleChange}
                                        placeholder="Contoh: Jakarta, Surabaya"
                                        className="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-primary"
                                    />
                                </div>
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <label className="text-xs font-semibold text-gray-300">Pesan Tambahan <span className="text-gray-500">(opsional)</span></label>
                                <textarea
                                    name="message"
                                    value={form.message}
                                    onChange={handleChange}
                                    rows={3}
                                    placeholder="Ada pertanyaan atau kebutuhan khusus?"
                                    className="resize-none rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-primary"
                                />
                            </div>

                            <button
                                type="submit"
                                className="mt-2 flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary to-[var(--color-primary-light)] py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:opacity-90"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                Hubungi Admin via WhatsApp
                            </button>

                            <p className="text-center text-[11px] text-gray-500">
                                Dengan mengirim formulir ini, kamu setuju untuk dihubungi oleh tim Nuvelo.
                            </p>
                        </form>
                    )}
                </div>
            </section>
        </GuestLayout>
    );
}





