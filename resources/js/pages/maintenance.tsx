import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface Props {
    estimatedEnd?: string | null;
    message?: string | null;
}

export default function Maintenance({ estimatedEnd, message }: Props) {
    const [visible, setVisible] = useState(false);
    const [rotation1, setRotation1] = useState(0);
    const [rotation2, setRotation2] = useState(0);

    useEffect(() => {
        const t = setTimeout(() => setVisible(true), 50);
        return () => clearTimeout(t);
    }, []);

    useEffect(() => {
        let frame: number;
        let start: number | null = null;

        const animate = (ts: number) => {
            if (!start) start = ts;
            const elapsed = ts - start;
            setRotation1((elapsed / 12) % 360);
            setRotation2(-((elapsed / 18) % 360));
            frame = requestAnimationFrame(animate);
        };

        frame = requestAnimationFrame(animate);
        return () => cancelAnimationFrame(frame);
    }, []);

    return (
        <>
            <Head title="Maintenance – Nuvelo" />

            <div className="min-h-screen bg-[#0f0f1a] flex items-center justify-center px-4 overflow-hidden relative">

                {/* Background glow blobs */}
                <div className="absolute top-1/4 left-1/4 w-72 h-72 rounded-full bg-purple-700/20 blur-3xl animate-pulse" />
                <div className="absolute bottom-1/4 right-1/4 w-64 h-64 rounded-full bg-violet-500/15 blur-3xl animate-pulse [animation-delay:1s]" />
                <div className="absolute top-1/2 left-1/2 w-48 h-48 rounded-full bg-indigo-600/10 blur-3xl animate-pulse [animation-delay:2s]" />

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
                    className="relative z-10 text-center max-w-md w-full"
                    style={{
                        opacity: visible ? 1 : 0,
                        transform: visible ? 'translateY(0)' : 'translateY(24px)',
                        transition: 'opacity 0.6s ease, transform 0.6s ease',
                    }}
                >
                    {/* Gear animation */}
                    <div className="flex items-center justify-center gap-2 mb-6 select-none">
                        <svg
                            width="64" height="64" viewBox="0 0 24 24" fill="none"
                            className="drop-shadow-[0_0_16px_rgba(176,88,255,0.6)]"
                            style={{ transform: `rotate(${rotation1}deg)`, transition: 'transform 0.016s linear' }}
                        >
                            <path
                                d="M12 15a3 3 0 100-6 3 3 0 000 6z"
                                fill="rgba(176,88,255,0.9)"
                            />
                            <path
                                d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"
                                stroke="rgba(176,88,255,0.9)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
                            />
                        </svg>

                        <svg
                            width="40" height="40" viewBox="0 0 24 24" fill="none"
                            className="drop-shadow-[0_0_12px_rgba(124,58,237,0.6)]"
                            style={{ transform: `rotate(${rotation2}deg)`, transition: 'transform 0.016s linear' }}
                        >
                            <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" fill="rgba(124,58,237,0.9)" />
                            <path
                                d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"
                                stroke="rgba(124,58,237,0.9)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
                            />
                        </svg>
                    </div>

                    {/* Brand */}
                    <div
                        className="text-4xl font-bold text-transparent bg-clip-text mb-2 select-none"
                        style={{
                            backgroundImage: 'linear-gradient(135deg, #b058ff 0%, #7c3aed 50%, #a855f7 100%)',
                            fontFamily: 'Orbitron, monospace',
                        }}
                    >
                        NUVELO
                    </div>

                    {/* Divider line */}
                    <div className="mx-auto my-4 h-px w-32 bg-gradient-to-r from-transparent via-purple-500 to-transparent" />

                    {/* Title */}
                    <h1 className="text-2xl md:text-3xl font-semibold text-white mb-3">
                        Sedang Maintenance
                    </h1>

                    {/* Description */}
                    <p className="text-gray-400 text-sm md:text-base max-w-sm mx-auto mb-6 leading-relaxed">
                        {message ?? 'Kami sedang melakukan peningkatan sistem untuk memberikan pengalaman terbaik. Mohon bersabar sebentar.'}
                    </p>

                    {/* ETA badge */}
                    <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm border border-purple-500/30 bg-purple-500/10 text-purple-300 mb-8">
                        <span className="relative flex h-2 w-2">
                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75" />
                            <span className="relative inline-flex rounded-full h-2 w-2 bg-purple-500" />
                        </span>
                        {estimatedEnd
                            ? <>Estimasi selesai: <strong className="text-white">{estimatedEnd}</strong></>
                            : 'Sebentar lagi kembali online'
                        }
                    </div>

                    {/* Animated dots */}
                    <div className="flex justify-center gap-2 mb-8">
                        {[0, 1, 2].map(i => (
                            <div
                                key={i}
                                className="w-2 h-2 rounded-full bg-purple-500"
                                style={{
                                    animation: 'bounce 0.9s ease-in-out infinite',
                                    animationDelay: `${i * 0.15}s`,
                                }}
                            />
                        ))}
                    </div>

                    {/* Divider */}
                    <div className="mx-auto mb-6 h-px w-full bg-gradient-to-r from-transparent via-purple-500/30 to-transparent" />

                    {/* Contact */}
                    <div className="flex flex-wrap items-center justify-center gap-4 mb-6">
                        <a
                            href="https://wa.me/6281234567890"
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium text-white
                                       bg-gradient-to-r from-green-600 to-emerald-600
                                       hover:from-green-500 hover:to-emerald-500
                                       transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-green-500/30
                                       active:scale-95"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                            Hubungi CS
                        </a>
                    </div>

                    {/* Safety note */}
                    <p className="text-xs text-gray-600 leading-relaxed">
                        Data & transaksi kamu tetap aman selama maintenance berlangsung.
                    </p>
                </div>
            </div>

            <style>{`
                @keyframes bounce {
                    0%, 100% { transform: translateY(0); opacity: 0.4; }
                    50%       { transform: translateY(-6px); opacity: 1; }
                }
            `}</style>
        </>
    );
}
