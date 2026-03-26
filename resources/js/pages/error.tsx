import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface ErrorPageProps {
    status: number;
}

const errorMessages: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Akses Ditolak',
        description: 'Kamu tidak memiliki izin untuk mengakses halaman ini.',
    },
    429: {
        title: 'Terlalu Banyak Percobaan',
        description: 'Kamu telah melakukan terlalu banyak percobaan. Silakan tunggu beberapa saat sebelum mencoba lagi.',
    },
    404: {
        title: 'Halaman Tidak Ditemukan',
        description: 'Halaman yang kamu cari tidak ada atau sudah dipindahkan.',
    },
    500: {
        title: 'Kesalahan Server',
        description: 'Terjadi kesalahan pada server kami. Silakan coba lagi nanti.',
    },
    503: {
        title: 'Layanan Tidak Tersedia',
        description: 'Server sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.',
    },
};

export default function ErrorPage({ status }: ErrorPageProps) {
    const [visible, setVisible] = useState(false);
    const [floatY, setFloatY] = useState(0);

    const error = errorMessages[status] ?? {
        title: 'Terjadi Kesalahan',
        description: 'Sesuatu yang tidak terduga telah terjadi.',
    };

    // Fade-in on mount
    useEffect(() => {
        const t = setTimeout(() => setVisible(true), 50);
        return () => clearTimeout(t);
    }, []);

    // Floating animation for the status code
    useEffect(() => {
        let frame: number;
        let start: number | null = null;

        const animate = (ts: number) => {
            if (!start) start = ts;
            const elapsed = ts - start;
            setFloatY(Math.sin(elapsed / 800) * 10);
            frame = requestAnimationFrame(animate);
        };

        frame = requestAnimationFrame(animate);
        return () => cancelAnimationFrame(frame);
    }, []);

    return (
        <>
            <Head title={`${status} – ${error.title}`} />

            <div className="min-h-screen bg-[#0f0f1a] flex items-center justify-center px-4 overflow-hidden relative">

                {/* Background glow blobs */}
                <div className="absolute top-1/4 left-1/4 w-72 h-72 rounded-full bg-purple-700/20 blur-3xl animate-pulse" />
                <div className="absolute bottom-1/4 right-1/4 w-64 h-64 rounded-full bg-violet-500/15 blur-3xl animate-pulse [animation-delay:1s]" />

                {/* Floating grid lines */}
                <div
                    className="absolute inset-0 opacity-5"
                    style={{
                        backgroundImage:
                            'linear-gradient(rgba(176,88,255,0.4) 1px, transparent 1px), linear-gradient(90deg, rgba(176,88,255,0.4) 1px, transparent 1px)',
                        backgroundSize: '60px 60px',
                    }}
                />

                <div
                    className="relative z-10 text-center"
                    style={{
                        opacity: visible ? 1 : 0,
                        transform: visible ? 'translateY(0)' : 'translateY(24px)',
                        transition: 'opacity 0.6s ease, transform 0.6s ease',
                    }}
                >
                    {/* Status code with floating effect */}
                    <div
                        className="font-bold text-transparent bg-clip-text select-none"
                        style={{
                            fontSize: 'clamp(6rem, 20vw, 12rem)',
                            lineHeight: 1,
                            backgroundImage: 'linear-gradient(135deg, #b058ff 0%, #7c3aed 50%, #a855f7 100%)',
                            transform: `translateY(${floatY}px)`,
                            transition: 'transform 0.05s linear',
                            textShadow: '0 0 80px rgba(176,88,255,0.4)',
                            fontFamily: 'Orbitron, monospace',
                        }}
                    >
                        {status}
                    </div>

                    {/* Divider line */}
                    <div className="mx-auto my-4 sm:my-6 h-px w-32 sm:w-48 bg-gradient-to-r from-transparent via-purple-500 to-transparent" />

                    {/* Title */}
                    <h1 className="text-2xl md:text-3xl font-semibold text-white mb-3">
                        {error.title}
                    </h1>

                    {/* Description */}
                    <p className="text-gray-400 text-sm md:text-base max-w-sm mx-auto mb-8 leading-relaxed">
                        {error.description}
                    </p>

                    {/* CTA Button */}
                    <Link
                        href="/"
                        className="inline-flex items-center gap-2 px-6 py-3 rounded-full text-sm font-medium text-white
                                   bg-gradient-to-r from-purple-600 to-violet-600
                                   hover:from-purple-500 hover:to-violet-500
                                   transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-purple-500/30
                                   active:scale-95"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            className="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth={2}
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Kembali ke Beranda
                    </Link>

                    {/* Error code badge */}
                    <p className="mt-8 text-xs text-gray-600 tracking-widest uppercase">
                        Error Code &nbsp;·&nbsp; {status}
                    </p>
                </div>
            </div>
        </>
    );
}
