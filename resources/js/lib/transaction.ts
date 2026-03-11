export const getPaymentStatusBadge = (
    paymentStatus: string | null | undefined,
) => {
    if (!paymentStatus)
        return { label: '-', className: 'bg-gray-400 text-black' };
    const map: Record<string, { label: string; className: string }> = {
        unpaid: { label: 'BELUM BAYAR', className: 'bg-yellow-400 text-black' },
        paid: { label: 'LUNAS', className: 'bg-green-400 text-black' },
        expired: { label: 'KADALUARSA', className: 'bg-red-500 text-white' },
    };
    return (
        map[paymentStatus] ?? {
            label: paymentStatus.toUpperCase(),
            className: 'bg-gray-400 text-black',
        }
    );
};

export const getTransactionStatusBadge = (
    status: string | null | undefined,
) => {
    if (!status) return { label: '-', className: 'bg-gray-400 text-black' };
    const map: Record<string, { label: string; className: string }> = {
        pending: { label: 'MENUNGGU', className: 'bg-yellow-400 text-black' },
        processing: { label: 'DIPROSES', className: 'bg-blue-400 text-black' },
        success: { label: 'BERHASIL', className: 'bg-green-400 text-black' },
        failed: { label: 'GAGAL', className: 'bg-red-500 text-white' },
    };
    return (
        map[status] ?? {
            label: status.toUpperCase(),
            className: 'bg-gray-400 text-black',
        }
    );
};
