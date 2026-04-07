import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import GuestLayout from '@/layouts/guest-layout';

function Badge({ color, children }: { color: 'green' | 'blue' | 'yellow' | 'red'; children: string }) {
    const cls = {
        green:  'bg-green-500/20 text-green-400 border-green-500/30',
        blue:   'bg-blue-500/20 text-blue-400 border-blue-500/30',
        yellow: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        red:    'bg-red-500/20 text-red-400 border-red-500/30',
    }[color];
    return (
        <span className={`inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] font-bold uppercase ${cls}`}>
            {children}
        </span>
    );
}

function CodeBlock({ children }: { children: string }) {
    const [copied, setCopied] = useState(false);
    const copy = async () => {
        await navigator.clipboard.writeText(children);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };
    return (
        <div className="group relative mt-3">
            <pre className="overflow-x-auto rounded-xl border border-[#31334c] bg-[#0d0d14] px-4 py-3 text-xs text-green-400">
                <code>{children}</code>
            </pre>
            <button
                onClick={copy}
                className="absolute right-2 top-2 rounded-lg border border-[#31334c] bg-[#1e1f29] px-2 py-1 text-[10px] font-semibold text-gray-400 opacity-0 transition group-hover:opacity-100 hover:text-white"
            >
                {copied ? 'Tersalin!' : 'Salin'}
            </button>
        </div>
    );
}

interface EndpointProps {
    method: 'GET' | 'POST';
    path: string;
    description: string;
    auth?: boolean;
    params?: { name: string; type: string; required: boolean; description: string }[];
    example?: string;
    response?: string;
    badge?: string;
}

