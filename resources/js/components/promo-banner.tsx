import { Link } from '@inertiajs/react';

export default function PromoBanner() {
    return (
        <section className="max-w-7xl mx-auto px-4 py-8 mb-16">
            <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#1E1F2E] via-[#2F1F45] to-[#1E1F2E] p-1 shadow-xl border border-border">
                {/* Dots Pattern */}
                <div className="absolute inset-0 opacity-10" style={{ backgroundImage: 'radial-gradient(circle, #fff 1px, transparent 1px)', backgroundSize: '16px 16px' }}></div>
                
                <div className="relative z-10 py-12 px-6 flex flex-col items-center text-center">
                    <h2 className="text-2xl md:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-yellow-400 to-amber-500 italic uppercase drop-shadow-[0_2px_2px_rgba(0,0,0,0.8)] border-b-2 border-transparent" style={{ WebkitTextStroke: '1px rgba(0,0,0,0.3)' }}>
                        Gabung & Nikmati Promo Eksklusif!
                    </h2>
                    <p className="mt-4 text-sm md:text-base text-gray-300 max-w-xl mx-auto">
                        Daftar akun di <strong className="text-yellow-400">NEBUSTORE</strong> dan dapatkan akses ke diskon khusus, bonus voucher, dan promo mingguan yang hanya tersedia untuk member.
                    </p>
                    <Link 
                        href="/register" 
                        className="mt-8 bg-primary hover:bg-primary-hover text-white font-bold py-3 px-8 rounded-full shadow-lg hover:shadow-primary/50 transition transform hover:-translate-y-1"
                    >
                        Daftar Sekarang
                    </Link>
                </div>
            </div>
        </section>
    );
}
