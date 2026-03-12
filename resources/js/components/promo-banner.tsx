import { Link } from '@inertiajs/react';

export default function PromoBanner() {
    return (
        <section className="mx-auto mb-16 max-w-7xl px-4 py-8">
            <div className="relative overflow-hidden rounded-3xl border border-border bg-gradient-to-r from-[#1E1F2E] via-[#2F1F45] to-[#1E1F2E] p-1 shadow-xl">
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
                        className="border-b-2 border-transparent bg-gradient-to-r from-yellow-300 via-yellow-400 to-amber-500 bg-clip-text text-2xl font-black text-transparent uppercase italic drop-shadow-[0_2px_2px_rgba(0,0,0,0.8)] md:text-4xl"
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
