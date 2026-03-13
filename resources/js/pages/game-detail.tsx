import React, { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';
import GameCard from '@/components/game-card';
import axios from 'axios';

interface Product {
    id: string;
    name: string;
    clean_name: string;
    price: number;
    extra: string | null;
}

interface PaymentMethod {
    id: string;
    name: string;
    icon_url: string | null;
    fee_flat: number;
    fee_percent: number;
    minimum_amount: number;
    is_coin?: boolean;
    disabled?: boolean;
    coin_balance?: number;
}

interface GameDetailProps {
    game: {
        id: string;
        name: string;
        slug: string;
        publisher: string;
        thumbnail: string | null;
        image: string | null;
        rating: number;
        reviews_count: string;
    };
    productGroups: {
        [category: string]: Product[];
    };
    paymentMethods: {
        [category: string]: PaymentMethod[];
    };
}

export default function GameDetail({
    game,
    productGroups,
    paymentMethods,
}: GameDetailProps) {
    // Form State
    const { data, setData, post, processing, errors } = useForm({
        user_id: '',
        server_id: '',
        whatsapp: '',
        product_id: '',
        payment_method: '',
        qty: 1,
        promo_code: '',
    });

    const [calculatedFees, setCalculatedFees] = useState<Record<
        string,
        number
    > | null>(null);
    const [isCalculatingFee, setIsCalculatingFee] = useState(false);

    const getImageUrl = (image: string | null) => {
        if (image && image.length > 0) {
            return image; // Base path is already appended by the Controller if it exists
        }
        return `https://ui-avatars.com/api/?name=${encodeURIComponent(game?.name || 'Topup')}&color=ffffff&background=8327d8&size=512&rounded=true&font-size=0.33`;
    };

    const [activeTab, setActiveTab] = useState<string>(
        Object.keys(productGroups)[0] || '',
    );
    const [showModal, setShowModal] = useState(false);

    const paymentMethodEntries = React.useMemo(
        () =>
            Object.entries(paymentMethods).sort(([a], [b]) => {
                const aIsCoin = a === 'Krysta Coin';
                const bIsCoin = b === 'Krysta Coin';

                if (aIsCoin === bIsCoin) return 0;
                return aIsCoin ? -1 : 1;
            }),
        [paymentMethods],
    );

    // Collapse State for Payment Methods — all open by default
    const [openCategories, setOpenCategories] = useState<
        Record<string, boolean>
    >(() =>
        Object.fromEntries(Object.keys(paymentMethods).map((k) => [k, true])),
    );

    const toggleCategory = (category: string) => {
        setOpenCategories((prev) => ({
            ...prev,
            [category]: !prev[category],
        }));
    };

    // Derived States
    const selectedProduct = productGroups[activeTab]?.find(
        (p) => p.id === data.product_id,
    );
    const selectedPayment = Object.values(paymentMethods)
        .flat()
        .find((pm) => pm.id === data.payment_method); // Assuming paymentMethods might be grouped

    // Calculate total including Tripay fees
    const calculateTotal = (pm: PaymentMethod) => {
        if (!selectedProduct) return 0;
        const subtotal = selectedProduct.price * data.qty;

        let totalFee = pm.fee_flat;
        if (pm.fee_percent > 0) {
            totalFee += Math.ceil((subtotal * pm.fee_percent) / 100);
        }

        return subtotal + totalFee;
    };

    // State for username validation
    const [isValidating, setIsValidating] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [validatedUsername, setValidatedUsername] = useState<string | null>(
        null,
    );

    const MIHOYO_GAMES = ['hsr', 'genshin', 'zzz'];
    const isMihoyoGame = MIHOYO_GAMES.includes(game.slug);

    const detectMihoyoServer = (uid: string) => {
        if (!uid) return null;
        switch (uid[0]) {
            case '6':
                return { name: 'America', zone: 'prod_official_usa' };
            case '7':
                return { name: 'Europe', zone: 'prod_official_eur' };
            case '8':
                return { name: 'Asia', zone: 'prod_official_asia' };
            case '9':
                return { name: 'SAR (TW/HK/MO)', zone: 'prod_official_cht' };
            default:
                return null;
        }
    };

    // Auto-detect Mihoyo Server from UID
    useEffect(() => {
        if (!isMihoyoGame || !data.user_id) return;
        const srv = detectMihoyoServer(data.user_id);
        if (srv && srv.zone !== data.server_id) {
            setData('server_id', srv.zone);
        }
    }, [data.user_id, isMihoyoGame]);

    // Debounced Username Check API
    useEffect(() => {
        const ready =
            data.user_id.length > 0 &&
            (isMihoyoGame || data.server_id.length > 0);

        if (!ready) {
            setValidatedUsername(null);
            return;
        }

        const t = setTimeout(async () => {
            setIsValidating(true);
            try {
                const response = await axios.post('/api/check-username', {
                    game: game.slug,
                    user_id: data.user_id,
                    zone_id: isMihoyoGame ? null : data.server_id,
                });

                if (response.data.success) {
                    setValidatedUsername(response.data.nickname);
                } else {
                    setValidatedUsername(
                        '❌ ' + (response.data.message || 'ID Tidak Valid'),
                    );
                }
            } catch (error: any) {
                setValidatedUsername(
                    '❌ ' +
                        (error.response?.data?.message ||
                            'Gagal memeriksa ID.'),
                );
            } finally {
                setIsValidating(false);
            }
        }, 600);

        return () => clearTimeout(t);
    }, [data.user_id, data.server_id, game.slug]);

    // Handle dynamic fee calculation
    useEffect(() => {
        const fetchFee = async () => {
            if (!data.product_id || !data.qty) {
                setCalculatedFees(null);
                return;
            }

            const product = selectedProduct;

            if (product) {
                try {
                    setIsCalculatingFee(true);
                    const subtotalAmount = product.price * data.qty;
                    const response = await axios.post('/api/calculate-fee', {
                        amount: subtotalAmount,
                    });

                    if (response.data.success) {
                        setCalculatedFees(response.data.data);
                    }
                } catch (error) {
                    console.error('Failed to bulk calculate fees', error);
                    setCalculatedFees(null);
                } finally {
                    setIsCalculatingFee(false);
                }
            }
        };

        const timeoutId = setTimeout(fetchFee, 300); // Debounce
        return () => clearTimeout(timeoutId);
    }, [data.product_id, data.qty, selectedProduct]);

    // Calculate cheapest payment method taking into account dynamic fees and subtotal
    const cheapestPaymentMethodId = React.useMemo(() => {
        if (!selectedProduct) return null;

        let lowestTotal = Infinity;
        let cheapestId: string | null = null;
        const subtotal = selectedProduct.price * data.qty;

        Object.values(paymentMethods)
            .flat()
            .forEach((method) => {
                if (subtotal >= method.minimum_amount) {
                    const staticAdminFee = Math.ceil(
                        method.fee_flat + (subtotal * method.fee_percent) / 100,
                    );
                    const fetchedFee = calculatedFees
                        ? calculatedFees[method.id]
                        : null;
                    const displayAdminFee =
                        fetchedFee !== null && fetchedFee !== undefined
                            ? fetchedFee
                            : staticAdminFee;
                    const total = subtotal + displayAdminFee;

                    if (total < lowestTotal) {
                        lowestTotal = total;
                        cheapestId = method.id;
                    }
                }
            });

        return cheapestId;
    }, [selectedProduct, data.qty, calculatedFees, paymentMethods]);

    // Format WhatsApp Number (IndoPhone)
    const [waDigits, setWaDigits] = useState('');
    const COUNTRY_CODE = '+62';

    const handleWaChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        let value = e.target.value.replace(/\D/g, '');
        if (value.startsWith('62')) {
            value = value.slice(2);
        }
        value = value.slice(0, 11);
        setWaDigits(value);
        setData('whatsapp', value.length >= 9 ? COUNTRY_CODE + value : '');
    };

    const formatIndoPhone = (digits: string) => {
        const p1 = digits.slice(0, 3);
        const p2 = digits.slice(3, 7);
        const p3 = digits.slice(7, 11);
        return [COUNTRY_CODE, p1, p2, p3].filter(Boolean).join(' ');
    };
    const formattedWa = formatIndoPhone(waDigits);

    // Deselect payment method if the current product price changes to below the minimum amount
    useEffect(() => {
        if (selectedProduct && selectedPayment) {
            const subtotal = selectedProduct.price * data.qty;
            if (subtotal < selectedPayment.minimum_amount) {
                setData('payment_method', ''); // Clear invalid selection
            }
        }
    }, [data.product_id, data.qty]);

    const handlePurchase = () => {
        if (!data.user_id) {
            alert('Silakan masukkan ID pemain Anda.');
            return;
        }
        if (!selectedProduct) {
            alert('Silakan pilih produk yang ingin dibeli.');
            return;
        }
        if (!selectedPayment) {
            alert('Silakan pilih metode pembayaran.');
            return;
        }
        if (
            isValidating ||
            !validatedUsername ||
            validatedUsername.startsWith('❌')
        ) {
            alert(
                'ID belum diisi atau tidak valid/ditemukan. Mohon periksa kembali.',
            );
            return;
        }

        setShowModal(true);
    };

    const submitOrder = async () => {
        if (isSubmitting) return;
        if (!validatedUsername || validatedUsername.startsWith('❌')) {
            alert('ID belum valid. Pastikan data akun benar.');
            return;
        }
        const payload = {
            product_id: data.product_id,
            customer_game_id: data.user_id,
            customer_zone_id: data.server_id || null,
            customer_whatsapp: data.whatsapp,
            customer_name: validatedUsername,
            payment_method: data.payment_method,
            qty: data.qty,
        };

        try {
            setIsSubmitting(true);
            const response = await axios.post('/api/checkout', payload);
            if (
                response.data.success &&
                response.data.data.transaction.invoice_id
            ) {
                router.visit(
                    '/invoice?invoice_id=' +
                        response.data.data.transaction.invoice_id,
                );
            } else {
                alert('Gagal membuat pesanan. Silakan coba lagi.');
            }
        } catch (error: any) {
            alert(
                error.response?.data?.message ||
                    'Terjadi kesalahan saat memproses pesanan.',
            );
        } finally {
            setIsSubmitting(false);
            setShowModal(false);
        }
    };

    const handleCheckoutClick = () => {
        if (!data.product_id) {
            alert('Silakan pilih produk terlebih dahulu!');
            return;
        }
        if (!data.payment_method) {
            alert('Silakan pilih metode pembayaran!');
            return;
        }
        setShowModal(true);
    };

    const confirmCheckout = () => {
        setShowModal(false);
        // post('/checkout', { ... })
        alert('Fitur Checkout akan segera diproses!');
    };

    return (
        <GuestLayout>
            <Head title={`${game.name} - Nuvelo`} />

            {/* Background Texture & Hero Graphic */}
            <div className="relative min-h-screen pb-40 md:pb-32">
                <div className="mx-auto max-w-7xl px-3 pt-4 sm:px-6 md:pt-10 lg:px-8">
                    {/* Breadcrumbs */}
                    <div className="mb-4 flex items-center gap-1.5 text-xs text-gray-400 md:mb-6 md:gap-2 md:text-sm">
                        <span>Beranda</span>
                        <span>›</span>
                        <span>Top Up</span>
                        <span>›</span>
                        <span className="font-semibold text-white">
                            {game.name}
                        </span>

                        <div className="ml-auto hidden md:block">
                            <button className="flex items-center gap-2 rounded-full border border-[#31334c] px-4 py-1.5 text-xs text-gray-300 transition hover:bg-white/5 hover:text-white">
                                Cara Pembelian{' '}
                                <svg
                                    width="14"
                                    height="14"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 16v-4" />
                                    <path d="M12 8h.01" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* Header Banner (Replicated from ClientTpProductInfo) */}
                    <div className="flex-start relative mt-6 flex h-fit w-full flex-col flex-wrap items-center justify-between gap-4 px-3 pb-6 md:mt-12 md:gap-8 md:px-8 md:pb-8 lg:flex-row lg:items-end">
                        {/* Game Card Thumbnail — fixed small size on mobile, original sm size on desktop */}
                        <div className="relative z-[10] shrink-0">
                            <GameCard
                                id={game.id}
                                title={game.name}
                                subTitle={game.publisher}
                                imgSrc={getImageUrl(game.image)}
                                slug={game.slug}
                                active={true}
                                customClass="!w-28 !h-[160px] md:!w-auto md:!h-auto"
                                cardSize="sm"
                            />
                        </div>

                        {/* Product Info */}
                        <div
                            className="z-10 flex w-full flex-1 flex-col flex-wrap items-start gap-4 md:w-fit md:flex-row md:gap-8"
                            id="client-product-info"
                        >
                            {/* Product Detail */}
                            <div
                                className="flex w-full flex-1 flex-col items-start justify-between md:w-fit"
                                id="client-product-detail"
                            >
                                <h3 className="mb-0.5 text-lg font-semibold text-white md:mb-1 md:text-2xl">
                                    {game.name}
                                </h3>
                                <h4 className="mb-2 text-sm text-[#FFC107] text-client-warning md:mb-2.5 md:text-lg">
                                    {game.publisher}
                                </h4>

                                {/* Badges */}
                                <div
                                    id="client-product-detail-badges"
                                    className="item-center flex flex-wrap gap-1.5 md:gap-2"
                                >
                                    <div className="flex cursor-pointer items-center gap-1 rounded-full border border-[#3b82f6]/30 bg-[#1e6fdb]/20 px-2.5 py-0.5 text-[0.65rem] font-semibold whitespace-nowrap text-[#3b82f6] md:gap-1.5 md:px-3 md:py-1 md:text-[0.7rem]">
                                        <svg
                                            width="12"
                                            height="12"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                        >
                                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
                                        </svg>
                                        Proses Cepat
                                    </div>
                                    <div className="flex cursor-pointer items-center gap-1 rounded-full border border-[#3b82f6]/30 bg-[#1e6fdb]/20 px-2.5 py-0.5 text-[0.65rem] font-semibold whitespace-nowrap text-[#3b82f6] md:gap-1.5 md:px-3 md:py-1 md:text-[0.7rem]">
                                        <svg
                                            width="12"
                                            height="12"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                        >
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                                        </svg>
                                        Customer Service 24/7
                                    </div>
                                </div>
                            </div>

                            {/* Separator Line (Vertical) md and up */}
                            <div className="hidden h-full items-center justify-center md:flex">
                                <svg
                                    width="1"
                                    height="100"
                                    viewBox="0 0 1 100"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    className="w-[1px] stroke-white/20"
                                >
                                    <line
                                        x1="0.5"
                                        y1="2.18557e-08"
                                        x2="0.499994"
                                        y2="141"
                                        stroke="currentColor"
                                    />
                                </svg>
                            </div>

                            {/* Separator Line (Horizontal) Mobile only */}
                            <div className="mt-1 h-px w-full rounded-full bg-gradient-to-r from-transparent via-white/50 to-transparent select-none md:hidden"></div>

                            {/* Rating — mobile: compact inline. Desktop: restored to original sizing */}
                            <div className="w-fit" id="client-product-rating">
                                <h5 className="md:text-md mb-1 text-xs text-nowrap text-gray-400 md:mb-1 md:text-[0.6rem]">
                                    Ulasan & Penilaian
                                </h5>
                                <div className="flex items-center gap-2 md:flex-col md:items-start md:gap-0">
                                    <p className="text-2xl font-bold text-white md:text-[1.26rem]">
                                        {game.rating}
                                    </p>
                                    <div className="flex text-[#FFC107] md:mt-1 md:mb-1">
                                        {[1, 2, 3, 4, 5].map((star) => (
                                            <svg
                                                key={star}
                                                width="16"
                                                height="16"
                                                viewBox="0 0 24 24"
                                                fill="currentColor"
                                            >
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                            </svg>
                                        ))}
                                    </div>
                                </div>
                                <p className="mt-1 text-xs text-nowrap text-gray-500 md:mt-0 md:text-[0.6rem]">
                                    Berdasarkan {game.reviews_count} penilaian
                                </p>
                            </div>
                        </div>

                        {/* Background Card Effect (Bunder2 di mobile) */}
                        <div className="absolute top-1/3 right-0 bottom-0 left-0 z-0 overflow-hidden rounded-2xl bg-[#242533] p-20 lg:top-[30%]">
                            <div className="absolute -right-42 -bottom-62 h-[150%] w-[150%] rotate-45 bg-[radial-gradient(#c26eff_2px,transparent_2px)] [background-size:16px_16px] opacity-10 md:hidden"></div>
                        </div>
                    </div>

                    {/* Forms Content */}
                    <div className="mt-6 grid grid-cols-1 gap-4 md:mt-10 md:gap-8 lg:grid-cols-3">
                        {/* Left Column (Inputs) */}
                        <div className="flex flex-col gap-4 md:gap-6 lg:col-span-2">
                            {/* SECTION 1: Informasi Akun */}
                            <div className="mt-0 overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29] shadow-lg">
                                {/* Header (Refactored from StepAccountInfo) */}
                                <div className="flex h-12 overflow-hidden rounded-t-xl border-b border-[#31334c]">
                                    <div className="flex w-12 shrink-0 items-center justify-center bg-[#c26eff] text-lg font-bold text-white">
                                        1
                                    </div>
                                    <div className="flex flex-1 items-center bg-[#31334c] px-4">
                                        <h4 className="text-sm font-semibold text-white">
                                            Informasi Akun
                                        </h4>
                                    </div>
                                </div>
                                <div className="space-y-4 p-5">
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <label className="mb-1 block text-xs text-white/70">
                                                User ID
                                            </label>
                                            <input
                                                type="text"
                                                placeholder="Masukkan User ID"
                                                value={data.user_id}
                                                onChange={(e) =>
                                                    setData(
                                                        'user_id',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border-none bg-[#2b2834] px-3 py-2 text-sm text-white placeholder-gray-500 outline-none focus:ring-1 focus:ring-primary"
                                            />
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-xs text-white/70">
                                                Server
                                            </label>
                                            <input
                                                type="text"
                                                disabled={isMihoyoGame}
                                                placeholder={
                                                    isMihoyoGame
                                                        ? 'Otomatis dari UID'
                                                        : 'Masukkan Server'
                                                }
                                                value={
                                                    isMihoyoGame
                                                        ? detectMihoyoServer(
                                                              data.user_id,
                                                          )?.name || ''
                                                        : data.server_id
                                                }
                                                onChange={(e) =>
                                                    !isMihoyoGame &&
                                                    setData(
                                                        'server_id',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border-none bg-[#2b2834] px-3 py-2 text-sm text-white placeholder-gray-500 outline-none focus:ring-1 focus:ring-primary disabled:bg-[#1a1a24] disabled:text-white/60"
                                            />
                                        </div>
                                    </div>
                                    <div
                                        className={`rounded-md px-4 py-2 text-xs ${
                                            isValidating
                                                ? 'bg-blue-500/15 text-blue-400'
                                                : validatedUsername &&
                                                    !validatedUsername.startsWith(
                                                        '❌',
                                                    )
                                                  ? 'bg-green-500/15 text-green-400'
                                                  : validatedUsername
                                                    ? 'bg-red-500/15 text-red-400'
                                                    : 'bg-client-warning/15 text-client-warning'
                                        }`}
                                    >
                                        {isValidating && 'Mengecek akun...'}
                                        {!isValidating &&
                                            !validatedUsername &&
                                            'Pastikan Anda mengisi data akun dengan benar'}
                                        {!isValidating &&
                                            validatedUsername &&
                                            !validatedUsername.startsWith(
                                                '❌',
                                            ) && (
                                                <>
                                                    ✅ Nickname:{' '}
                                                    <b className="ml-1 text-white">
                                                        {validatedUsername}
                                                    </b>
                                                </>
                                            )}
                                        {!isValidating &&
                                            validatedUsername &&
                                            validatedUsername.startsWith(
                                                '❌',
                                            ) && <>{validatedUsername}</>}
                                    </div>
                                </div>
                            </div>

                            {/* SECTION 2: Detail Kontak */}
                            <div className="mt-0 overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29] shadow-lg md:mt-6">
                                {/* Header */}
                                <div className="flex h-12 overflow-hidden rounded-t-xl border-b border-[#31334c]">
                                    <div className="flex w-12 shrink-0 items-center justify-center bg-[#c26eff] text-lg font-bold text-white">
                                        2
                                    </div>
                                    <div className="flex flex-1 items-center bg-[#31334c] px-4">
                                        <h4 className="text-sm font-semibold text-white">
                                            Detail Kontak
                                        </h4>
                                    </div>
                                </div>
                                <div className="space-y-4 p-5">
                                    <div>
                                        <label className="mb-1 block text-xs text-white/70">
                                            No. WhatsApp
                                        </label>
                                        <input
                                            type="tel"
                                            placeholder="+62 8xxx xxxx xxxx"
                                            value={formattedWa}
                                            onChange={handleWaChange}
                                            className="w-full rounded-md border-none bg-[#2b2834] px-3 py-2 text-sm text-white placeholder-gray-500 outline-none focus:ring-1 focus:ring-primary"
                                        />
                                        <p className="mt-2 text-xs text-white/50">
                                            Contoh: +62 867 0XXX XXXX
                                        </p>
                                    </div>
                                    <div className="rounded-md bg-client-warning/15 px-4 py-2 text-xs text-[#FFC107]">
                                        Nomor ini akan kami hubungi jika terjadi
                                        masalah
                                    </div>
                                </div>
                            </div>

                            {/* SECTION 3: Pilih Nominal */}
                            <div className="mt-0 overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29] shadow-lg md:mt-6">
                                {/* Header */}
                                <div className="flex h-12 overflow-hidden rounded-t-xl border-b border-[#31334c]">
                                    <div className="flex w-12 shrink-0 items-center justify-center bg-[#c26eff] text-lg font-bold text-white">
                                        3
                                    </div>
                                    <div className="flex flex-1 items-center bg-[#31334c] px-4">
                                        <h4 className="text-sm font-semibold text-white">
                                            Pilih Nominal
                                        </h4>
                                    </div>
                                </div>
                                <div className="p-4">
                                    {/* Tabs */}
                                    <div className="mb-4 flex flex-wrap gap-2">
                                        {Object.keys(productGroups).map(
                                            (tab) => (
                                                <button
                                                    key={tab}
                                                    onClick={() =>
                                                        setActiveTab(tab)
                                                    }
                                                    className={`rounded-lg border px-4 py-1.5 text-sm transition ${activeTab === tab ? 'border-primary bg-primary text-white shadow-[0_0_10px_rgba(168,85,247,0.4)]' : 'border-[#31334c] text-white hover:bg-white/10'}`}
                                                >
                                                    {tab}
                                                </button>
                                            ),
                                        )}
                                    </div>

                                    {/* Grid Products */}
                                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 md:gap-4">
                                        {(productGroups[activeTab] || []).map(
                                            (product) => (
                                                <div
                                                    key={product.id}
                                                    onClick={() =>
                                                        setData(
                                                            'product_id',
                                                            product.id,
                                                        )
                                                    }
                                                    className={`group relative cursor-pointer overflow-hidden rounded-xl border bg-[#1A1A24] p-3 transition-all hover:border-[#6a359c] md:p-4 ${data.product_id === product.id ? 'border-primary shadow-[0_0_15px_rgba(168,85,247,0.2)] ring-1 ring-primary' : 'border-[#31334c]'}`}
                                                >
                                                    {/* Card Content */}
                                                    <div className="relative z-10 flex h-full flex-col justify-between">
                                                        <div className="mb-2 flex items-start justify-between">
                                                            <div>
                                                                <div className="text-sm leading-tight font-bold text-[#FFC107]">
                                                                    {
                                                                        product.clean_name
                                                                    }
                                                                </div>
                                                                {product.extra && (
                                                                    <div className="mt-0.5 text-[10px] text-gray-400">
                                                                        {
                                                                            product.extra
                                                                        }
                                                                    </div>
                                                                )}
                                                            </div>
                                                            <div className="flex h-6 w-6 shrink-0 items-center justify-center">
                                                                <img
                                                                    src="https://cdns.iconmonstr.com/wp-content/releases/preview/2012/240/iconmonstr-diamond-1.png"
                                                                    alt="Diamond"
                                                                    className="h-5 w-5 hue-rotate-[180deg] invert-[0.8] saturate-[3] sepia-[1]"
                                                                />
                                                            </div>
                                                        </div>
                                                        <div className="mt-2 font-bold text-white">
                                                            Rp{' '}
                                                            {product.price.toLocaleString(
                                                                'id-ID',
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Active state gradient background */}
                                                    {data.product_id ===
                                                        product.id && (
                                                        <div className="pointer-events-none absolute inset-0 bg-gradient-to-br from-primary/10 to-transparent"></div>
                                                    )}
                                                </div>
                                            ),
                                        )}
                                    </div>
                                    {(productGroups[activeTab] || []).length ===
                                        0 && (
                                        <div className="py-10 text-center text-gray-500">
                                            Belum ada produk untuk kategori ini.
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* SECTION 4: Detail Pembelian */}
                            <div className="mt-0 overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29] shadow-lg">
                                {/* Header */}
                                <div className="flex h-12 overflow-hidden rounded-t-xl border-b border-[#31334c]">
                                    <div className="flex w-12 shrink-0 items-center justify-center bg-[#c26eff] text-lg font-bold text-white">
                                        4
                                    </div>
                                    <div className="flex flex-1 items-center bg-[#31334c] px-4">
                                        <h4 className="text-sm font-semibold text-white">
                                            Detail Pembelian
                                        </h4>
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                                    <div>
                                        <label className="mb-1 block text-xs text-white/70">
                                            Jumlah Pembelian
                                        </label>
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="number"
                                                value={data.qty}
                                                readOnly
                                                className="w-14 rounded-lg border-none bg-[#2b2735] px-3 py-2 text-white outline-none"
                                            />
                                            <button
                                                onClick={() =>
                                                    setData(
                                                        'qty',
                                                        Math.max(
                                                            1,
                                                            data.qty - 1,
                                                        ),
                                                    )
                                                }
                                                className="rounded-lg bg-primary px-3 py-2 font-bold text-white transition hover:bg-primary/80"
                                            >
                                                −
                                            </button>
                                            <button
                                                onClick={() =>
                                                    setData('qty', data.qty + 1)
                                                }
                                                className="rounded-lg bg-primary px-3 py-2 font-bold text-white transition hover:bg-primary/80"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-xs text-white/70">
                                            Kode Promo
                                        </label>
                                        <div className="flex gap-2">
                                            <input
                                                type="text"
                                                placeholder="Masukkan kode promo"
                                                value={data.promo_code}
                                                onChange={(e) =>
                                                    setData(
                                                        'promo_code',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full min-w-0 flex-1 rounded-lg border-none bg-[#2f2a3a] px-3 py-2 text-sm text-white placeholder-gray-500 outline-none focus:ring-1 focus:ring-primary"
                                            />
                                            <button className="shrink-0 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/80">
                                                Pakai
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Right Column (Details & Payments) */}
                        <div className="flex flex-col gap-4 md:gap-6">
                            {/* SECTION 5: Metode Pembayaran */}
                            <div className="mt-0 overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29] shadow-lg">
                                {/* Header */}
                                <div className="flex h-12 overflow-hidden rounded-t-xl border-b border-[#31334c]">
                                    <div className="flex w-12 shrink-0 items-center justify-center bg-[#c26eff] text-lg font-bold text-white">
                                        5
                                    </div>
                                    <div className="flex flex-1 items-center bg-[#31334c] px-4">
                                        <h4 className="text-sm font-semibold text-white">
                                            Metode Pembayaran
                                        </h4>
                                    </div>
                                </div>
                                <div className="space-y-4 p-4">
                                    {/* Iterating Categories */}
                                    {paymentMethodEntries.map(
                                        ([category, methods]) => {
                                            const isOpen =
                                                openCategories[category] !==
                                                false;
                                            const isQRIS =
                                                category.toUpperCase() ===
                                                    'QRIS' ||
                                                category
                                                    .toUpperCase()
                                                    .includes('QRIS');
                                            const isCoin =
                                                category === 'Krysta Coin';

                                            return (
                                                <div
                                                    key={category}
                                                    className="overflow-hidden rounded-lg bg-[#3a3545]"
                                                >
                                                    {/* ===== GROUP HEADER ===== */}
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            !isCoin &&
                                                            toggleCategory(
                                                                category,
                                                            )
                                                        }
                                                        className={`flex w-full items-center justify-between px-4 py-3 ${isCoin ? 'cursor-default' : 'cursor-pointer'}`}
                                                    >
                                                        <div className="flex items-center gap-2">
                                                            {/* Icon coin di group header */}
                                                            {isCoin && (
                                                                <div className="flex h-5 w-5 shrink-0 items-center justify-center overflow-hidden rounded-full">
                                                                    {methods[0]
                                                                        ?.icon_url ? (
                                                                        <img
                                                                            src={
                                                                                methods[0]
                                                                                    .icon_url
                                                                            }
                                                                            alt="Krysta Coin"
                                                                            className="h-full w-full object-contain"
                                                                        />
                                                                    ) : (
                                                                        <span className="text-[10px] font-bold text-gray-500">
                                                                            COIN
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            )}
                                                            <span className="text-sm font-semibold text-white">
                                                                {category}
                                                            </span>
                                                            {/* Badge saldo kalau sudah login */}
                                                            {isCoin &&
                                                                methods[0]
                                                                    ?.disabled ===
                                                                    false && (
                                                                    <span className="rounded-full bg-[#2f2a3a] px-2 py-0.5 text-[10px] font-semibold text-[#c26eff]">
                                                                        {methods[0]?.coin_balance?.toLocaleString(
                                                                            'id-ID',
                                                                        ) ??
                                                                            0}{' '}
                                                                        Coins
                                                                    </span>
                                                                )}
                                                        </div>

                                                        {/* Icons preview — hanya untuk non-coin dan non-QRIS */}
                                                        {!isCoin && !isQRIS && (
                                                            <div className="flex items-center gap-3">
                                                                <div className="flex gap-2">
                                                                    {methods
                                                                        .slice(
                                                                            0,
                                                                            4,
                                                                        )
                                                                        .map(
                                                                            (
                                                                                pm,
                                                                                i,
                                                                            ) => (
                                                                                <div
                                                                                    key={
                                                                                        i
                                                                                    }
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
                                                        )}
                                                    </button>

                                                    {/* ===== CONTENT ===== */}
                                                    {(isOpen || isCoin) && (
                                                        <div className="space-y-2 bg-[#2f2a3a] p-3">
                                                            {methods.map(
                                                                (
                                                                    method,
                                                                    index,
                                                                ) => {
                                                                    const subtotal =
                                                                        (selectedProduct?.price ||
                                                                            0) *
                                                                        data.qty;

                                                                    // Validasi berbeda untuk coin vs metode biasa
                                                                    const isValidAmount =
                                                                        subtotal >=
                                                                        method.minimum_amount;

                                                                    const valid =
                                                                        method.is_coin
                                                                            ? !method.disabled &&
                                                                              (method.coin_balance ??
                                                                                  0) >=
                                                                                  subtotal &&
                                                                              selectedProduct !=
                                                                                  null
                                                                            : selectedProduct !=
                                                                                  null &&
                                                                              isValidAmount;

                                                                    const isChecked =
                                                                        data.payment_method ===
                                                                        method.id;

                                                                    // Fee coin selalu 0
                                                                    const staticAdminFee =
                                                                        method.is_coin
                                                                            ? 0
                                                                            : Math.ceil(
                                                                                  method.fee_flat +
                                                                                      (subtotal *
                                                                                          method.fee_percent) /
                                                                                          100,
                                                                              );

                                                                    const fetchedFee =
                                                                        calculatedFees
                                                                            ? calculatedFees[
                                                                                  method
                                                                                      .id
                                                                              ]
                                                                            : null;
                                                                    const displayAdminFee =
                                                                        method.is_coin
                                                                            ? 0
                                                                            : fetchedFee !==
                                                                                    null &&
                                                                                fetchedFee !==
                                                                                    undefined
                                                                              ? fetchedFee
                                                                              : staticAdminFee;
                                                                    const displayTotal =
                                                                        subtotal +
                                                                        displayAdminFee;

                                                                    // ===== COIN ITEM — BELUM LOGIN =====
                                                                    if (
                                                                        method.is_coin &&
                                                                        method.disabled
                                                                    ) {
                                                                        return (
                                                                            <div
                                                                                key={
                                                                                    method.id
                                                                                }
                                                                                className="flex w-full items-center justify-between rounded-lg bg-[#3a3545] p-3 opacity-70"
                                                                            >
                                                                                <div className="flex items-center gap-3">
                                                                                    <div className="flex h-6 w-12 shrink-0 items-center justify-center overflow-hidden rounded">
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
                                                                                            <span className="text-[10px] font-bold text-gray-500">
                                                                                                COIN
                                                                                            </span>
                                                                                        )}
                                                                                    </div>
                                                                                    <div className="text-left">
                                                                                        <p className="text-sm font-semibold text-white">
                                                                                            {
                                                                                                method.name
                                                                                            }
                                                                                        </p>
                                                                                        <p className="text-xs text-yellow-400">
                                                                                            Login
                                                                                            untuk
                                                                                            menggunakan
                                                                                            Krysta
                                                                                            Coin
                                                                                        </p>
                                                                                    </div>
                                                                                </div>
                                                                                <a
                                                                                    href="/login"
                                                                                    className="shrink-0 rounded-lg bg-yellow-500 px-3 py-1.5 text-xs font-bold text-black transition hover:bg-yellow-400"
                                                                                >
                                                                                    Login
                                                                                </a>
                                                                            </div>
                                                                        );
                                                                    }

                                                                    // ===== COIN ITEM — SUDAH LOGIN TAPI SALDO KURANG =====
                                                                    if (
                                                                        method.is_coin &&
                                                                        !method.disabled &&
                                                                        selectedProduct &&
                                                                        (method.coin_balance ??
                                                                            0) <
                                                                            subtotal
                                                                    ) {
                                                                        return (
                                                                            <div
                                                                                key={
                                                                                    method.id
                                                                                }
                                                                                className="flex w-full items-center justify-between rounded-lg bg-[#3a3545] p-3 opacity-60"
                                                                            >
                                                                                <div className="flex items-center gap-3">
                                                                                    <div className="flex h-6 w-12 shrink-0 items-center justify-center overflow-hidden rounded">
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
                                                                                            <span className="text-[10px] font-bold text-gray-500">
                                                                                                COIN
                                                                                            </span>
                                                                                        )}
                                                                                    </div>
                                                                                    <div className="text-left">
                                                                                        <p className="text-sm font-semibold text-white">
                                                                                            {
                                                                                                method.name
                                                                                            }
                                                                                        </p>
                                                                                        <p className="text-xs text-red-400">
                                                                                            Saldo
                                                                                            tidak
                                                                                            cukup
                                                                                            (
                                                                                            {method.coin_balance?.toLocaleString(
                                                                                                'id-ID',
                                                                                            ) ??
                                                                                                0}{' '}
                                                                                            Coins)
                                                                                        </p>
                                                                                    </div>
                                                                                </div>
                                                                                <p className="text-sm font-semibold text-white">
                                                                                    Rp{' '}
                                                                                    {subtotal.toLocaleString(
                                                                                        'id-ID',
                                                                                    )}
                                                                                </p>
                                                                            </div>
                                                                        );
                                                                    }

                                                                    // ===== ITEM BIASA (Tripay + Coin yang valid) =====
                                                                    return (
                                                                        <button
                                                                            key={
                                                                                method.id
                                                                            }
                                                                            disabled={
                                                                                !valid
                                                                            }
                                                                            onClick={() =>
                                                                                setData(
                                                                                    'payment_method',
                                                                                    method.id,
                                                                                )
                                                                            }
                                                                            className={`flex w-full cursor-pointer items-center justify-between rounded-lg p-3 transition ${isChecked ? 'ring-2 ring-primary' : 'bg-[#3a3545]'} ${!valid && 'cursor-not-allowed opacity-50'}`}
                                                                        >
                                                                            <div className="flex items-center gap-3">
                                                                                <div
                                                                                    className={`flex h-6 w-12 shrink-0 items-center justify-center overflow-hidden rounded ${method.is_coin ? '' : 'bg-white p-0.5'}`}
                                                                                >
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
                                                                                        <span className="text-[10px] font-bold text-gray-500">
                                                                                            COIN
                                                                                        </span>
                                                                                    )}
                                                                                </div>
                                                                                <div className="text-left">
                                                                                    <p className="text-sm font-semibold text-white">
                                                                                        {
                                                                                            method.name
                                                                                        }
                                                                                    </p>
                                                                                    {!selectedProduct ? (
                                                                                        <p className="text-xs text-red-400">
                                                                                            Pilih
                                                                                            Produk
                                                                                        </p>
                                                                                    ) : !isValidAmount &&
                                                                                      !method.is_coin ? (
                                                                                        <p className="text-xs text-red-400">
                                                                                            Min.
                                                                                            Rp{' '}
                                                                                            {method.minimum_amount.toLocaleString(
                                                                                                'id-ID',
                                                                                            )}
                                                                                        </p>
                                                                                    ) : (
                                                                                        <p className="text-xs text-gray-300">
                                                                                            {method.is_coin ? (
                                                                                                'Bebas Biaya Admin'
                                                                                            ) : isCalculatingFee ? (
                                                                                                <span className="animate-pulse italic">
                                                                                                    Menghitung...
                                                                                                </span>
                                                                                            ) : (
                                                                                                `Admin Rp ${displayAdminFee.toLocaleString('id-ID')}`
                                                                                            )}
                                                                                        </p>
                                                                                    )}
                                                                                </div>
                                                                            </div>

                                                                            <div className="text-right">
                                                                                <p className="text-sm font-semibold text-white">
                                                                                    Rp{' '}
                                                                                    {isCalculatingFee &&
                                                                                    !method.is_coin ? (
                                                                                        <span className="animate-pulse italic">
                                                                                            ...
                                                                                        </span>
                                                                                    ) : (
                                                                                        displayTotal.toLocaleString(
                                                                                            'id-ID',
                                                                                        )
                                                                                    )}
                                                                                </p>
                                                                                {method.is_coin && (
                                                                                    <span className="mt-1 inline-block rounded bg-[#c26eff] px-2 py-0.5 text-[10px] font-bold text-white shadow-[0_0_10px_rgba(194,110,255,0.4)]">
                                                                                        NO
                                                                                        FEE
                                                                                    </span>
                                                                                )}
                                                                                {!method.is_coin &&
                                                                                    method.id ===
                                                                                        cheapestPaymentMethodId && (
                                                                                        <span className="mt-1 inline-block rounded bg-purple-500 px-2 py-0.5 text-[10px] font-bold text-white shadow-[0_0_10px_rgba(168,85,247,0.4)]">
                                                                                            BEST
                                                                                            PRICE
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
                            </div>

                            {/* SECTION 6: Rincian Pembayaran — hidden on mobile */}
                            {selectedProduct && selectedPayment && (
                                <div className="mt-0 mb-20 hidden overflow-hidden rounded-xl border border-[#31334c] bg-[#1e1f29] shadow-lg md:mb-0 md:block">
                                    <div className="flex h-12 overflow-hidden rounded-t-xl border-b border-[#31334c]">
                                        <div className="flex w-12 shrink-0 items-center justify-center bg-[#c26eff] text-lg font-bold text-white">
                                            6
                                        </div>
                                        <div className="flex flex-1 items-center bg-[#31334c] px-4">
                                            <h4 className="text-sm font-semibold text-white">
                                                Rincian Pembayaran
                                            </h4>
                                        </div>
                                    </div>
                                    <div className="space-y-3 p-5">
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-gray-400">
                                                Harga Produk
                                            </span>
                                            <span className="font-medium text-white">
                                                Rp{' '}
                                                {(
                                                    selectedProduct.price *
                                                    data.qty
                                                ).toLocaleString('id-ID')}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-gray-400">
                                                Biaya Admin (
                                                {selectedPayment.name})
                                            </span>
                                            {isCalculatingFee ? (
                                                <span className="animate-pulse text-xs text-gray-400 italic">
                                                    Menghitung...
                                                </span>
                                            ) : (
                                                <span className="font-medium text-white">
                                                    Rp{' '}
                                                    {(calculatedFees?.[
                                                        selectedPayment.id
                                                    ] !== undefined
                                                        ? calculatedFees[
                                                              selectedPayment.id
                                                          ]
                                                        : Math.ceil(
                                                              selectedPayment.fee_flat +
                                                                  (selectedProduct.price *
                                                                      data.qty *
                                                                      selectedPayment.fee_percent) /
                                                                      100,
                                                          )
                                                    ).toLocaleString('id-ID')}
                                                </span>
                                            )}
                                        </div>
                                        {data.promo_code && (
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-green-400">
                                                    Diskon Promo
                                                </span>
                                                <span className="font-medium text-green-400">
                                                    - Rp 0
                                                </span>
                                            </div>
                                        )}
                                        <hr className="my-3 border-dashed border-[#31334c] opacity-50" />
                                        <div className="flex items-center justify-between">
                                            <span className="text-base font-bold text-white">
                                                Total Keseluruhan
                                            </span>
                                            <span className="text-lg font-black text-[#FFC107]">
                                                Rp{' '}
                                                {(
                                                    selectedProduct.price *
                                                        data.qty +
                                                    (calculatedFees?.[
                                                        selectedPayment.id
                                                    ] !== undefined
                                                        ? calculatedFees[
                                                              selectedPayment.id
                                                          ]
                                                        : Math.ceil(
                                                              selectedPayment.fee_flat +
                                                                  (selectedProduct.price *
                                                                      data.qty *
                                                                      selectedPayment.fee_percent) /
                                                                      100,
                                                          ))
                                                ).toLocaleString('id-ID')}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Floating Action Menu (Bottom Docked) — positioned above bottom nav on mobile */}
            <div className="fixed bottom-[60px] left-0 z-[45] w-full border-t border-[#31334c] bg-[#1e1f29] shadow-[0_-10px_30px_rgba(0,0,0,0.5)] md:bottom-0">
                <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-3 py-3 sm:flex-row sm:px-6 md:flex-row md:gap-4 md:px-4 md:py-4 lg:px-8">
                    {/* Payment Breakdown — Mobile Only (displayed above total) */}
                    {selectedProduct && selectedPayment && (
                        <div className="w-full space-y-2 border-b border-[#31334c] pb-3 md:hidden">
                            <div className="flex items-center justify-between text-xs">
                                <span className="text-gray-400">
                                    Harga Produk
                                </span>
                                <span className="font-medium text-white">
                                    Rp{' '}
                                    {(
                                        selectedProduct.price * data.qty
                                    ).toLocaleString('id-ID')}
                                </span>
                            </div>
                            <div className="flex items-center justify-between text-xs">
                                <span className="text-gray-400">
                                    Biaya Admin ({selectedPayment.name})
                                </span>
                                <span className="font-medium text-white">
                                    Rp{' '}
                                    {(calculatedFees?.[selectedPayment.id] !==
                                    undefined
                                        ? calculatedFees[selectedPayment.id]
                                        : Math.ceil(
                                              selectedPayment.fee_flat +
                                                  (selectedProduct.price *
                                                      data.qty *
                                                      selectedPayment.fee_percent) /
                                                      100,
                                          )
                                    ).toLocaleString('id-ID')}
                                </span>
                            </div>
                        </div>
                    )}
                    <div className="w-full text-left text-white md:w-auto">
                        <div className="text-xs text-gray-400 md:text-sm">
                            Total Pembayaran
                        </div>
                        <div className="text-base font-black text-[#FFC107] md:text-xl">
                            {selectedProduct && selectedPayment
                                ? `Rp ${(
                                      selectedProduct.price * data.qty +
                                      (calculatedFees?.[selectedPayment.id] !==
                                      undefined
                                          ? calculatedFees[selectedPayment.id]
                                          : Math.ceil(
                                                selectedPayment.fee_flat +
                                                    (selectedProduct.price *
                                                        data.qty *
                                                        selectedPayment.fee_percent) /
                                                        100,
                                            ))
                                  ).toLocaleString('id-ID')}`
                                : 'Rp 0'}
                        </div>
                    </div>

                    {/* Animated Button */}
                    <button
                        onClick={handlePurchase}
                        className="group relative flex w-full shrink-0 items-center justify-center gap-2 overflow-hidden rounded-xl bg-gradient-to-r from-primary to-[#9b4dec] px-6 py-2.5 text-sm font-bold text-white shadow-[0_0_20px_rgba(168,85,247,0.3)] transition hover:shadow-[0_0_30px_rgba(168,85,247,0.5)] md:w-auto md:px-12 md:py-3 md:text-lg"
                    >
                        {/* Particles effect (simulated CSS) */}
                        <div className="absolute inset-0 opacity-0 transition duration-300 group-hover:opacity-100">
                            {[...Array(6)].map((_, i) => (
                                <div
                                    key={i}
                                    className={`absolute h-1 w-1 animate-ping rounded-full bg-white opacity-70`}
                                    style={{
                                        top: `${Math.random() * 100}%`,
                                        left: `${Math.random() * 100}%`,
                                        animationDelay: `${Math.random() * 0.5}s`,
                                        animationDuration: `${0.5 + Math.random()}s`,
                                    }}
                                ></div>
                            ))}
                        </div>

                        <svg
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="relative z-10"
                        >
                            <path d="m5 11 4-7" />
                            <path d="m19 11-4-7" />
                            <path d="M2 11h20" />
                            <path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8c.9 0 1.8-.7 2-1.6l1.7-7.4" />
                            <path d="m9 11 1 9" />
                            <path d="M4.5 15.5h15" />
                            <path d="m15 11-1 9" />
                        </svg>
                        <span className="relative z-10">Buat Pesanan!</span>
                    </button>
                </div>
            </div>

            {/* Modal Konfirmasi Pesanan */}
            {showModal && (
                <div className="animate-fade-in fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-3 py-4 backdrop-blur-sm sm:px-4 md:px-4">
                    <div className="animate-slide-up max-h-[85vh] w-full max-w-md overflow-x-hidden overflow-y-auto rounded-3xl border border-[#31334c] bg-[#242533] shadow-2xl">
                        {/* Modal Header & Graphic */}
                        <div className="sticky top-0 z-10 flex flex-col items-center bg-[#242533] p-4 pb-2 text-center sm:p-5 sm:pb-3 md:p-8 md:pb-4">
                            <div className="pointer-events-none relative mb-3 h-16 w-16 sm:mb-4 sm:h-20 sm:w-20 md:mb-6 md:h-32 md:w-32">
                                {/* Success Circle Check - Replicating mockup graphic */}
                                <div className="absolute inset-0 flex items-center justify-center rounded-full border-4 border-[#242533] bg-gradient-to-tr from-[#1e1f29] to-[#31334c] shadow-[0_0_30px_rgba(74,222,128,0.2)]">
                                    <svg
                                        width="40"
                                        height="40"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#4ade80"
                                        strokeWidth="3"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        className="md:h-[60px] md:w-[60px]"
                                    >
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                </div>
                                {/* Small bag badge */}
                                <div className="absolute -right-2 -bottom-2 rounded-full border-4 border-[#242533] bg-white p-2 shadow-lg">
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#a855f7"
                                        strokeWidth="2"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                        <path d="M3 6h18" />
                                        <path d="M16 10a4 4 0 0 1-8 0" />
                                    </svg>
                                </div>
                                {/* Scattered particles in BG */}
                                <style>{`
                                    @keyframes float-p1 { 0%, 100% { transform: translate(0, 0) rotate(45deg) scale(1); } 50% { transform: translate(-6px, -10px) rotate(90deg) scale(1.1); } }
                                    @keyframes float-p2 { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(4px, -8px) scale(1.2); } }
                                    @keyframes float-p3 { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 50% { transform: translate(-6px, 6px) rotate(45deg); } }
                                    @keyframes float-p4 { 0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.5; } 50% { transform: translate(8px, 10px) scale(1.3); opacity: 1; } }
                                `}</style>
                                <div
                                    className="absolute top-2 left-0 h-2.5 w-2.5 rounded bg-yellow-500/80"
                                    style={{
                                        animation:
                                            'float-p1 3s ease-in-out infinite',
                                    }}
                                ></div>
                                <div
                                    className="absolute bottom-6 -left-5 h-2 w-2 rounded-full bg-blue-400/80"
                                    style={{
                                        animation:
                                            'float-p2 4s ease-in-out infinite',
                                    }}
                                ></div>
                                <div
                                    className="absolute top-8 -right-5 h-2 w-2 rounded-sm border-2 border-gray-400/60"
                                    style={{
                                        animation:
                                            'float-p3 3.5s ease-in-out infinite',
                                    }}
                                ></div>
                                <div
                                    className="absolute -right-8 bottom-2 h-1.5 w-1.5 rounded-full bg-purple-400/80"
                                    style={{
                                        animation:
                                            'float-p4 4.5s ease-in-out infinite',
                                    }}
                                ></div>
                                <div
                                    className="absolute -top-3 right-6 h-1.5 w-1.5 rounded-full bg-green-400/80"
                                    style={{
                                        animation:
                                            'float-p2 3.8s ease-in-out infinite reverse',
                                    }}
                                ></div>
                            </div>

                            <h2 className="mb-1 text-lg font-bold text-white sm:text-xl md:mb-2 md:text-2xl">
                                Konfirmasi Pesanan
                            </h2>
                            <p className="px-1 text-xs text-gray-400 sm:px-2 md:text-sm">
                                Pastikan data akun dan produk yang dipilih valid
                                dan sesuai.
                            </p>
                        </div>

                        {/* Order Summary Form */}
                        <div className="p-3 pt-1 sm:p-4 sm:pt-1 md:p-6 md:pt-2">
                            <div className="mb-3 rounded-2xl border border-[#31334c] bg-[#1a1a24] p-2.5 shadow-inner sm:mb-4 sm:p-3 md:mb-6 md:p-5">
                                <div className="flex flex-col gap-2 text-xs sm:gap-3 sm:text-sm">
                                    <div className="flex flex-col gap-1">
                                        <span className="font-bold text-white">
                                            Username
                                        </span>
                                        <span
                                            className={`min-h-[20px] text-xs font-medium sm:text-sm ${validatedUsername?.startsWith('❌') ? 'text-red-400' : 'text-green-400'}`}
                                        >
                                            {isValidating ? (
                                                <span className="inline-flex animate-pulse items-center gap-2 text-gray-400">
                                                    <svg
                                                        className="h-3.5 w-3.5 animate-spin"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <circle
                                                            className="opacity-25"
                                                            cx="12"
                                                            cy="12"
                                                            r="10"
                                                            stroke="currentColor"
                                                            strokeWidth="4"
                                                        ></circle>
                                                        <path
                                                            className="opacity-75"
                                                            fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                        ></path>
                                                    </svg>
                                                    Memeriksa...
                                                </span>
                                            ) : (
                                                validatedUsername || '-'
                                            )}
                                        </span>
                                    </div>
                                    <div className="flex flex-col gap-1">
                                        <span className="font-bold text-white">
                                            Server
                                        </span>
                                        <span className="text-xs text-gray-300 sm:text-sm">
                                            {data.server_id || '-'}
                                        </span>
                                    </div>
                                    <div className="flex flex-col gap-1">
                                        <span className="font-bold text-white">
                                            ID
                                        </span>
                                        <span className="text-xs text-gray-300 sm:text-sm">
                                            {data.user_id || '-'}
                                        </span>
                                    </div>
                                    <div className="mt-2 border-t border-[#31334c] pt-2">
                                        <div className="flex flex-col gap-1">
                                            <span className="font-bold text-white">
                                                Product
                                            </span>
                                            <span className="text-xs text-gray-300 sm:text-sm">
                                                {game.name}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="flex">
                                        <span className="w-24 shrink-0 font-bold text-white">
                                            Item
                                        </span>
                                        <span className="text-gray-300">
                                            <span className="mr-2">:</span>
                                            {selectedProduct?.name || '-'}
                                        </span>
                                    </div>
                                    <div className="flex">
                                        <span className="w-24 shrink-0 font-bold text-white">
                                            Payment
                                        </span>
                                        <span className="line-clamp-2 text-gray-300">
                                            <span className="mr-2">:</span>
                                            {Object.values(paymentMethods)
                                                .flat()
                                                .find(
                                                    (m) =>
                                                        m.id ===
                                                        data.payment_method,
                                                )?.name || '-'}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <button
                                    onClick={() => setShowModal(false)}
                                    className="rounded-xl border border-red-500/30 bg-transparent px-4 py-3.5 font-bold text-red-500 transition hover:bg-red-500/10"
                                >
                                    Batalkan Pesanan!
                                </button>
                                <button
                                    onClick={submitOrder}
                                    disabled={isSubmitting || isValidating || isCalculatingFee}
                                    className={`rounded-xl px-4 py-3.5 font-bold text-white shadow-[0_0_15px_rgba(168,85,247,0.4)] transition ${isSubmitting || isValidating || isCalculatingFee ? 'cursor-not-allowed bg-gray-500 shadow-none' : 'bg-primary hover:bg-primary/90'}`}
                                >
                                    {isSubmitting
                                        ? 'Memproses...'
                                        : isCalculatingFee
                                            ? 'Menghitung...'
                                            : 'Buat Pesanan!'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </GuestLayout>
    );
}