function Endpoint({ method, path, description, auth, params, example, response, badge }: EndpointProps) {
    const [open, setOpen] = useState(false);
    const methodColor = method === 'GET' ? 'text-green-400 bg-green-500/10 border-green-500/20' : 'text-blue-400 bg-blue-500/10 border-blue-500/20';

    return (
        <div className="overflow-hidden rounded-2xl border border-[#31334c] bg-[#1e1f29]">
            <button
                className="flex w-full items-center gap-3 px-5 py-4 text-left transition hover:bg-white/5"
                onClick={() => setOpen(!open)}
            >
                <span className={`shrink-0 rounded-md border px-2 py-0.5 text-[11px] font-black uppercase ${methodColor}`}>{method}</span>
                <code className="flex-1 truncate text-sm font-mono text-white">{path}</code>
                {badge && <span className="shrink-0 rounded-full bg-orange-500/20 px-2 py-0.5 text-[10px] font-bold text-orange-400">{badge}</span>}
                {auth && <span className="shrink-0 rounded-full border border-yellow-500/30 bg-yellow-500/10 px-2 py-0.5 text-[10px] font-bold text-yellow-400">Auth</span>}
                <svg className={`h-4 w-4 shrink-0 text-gray-500 transition-transform ${open ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {open && (
                <div className="border-t border-[#31334c] px-5 pb-5 pt-4">
                    <p className="text-sm text-gray-300">{description}</p>

                    {params && params.length > 0 && (
                        <div className="mt-4">
                            <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Parameter</p>
                            <div className="overflow-hidden rounded-xl border border-[#31334c]">
                                <table className="w-full text-xs">
                                    <thead className="bg-white/5">
                                        <tr>
                                            <th className="px-4 py-2 text-left font-semibold text-gray-400">Nama</th>
                                            <th className="px-4 py-2 text-left font-semibold text-gray-400">Tipe</th>
                                            <th className="px-4 py-2 text-left font-semibold text-gray-400">Wajib</th>
                                            <th className="px-4 py-2 text-left font-semibold text-gray-400">Deskripsi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[#31334c]">
                                        {params.map((p) => (
                                            <tr key={p.name}>
                                                <td className="px-4 py-2 font-mono text-yellow-400">{p.name}</td>
                                                <td className="px-4 py-2 text-gray-400">{p.type}</td>
                                                <td className="px-4 py-2">{p.required ? <span className="text-red-400">Ya</span> : <span className="text-gray-500">Tidak</span>}</td>
                                                <td className="px-4 py-2 text-gray-300">{p.description}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {example && (
                        <div className="mt-4">
                            <p className="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-500">Contoh Request</p>
                            <CodeBlock>{example}</CodeBlock>
                        </div>
                    )}

                    {response && (
                        <div className="mt-4">
                            <p className="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-500">Contoh Response</p>
                            <CodeBlock>{response}</CodeBlock>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

export default function ApiDocs() {
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : '';

    return (
        <GuestLayout>
            <Head title="Dokumentasi API" />

            <div className="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-10">
                    <div className="mb-3 inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                        API v1
                    </div>
                    <h1 className="text-2xl font-black text-white md:text-3xl">Dokumentasi API</h1>
                    <p className="mt-2 text-sm text-gray-400">
                        Integrasikan layanan top up kami ke platform kamu menggunakan Reseller API.
                        Dapatkan API Key di halaman{' '}
                        <Link href="/dashboard/api-credentials" className="text-primary underline">API Credentials</Link>.
                    </p>
                </div>

                {/* Auth Section */}
                <div className="mb-8 rounded-2xl border border-yellow-500/20 bg-yellow-500/5 p-5">
                    <h2 className="mb-2 text-sm font-bold text-yellow-400">Autentikasi</h2>
                    <p className="mb-3 text-sm text-gray-300">
                        Semua endpoint Reseller API membutuhkan autentikasi via header <code className="rounded bg-white/10 px-1 text-yellow-300">X-API-Key</code>.
                    </p>
                    <CodeBlock>{`// Header yang harus disertakan di setiap request:\nX-API-Key: YOUR_API_KEY_HERE`}</CodeBlock>
                    <p className="mt-3 text-xs text-gray-500">Rate limit: 120 request per menit per API Key.</p>
                </div>

                {/* Base URL */}
                <div className="mb-8 rounded-2xl border border-[#31334c] bg-[#1e1f29] p-5">
                    <h2 className="mb-2 text-sm font-bold text-white">Base URL</h2>
                    <CodeBlock>{`${baseUrl}/api/v1`}</CodeBlock>
                </div>

                {/* Endpoints */}
                <div className="mb-4">
                    <h2 className="text-lg font-bold text-white">Endpoint Tersedia</h2>
                </div>

                <div className="flex flex-col gap-3">
                    <Endpoint
                        method="GET"
                        path="/api/v1/games"
                        description="Mendapatkan daftar semua game yang aktif beserta informasi dasar seperti nama, slug, dan publisher."
                        auth
                        example={`curl -X GET ${baseUrl}/api/v1/games \\\n  -H "X-API-Key: YOUR_API_KEY"`}
                        response={`{\n  "success": true,\n  "data": [\n    {\n      "id": 1,\n      "name": "Mobile Legends",\n      "slug": "mobile-legends",\n      "publisher": "Moonton"\n    }\n  ]\n}`}
                    />

                    <Endpoint
                        method="GET"
                        path="/api/v1/products?game_slug={slug}"
                        description="Mendapatkan daftar produk/paket untuk game tertentu, dikelompokkan berdasarkan kategori. Harga sudah dalam Rupiah."
                        auth
                        params={[
                            { name: 'game_slug', type: 'string', required: true, description: 'Slug game, contoh: mobile-legends, free-fire' },
                        ]}
                        example={`curl -X GET "${baseUrl}/api/v1/products?game_slug=mobile-legends" \\\n  -H "X-API-Key: YOUR_API_KEY"`}
                        response={`{\n  "success": true,\n  "data": {\n    "game": { "id": 1, "name": "Mobile Legends", "slug": "mobile-legends" },\n    "products": {\n      "Diamond": [\n        { "id": "...", "name": "86 Diamond", "price": 19000, "discount_percent": 0 }\n      ]\n    }\n  }\n}`}
                    />

                    <Endpoint
                        method="POST"
                        path="/api/v1/order"
                        description="Membuat pesanan top up baru. Endpoint ini akan segera tersedia — saat ini dalam pengembangan."
                        auth
                        badge="Coming Soon"
                        params={[
                            { name: 'product_id', type: 'string', required: true, description: 'ID produk dari endpoint /products' },
                            { name: 'customer_game_id', type: 'string', required: true, description: 'ID akun game customer' },
                            { name: 'customer_zone_id', type: 'string', required: false, description: 'Zone/Server ID (jika dibutuhkan game)' },
                            { name: 'customer_whatsapp', type: 'string', required: true, description: 'Nomor WhatsApp customer (format: +62xxx)' },
                        ]}
                        example={`// Akan tersedia setelah verifikasi reseller aktif`}
                        response={`// Akan tersedia setelah verifikasi reseller aktif`}
                    />

                    <Endpoint
                        method="GET"
                        path="/api/v1/order/{invoice_id}"
                        description="Mengecek status pesanan berdasarkan invoice ID. Endpoint ini akan segera tersedia."
                        auth
                        badge="Coming Soon"
                        params={[
                            { name: 'invoice_id', type: 'string', required: true, description: 'Nomor invoice, contoh: INV-01JRXXX' },
                        ]}
                        example={`// Akan tersedia setelah verifikasi reseller aktif`}
                    />
                </div>

                {/* Response Format */}
                <div className="mt-10 rounded-2xl border border-[#31334c] bg-[#1e1f29] p-5">
                    <h2 className="mb-3 text-sm font-bold text-white">Format Response</h2>
                    <p className="mb-3 text-sm text-gray-400">Semua response menggunakan format JSON dengan struktur berikut:</p>
                    <CodeBlock>{`// Sukses\n{\n  "success": true,\n  "data": { ... }\n}\n\n// Error\n{\n  "success": false,\n  "message": "Pesan error di sini"\n}`}</CodeBlock>
                </div>

                {/* HTTP Status Codes */}
                <div className="mt-6 rounded-2xl border border-[#31334c] bg-[#1e1f29] p-5">
                    <h2 className="mb-4 text-sm font-bold text-white">HTTP Status Codes</h2>
                    <div className="flex flex-col gap-2 text-sm">
                        {[
                            { code: '200', color: 'green' as const, desc: 'Request berhasil' },
                            { code: '401', color: 'yellow' as const, desc: 'API Key tidak ada atau tidak valid' },
                            { code: '422', color: 'yellow' as const, desc: 'Validasi gagal — cek parameter yang dikirim' },
                            { code: '429', color: 'red' as const, desc: 'Rate limit terlampaui — kurangi frekuensi request' },
                            { code: '500', color: 'red' as const, desc: 'Server error — hubungi support' },
                        ].map(({ code, color, desc }) => (
                            <div key={code} className="flex items-center gap-3">
                                <Badge color={color}>{code}</Badge>
                                <span className="text-gray-300">{desc}</span>
                            </div>
                        ))}
                    </div>
                </div>

                <p className="mt-8 text-center text-xs text-gray-600">
                    Ada pertanyaan? Hubungi kami via WhatsApp atau buka tiket support.
                </p>
            </div>
        </GuestLayout>
    );
}
