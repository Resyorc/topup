import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import UserLayout from '@/layouts/user-layout';

interface ApiCredentialsProps {
    apiKey: string | null;
    apiAccessEnabled: boolean;
    emailVerified: boolean;
}

function CodeBlock({ children }: { children: string }) {
    const [copied, setCopied] = useState(false);
    const copy = async () => {
        await navigator.clipboard.writeText(children);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };
    return (
        <div className="group relative">
            <pre className="overflow-x-auto rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-main)] px-4 py-3 text-xs text-green-400">
                <code>{children}</code>
            </pre>
            <button
                onClick={copy}
                className="absolute right-2 top-2 rounded-lg border border-[var(--color-border-light)] bg-[var(--color-bg-card)] px-2 py-1 text-[10px] font-semibold text-gray-400 opacity-0 transition group-hover:opacity-100 hover:text-white"
            >
                {copied ? 'Tersalin!' : 'Salin'}
            </button>
        </div>
    );
}

function LockedState({ emailVerified }: { emailVerified: boolean }) {
    return (
        <div className="overflow-hidden rounded-2xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)]">
            <div className="border-b border-[var(--color-border-light)] bg-white/5 px-6 py-4">
                <h2 className="text-sm font-bold text-white">API Access Belum Diaktifkan</h2>
            </div>
            <div className="flex flex-col items-center gap-5 p-8 text-center">
                <div className="flex h-16 w-16 items-center justify-center rounded-full border border-yellow-500/30 bg-yellow-500/10">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-yellow-400">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                </div>
                <div className="max-w-sm">
                    <h3 className="text-base font-bold text-white">Akses API Terbatas</h3>
                    <p className="mt-2 text-sm text-gray-400">
                        Untuk menggunakan Reseller API, akun kamu perlu diverifikasi dan disetujui oleh admin terlebih dahulu.
                    </p>
                </div>
                <div className="w-full max-w-sm space-y-3 rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-main)] p-4 text-left">
                    <p className="text-xs font-semibold uppercase tracking-wider text-gray-500">Syarat Aktivasi</p>
                    <div className="flex items-center gap-3">
                        {emailVerified ? (
                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-500/20">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" className="text-green-400"><path d="M20 6 9 17l-5-5" /></svg>
                            </span>
                        ) : (
                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-500/20">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" className="text-red-400"><path d="M18 6 6 18M6 6l12 12" /></svg>
                            </span>
                        )}
                        <span className={`text-sm ${emailVerified ? 'text-gray-300' : 'text-gray-500'}`}>
                            Email sudah diverifikasi
                        </span>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-500/20">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" className="text-red-400"><path d="M18 6 6 18M6 6l12 12" /></svg>
                        </span>
                        <span className="text-sm text-gray-500">Disetujui oleh admin</span>
                    </div>
                </div>
                <p className="text-xs text-gray-500">
                    Hubungi admin melalui WhatsApp atau email untuk meminta aktivasi akses API.
                </p>
            </div>
        </div>
    );
}

export default function ApiCredentials({ apiKey, apiAccessEnabled, emailVerified }: ApiCredentialsProps) {
    const { flash } = usePage().props as any;
    const [loading, setLoading] = useState(false);
    const [visible, setVisible] = useState(false);

    const handleRegenerate = async () => {
        if (!confirm('API Key lama akan tidak bisa digunakan lagi. Lanjutkan?')) return;
        setLoading(true);
        router.post('/dashboard/api-credentials/regenerate', {}, {
            onFinish: () => setLoading(false),
        });
    };

    return (
        <UserLayout title="API Credentials">
            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-xl font-black text-white md:text-2xl">API Credentials</h1>
                    <p className="mt-1 text-sm text-gray-400">
                        Gunakan API Key di bawah untuk mengakses Reseller API kami secara programatis.
                    </p>
                </div>

                {flash?.success && (
                    <div className="rounded-xl border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-400">
                        {flash.success}
                    </div>
                )}

                {!apiAccessEnabled ? (
                    <LockedState emailVerified={emailVerified} />
                ) : (
                    <>
                        {/* API Key Card */}
                        <div className="overflow-hidden rounded-2xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)]">
                            <div className="border-b border-[var(--color-border-light)] bg-white/5 px-6 py-4">
                                <h2 className="text-sm font-bold text-white">API Key Kamu</h2>
                            </div>
                            <div className="p-6">
                                {apiKey ? (
                                    <div className="flex flex-col gap-4">
                                        <div className="flex items-center gap-3 rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-main)] px-4 py-3">
                                            <code className="flex-1 overflow-x-auto text-xs font-mono text-green-400 select-all">
                                                {visible ? apiKey : '•'.repeat(apiKey.length)}
                                            </code>
                                            <button
                                                onClick={() => setVisible(!visible)}
                                                className="shrink-0 text-xs text-gray-400 transition hover:text-white"
                                            >
                                                {visible ? 'Sembunyikan' : 'Tampilkan'}
                                            </button>
                                            <button
                                                onClick={() => navigator.clipboard.writeText(apiKey)}
                                                className="shrink-0 rounded-lg border border-[var(--color-border-light)] px-3 py-1.5 text-xs font-semibold text-gray-300 transition hover:border-primary hover:text-white"
                                            >
                                                Salin
                                            </button>
                                        </div>
                                        <p className="text-xs text-gray-500">
                                            Jangan bagikan API Key ini kepada siapapun. Jika bocor, segera regenerate.
                                        </p>
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center gap-4 py-6 text-center">
                                        <div className="flex h-14 w-14 items-center justify-center rounded-full bg-white/5">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-gray-500">
                                                <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4" />
                                            </svg>
                                        </div>
                                        <p className="text-sm text-gray-400">Kamu belum memiliki API Key. Generate sekarang untuk mulai menggunakan Reseller API.</p>
                                    </div>
                                )}

                                <button
                                    onClick={handleRegenerate}
                                    disabled={loading}
                                    className="mt-4 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-primary/90 disabled:opacity-50"
                                >
                                    {loading ? 'Memproses...' : apiKey ? 'Regenerate API Key' : 'Generate API Key'}
                                </button>
                            </div>
                        </div>

                        {/* Usage Examples */}
                        <div className="overflow-hidden rounded-2xl border border-[var(--color-border-light)] bg-[var(--color-bg-card)]">
                            <div className="border-b border-[var(--color-border-light)] bg-white/5 px-6 py-4">
                                <h2 className="text-sm font-bold text-white">Cara Penggunaan</h2>
                            </div>
                            <div className="flex flex-col gap-5 p-6">
                                <div>
                                    <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Autentikasi (Header)</p>
                                    <CodeBlock>{`X-API-Key: ${apiKey ?? 'YOUR_API_KEY_HERE'}`}</CodeBlock>
                                </div>

                                <div>
                                    <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Daftar Semua Game</p>
                                    <CodeBlock>{`GET ${window.location.origin}/api/v1/games\nHeaders: X-API-Key: ${apiKey ?? 'YOUR_API_KEY'}`}</CodeBlock>
                                </div>

                                <div>
                                    <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Produk per Game</p>
                                    <CodeBlock>{`GET ${window.location.origin}/api/v1/products?game_slug=mobile-legends\nHeaders: X-API-Key: ${apiKey ?? 'YOUR_API_KEY'}`}</CodeBlock>
                                </div>

                                <p className="text-xs text-gray-500">
                                    Lihat dokumentasi lengkap di halaman{' '}
                                    <a href="/api-docs" className="text-primary underline">API Documentation</a>.
                                </p>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </UserLayout>
    );
}




