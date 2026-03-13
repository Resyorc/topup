import { Head, useForm, usePage } from '@inertiajs/react';
import UserLayout from '@/layouts/user-layout';

export default function TopupSaldo() {
    const page = usePage().props as {
        coinsBalance?: number;
        auth?: { user?: { phone?: string | null } };
    };

    const coinsBalance = page.coinsBalance ?? 0;

    const { data, setData, post, processing, errors } = useForm({
        amount: 10000,
        customer_whatsapp: page.auth?.user?.phone ?? '',
    });

    const presetAmounts = [10000, 20000, 50000, 100000, 250000];

    return (
        <UserLayout title="Top Up Saldo">
            <Head title="Top Up Saldo" />

            <section className="space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-white">
                            Top Up Saldo
                        </h1>
                        <p className="mt-1 text-sm text-gray-400">
                            Isi saldo Krysta Coin untuk dipakai belanja tanpa
                            biaya admin.
                        </p>
                    </div>
                    <div className="rounded-xl border border-[#31334c] bg-[#1e1f29] px-5 py-3 text-right">
                        <div className="text-xs tracking-wide text-gray-500 uppercase">
                            Saldo Saat Ini
                        </div>
                        <div className="text-2xl font-black text-yellow-400">
                            {coinsBalance.toLocaleString('id-ID')} Coins
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
                    <div className="rounded-2xl border border-[#31334c] bg-[#1e1f29] p-6">
                        <h2 className="text-lg font-semibold text-white">
                            Buat Top Up Baru
                        </h2>

                        <form
                            className="mt-6 space-y-6"
                            onSubmit={(event) => {
                                event.preventDefault();
                                post('/dashboard/topup-saldo');
                            }}
                        >
                            <div>
                                <label className="mb-3 block text-sm font-medium text-gray-200">
                                    Pilih nominal
                                </label>
                                <div className="grid grid-cols-2 gap-3 md:grid-cols-3">
                                    {presetAmounts.map((amount) => (
                                        <button
                                            key={amount}
                                            type="button"
                                            onClick={() =>
                                                setData('amount', amount)
                                            }
                                            className={`rounded-xl border px-4 py-3 text-sm font-semibold transition ${data.amount === amount ? 'border-primary bg-primary/15 text-white' : 'border-[#31334c] bg-[#252834] text-gray-300 hover:border-primary/50 hover:text-white'}`}
                                        >
                                            Rp {amount.toLocaleString('id-ID')}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-200">
                                    Nominal custom
                                </label>
                                <input
                                    type="number"
                                    min={1000}
                                    step={1000}
                                    value={data.amount}
                                    onChange={(event) =>
                                        setData(
                                            'amount',
                                            Number(event.target.value) || 0,
                                        )
                                    }
                                    className="w-full rounded-xl border border-[#31334c] bg-[#252834] px-4 py-3 text-white transition outline-none focus:border-primary"
                                />
                                {errors.amount && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {errors.amount}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-200">
                                    WhatsApp
                                </label>
                                <input
                                    type="text"
                                    value={data.customer_whatsapp}
                                    onChange={(event) =>
                                        setData(
                                            'customer_whatsapp',
                                            event.target.value,
                                        )
                                    }
                                    className="w-full rounded-xl border border-[#31334c] bg-[#252834] px-4 py-3 text-white transition outline-none focus:border-primary"
                                    placeholder="08xxxxxxxxxx"
                                />
                                {errors.customer_whatsapp && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {errors.customer_whatsapp}
                                    </p>
                                )}
                            </div>

                            <div className="rounded-2xl border border-[#31334c] bg-[#252834] p-5">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-white p-2">
                                        <img
                                            src="/qris.svg"
                                            alt="QRIS"
                                            className="h-full w-full object-contain"
                                        />
                                    </div>
                                    <div>
                                        <p className="text-sm font-semibold text-white">
                                            Metode Pembayaran
                                        </p>
                                        <p className="text-sm text-gray-300">
                                            QRIS
                                        </p>
                                    </div>
                                </div>
                                <p className="mt-4 text-sm leading-6 text-gray-400">
                                    Top up Krysta Coins hanya menggunakan QRIS.
                                    Setelah invoice dibuat, pembayaran dan QR
                                    code akan ditampilkan di halaman invoice.
                                </p>
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full rounded-xl bg-linear-to-r from-primary to-[#9b4dec] px-5 py-3 text-sm font-bold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing
                                    ? 'Membuat Invoice...'
                                    : 'Buat Invoice QRIS'}
                            </button>
                        </form>
                    </div>
                    <div className="space-y-6">
                        <div className="rounded-2xl border border-[#31334c] bg-[#1e1f29] p-6">
                            <h2 className="text-lg font-semibold text-white">
                                Informasi
                            </h2>
                            <ul className="mt-4 space-y-3 text-sm text-gray-300">
                                <li>1. 1 Krysta Coin = 1 Rupiah.</li>
                                <li>
                                    2. Pembayaran top up saldo hanya melalui
                                    QRIS.
                                </li>
                                <li>
                                    3. Setelah invoice dibuat, selesaikan
                                    pembayaran di halaman invoice.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
        </UserLayout>
    );
}
