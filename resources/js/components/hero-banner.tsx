export default function HeroBanner() {
    return (
        <div className="w-full mb-10 overflow-hidden relative group">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
                <div className="relative rounded-3xl overflow-hidden aspect-[21/9] md:aspect-[3/1] bg-gradient-to-r from-indigo-900 via-primary to-purple-800 shadow-2xl flex items-center">
                    
                    {/* Abstract Background Elements */}
                    <div className="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] mix-blend-overlay"></div>
                    <div className="absolute -top-24 -left-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                    <div className="absolute -bottom-24 -right-24 w-96 h-96 bg-fuchsia-500/20 rounded-full blur-3xl"></div>

                    {/* Banner Content */}
                    <div className="relative z-10 p-8 md:p-12 w-full md:w-2/3 h-full flex flex-col justify-center">
                        <div className="hidden md:flex items-center gap-1 mb-4 opacity-80">
                            <span className="text-white font-black text-2xl tracking-tighter">N</span>
                            <span className="text-white font-bold text-2xl tracking-tighter -ml-1">ebu.</span>
                        </div>
                        <h1 className="text-3xl md:text-5xl font-black text-white italic tracking-tight uppercase leading-tight drop-shadow-lg">
                            Top Up Game Favoritmu <br className="hidden md:block"/> Lebih Cepat & Murah!
                        </h1>
                        <p className="mt-4 text-sm md:text-base text-gray-200 bg-black/20 backdrop-blur-md rounded-xl p-3 inline-block border border-white/10 shadow-lg max-w-lg">
                            Dapatkan voucher Mobile Legends, Free Fire, PUBG M, dan lainnya dengan harga terbaik hanya di <strong className="text-yellow-400">NEBUSTORE</strong>.
                        </p>
                    </div>

                    {/* Mock Character Image space */}
                    <div className="absolute bottom-0 right-0 w-1/3 h-full hidden md:block">
                        <div className="w-full h-full bg-gradient-to-l from-black/40 to-transparent"></div>
                    </div>
                </div>

                {/* Indicators */}
                <div className="flex justify-center mt-4 gap-2">
                    <div className="w-8 h-2 rounded-full bg-primary shadow-sm hover:cursor-pointer"></div>
                    <div className="w-2 h-2 rounded-full bg-gray-600 hover:bg-gray-400 cursor-pointer transition"></div>
                    <div className="w-2 h-2 rounded-full bg-gray-600 hover:bg-gray-400 cursor-pointer transition"></div>
                </div>
            </div>
        </div>
    );
}
