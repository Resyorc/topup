export default function HeroBanner() {
    return (
        <div className="group relative mb-10 w-full overflow-hidden">
            <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                <div className="relative flex aspect-[21/9] items-center overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-900 via-primary to-purple-800 shadow-2xl md:aspect-[3/1]">
                    {/* Abstract Background Elements */}
                    <div className="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-20 mix-blend-overlay"></div>
                    <div className="absolute -top-24 -left-24 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
                    <div className="absolute -right-24 -bottom-24 h-96 w-96 rounded-full bg-fuchsia-500/20 blur-3xl"></div>

                    {/* Banner Content */}
                    <div className="relative z-10 flex h-full w-full flex-col justify-center p-8 md:w-2/3 md:p-12">
                        <div className="mb-4 hidden items-center gap-1 opacity-80 md:flex">
                            <span className="text-2xl font-black tracking-tighter text-white">
                                N
                            </span>
                            <span className="-ml-1 text-2xl font-bold tracking-tighter text-white">
                                ebu.
                            </span>
                        </div>
                        <h1 className="text-3xl leading-tight font-black tracking-tight text-white uppercase italic drop-shadow-lg md:text-5xl">
                            Top Up Game Favoritmu{' '}
                            <br className="hidden md:block" /> Lebih Cepat &
                            Murah!
                        </h1>
                        <p className="mt-4 inline-block max-w-lg rounded-xl border border-white/10 bg-black/20 p-3 text-sm text-gray-200 shadow-lg backdrop-blur-md md:text-base">
                            Dapatkan voucher Mobile Legends, Free Fire, PUBG M,
                            dan lainnya dengan harga terbaik hanya di{' '}
                            <strong className="text-yellow-400">Nuvelo</strong>.
                        </p>
                    </div>

                    {/* Mock Character Image space */}
                    <div className="absolute right-0 bottom-0 hidden h-full w-1/3 md:block">
                        <div className="h-full w-full bg-gradient-to-l from-black/40 to-transparent"></div>
                    </div>
                </div>

                {/* Indicators */}
                <div className="mt-4 flex justify-center gap-2">
                    <div className="h-2 w-8 rounded-full bg-primary shadow-sm hover:cursor-pointer"></div>
                    <div className="h-2 w-2 cursor-pointer rounded-full bg-gray-600 transition hover:bg-gray-400"></div>
                    <div className="h-2 w-2 cursor-pointer rounded-full bg-gray-600 transition hover:bg-gray-400"></div>
                </div>
            </div>
        </div>
    );
}
