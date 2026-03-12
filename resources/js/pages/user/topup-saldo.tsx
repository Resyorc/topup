import React, { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import UserLayout from '@/layouts/user-layout';

type PaymentMethod = {
    id: string;
    name: string;
    icon_url?: string | null;
    minimum_amount: number;
};

type ActiveTopup = {
    invoice_id: string;
    amount: number;
    status: string;
    payment_name: string | null;
    payment_url: string | null;
    pay_code: string | null;
    qr_url: string | null;
    pay_url: string | null;
    expired_at: string | null;
    failure_reason: string | null;
};

export default function TopupSaldo() {
    const page = usePage().props as {
        coinsBalance?: number;
        paymentMethods?: Record<string, PaymentMethod[]>;
        activeTopup?: ActiveTopup | null;
        auth?: { user?: { phone?: string | null } };
    };

    const coinsBalance = page.coinsBalance ?? 0;
    const paymentMethods = page.paymentMethods ?? {};
    const activeTopup = page.activeTopup ?? null;

    const paymentMethodEntries = React.useMemo(
        () => Object.entries(paymentMethods),
        [paymentMethods],
    );

    const [openCategories, setOpenCategories] = useState<
        Record<string, boolean>
    >(() =>
        Object.fromEntries(
            Object.keys(paymentMethods).map((key) => [key, true]),
        ),
    );

    const toggleCategory = (category: string) => {
        setOpenCategories((prev) => ({
            ...prev,
            [category]: !prev[category],
        }));
    };

    const { data, setData, post, processing, errors } = useForm({
        amount: 10000,
        payment_method: '',
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

                <div className="grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
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

                            <div className="mt-0 overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29] shadow-lg">
                                <div className="flex h-12 overflow-hidden rounded-t-xl border-b border-[#31334c]">
                                    <div className="flex w-12 shrink-0 items-center justify-center bg-[#c26eff] text-lg font-bold text-white">
                                        1
                                    </div>
                                    <div className="flex flex-1 items-center bg-[#31334c] px-4">
                                        <h4 className="text-sm font-semibold text-white">
                                            Metode Pembayaran
                                        </h4>
                                    </div>
                                </div>

                                <div className="space-y-4 p-4">
                                    {paymentMethodEntries.map(
                                        ([category, methods]) => {
                                            const isOpen =
                                                openCategories[category] !==
                                                false;

                                            return (
                                                <div
                                                    key={category}
                                                    className="overflow-hidden rounded-lg bg-[#3a3545]"
                                                >
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            toggleCategory(
                                                                category,
                                                            )
                                                        }
                                                        className="flex w-full cursor-pointer items-center justify-between px-4 py-3"
                                                    >
                                                        <span className="text-sm font-semibold text-white">
                                                            {category}
                                                        </span>
                                                        <div className="flex items-center gap-3">
                                                            <div className="flex gap-2">
                                                                {methods
                                                                    .slice(0, 4)
                                                                    .map(
                                                                        (
                                                                            pm,
                                                                            i,
                                                                        ) => (
                                                                            <div
                                                                                key={`${pm.id}-${i}`}
                                                                                className="flex h-6 w-10 shrink-0 items-center justify-center overflow-hidden rounded bg-white p-0.5 md:w-12"
                                                                            >
                                                                                {pm.icon_url ? (
                                                                                    <img
                                                                                        src={
                                                                                            pm.icon_url
                                                                                        }
                                                                                        alt={
                                                                                            pm.name
                                                                                        }
                                                                                        className="max-h-full max-w-full object-contain"
                                                                                    />
                                                                                ) : (
                                                                                    <span className="text-[8px] font-bold text-gray-500">
                                                                                        LOGO
                                                                                    </span>
                                                                                )}
                                                                            </div>
                                                                        ),
                                                                    )}
                                                            </div>
                                                            <span
                                                                className={`text-white transition ${isOpen ? 'rotate-180' : ''}`}
                                                            >
                                                                ▼
                                                            </span>
                                                        </div>
                                                    </button>

                                                    {isOpen && (
                                                        <div className="space-y-2 bg-[#2f2a3a] p-3">
                                                            {methods.map(
                                                                (method) => {
                                                                    const isChecked =
                                                                        data.payment_method ===
                                                                        method.id;

                                                                    return (
                                                                        <button
                                                                            key={
                                                                                method.id
                                                                            }
                                                                            type="button"
                                                                            onClick={() =>
                                                                                setData(
                                                                                    'payment_method',
                                                                                    method.id,
                                                                                )
                                                                            }
                                                                            className={`w-full rounded-lg border p-3 text-left transition ${isChecked ? 'border-primary bg-primary/10' : 'border-[#4b4558] bg-[#3a3545] hover:border-primary/60 hover:bg-[#433f4f]'}`}
                                                                        >
                                                                            <div className="flex items-center justify-between gap-3">
                                                                                <div className="flex min-w-0 items-center gap-3">
                                                                                    <div className="flex h-7 w-12 shrink-0 items-center justify-center overflow-hidden rounded bg-white p-0.5">
                                                                                        {method.icon_url ? (
                                                                                            <img
                                                                                                src={
                                                                                                    method.icon_url
                                                                                                }
                                                                                                alt={
                                                                                                    method.name
                                                                                                }
                                                                                                className="max-h-full max-w-full object-contain"
                                                                                            />
                                                                                        ) : (
                                                                                            <span className="text-[9px] font-bold text-gray-500">
                                                                                                LOGO
                                                                                            </span>
                                                                                        )}
                                                                                    </div>
                                                                                    <div className="min-w-0">
                                                                                        <p className="truncate text-sm font-semibold text-white">
                                                                                            {
                                                                                                method.name
                                                                                            }
                                                                                        </p>
                                                                                        <p className="text-[11px] text-gray-400">
                                                                                            Min.
                                                                                            Rp{' '}
                                                                                            {method.minimum_amount.toLocaleString(
                                                                                                'id-ID',
                                                                                            )}
                                                                                        </p>
                                                                                    </div>
                                                                                </div>

                                                                                {isChecked && (
                                                                                    <span className="rounded-full bg-primary px-2 py-0.5 text-[10px] font-semibold text-white">
                                                                                        Dipilih
                                                                                    </span>
                                                                                )}
                                                                            </div>
                                                                        </button>
                                                                    );
                                                                },
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        },
                                    )}
                                </div>

                                {errors.payment_method && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {errors.payment_method}
                                    </p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={processing || !data.payment_method}
                                className="w-full rounded-xl bg-linear-to-r from-primary to-[#9b4dec] px-5 py-3 text-sm font-bold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing ? 'Memproses...' : 'Buat Top Up'}
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
                                    2. Saldo akan masuk otomatis setelah Tripay
                                    memberi status PAID.
                                </li>
                                <li>
                                    3. Top up yang melewati batas waktu akan
                                    otomatis expired.
                                </li>
                            </ul>
                        </div>

                        {activeTopup && (
                            <div className="rounded-2xl border border-[#31334c] bg-[#1e1f29] p-6">
                                <h2 className="text-lg font-semibold text-white">
                                    Top Up Terakhir
                                </h2>
                                <div className="mt-4 space-y-3 text-sm text-gray-300">
                                    <div>
                                        <span className="text-gray-500">
                                            Invoice:
                                        </span>{' '}
                                        {activeTopup.invoice_id}
                                    </div>
                                    <div>
                                        <span className="text-gray-500">
                                            Nominal:
                                        </span>{' '}
                                        Rp{' '}
                                        {activeTopup.amount.toLocaleString(
                                            'id-ID',
                                        )}
                                    </div>
                                    <div>
                                        <span className="text-gray-500">
                                            Status:
                                        </span>{' '}
                                        <span className="font-semibold text-white uppercase">
                                            {activeTopup.status}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-gray-500">
                                            Metode:
                                        </span>{' '}
                                        {activeTopup.payment_name ?? '-'}
                                    </div>
                                    {activeTopup.pay_code && (
                                        <div>
                                            <span className="text-gray-500">
                                                Kode Bayar:
                                            </span>{' '}
                                            <span className="font-semibold text-white">
                                                {activeTopup.pay_code}
                                            </span>
                                        </div>
                                    )}
                                    {activeTopup.expired_at && (
                                        <div>
                                            <span className="text-gray-500">
                                                Expired:
                                            </span>{' '}
                                            {activeTopup.expired_at}
                                        </div>
                                    )}
                                    {activeTopup.failure_reason && (
                                        <div className="rounded-lg border border-red-500/20 bg-red-500/10 px-3 py-2 text-red-300">
                                            {activeTopup.failure_reason}
                                        </div>
                                    )}
                                </div>

                                <div className="mt-5 flex flex-col gap-3">
                                    {activeTopup.payment_url && (
                                        <a
                                            href={activeTopup.payment_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                                        >
                                            Lanjutkan Pembayaran
                                        </a>
                                    )}
                                    {activeTopup.qr_url && (
                                        <a
                                            href={activeTopup.qr_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center justify-center rounded-xl border border-[#31334c] px-4 py-3 text-sm font-semibold text-gray-200 transition hover:bg-white/5"
                                        >
                                            Lihat QR Pembayaran
                                        </a>
                                    )}
                                    {activeTopup.pay_url && (
                                        <a
                                            href={activeTopup.pay_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center justify-center rounded-xl border border-[#31334c] px-4 py-3 text-sm font-semibold text-gray-200 transition hover:bg-white/5"
                                        >
                                            Buka Pembayaran
                                        </a>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </section>
        </UserLayout>
    );
}
