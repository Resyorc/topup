export const getPaymentStatusBadge = (
    paymentStatus: string | null | undefined,
) => {
    if (!paymentStatus)
        return { label: '-', className: 'bg-gray-400 text-black' };
    const map: Record<string, { label: string; className: string }> = {
        unpaid: { label: 'BELUM BAYAR', className: 'bg-status-pending-bg border border-status-pending-border text-status-pending' },
        paid: { label: 'LUNAS', className: 'bg-status-success-bg border border-status-success-border text-status-success' },
        expired: { label: 'KADALUARSA', className: 'bg-status-failed-bg border border-status-failed-border text-status-failed' },
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
        pending: { label: 'MENUNGGU', className: 'bg-status-pending-bg border border-status-pending-border text-status-pending' },
        processing: { label: 'DIPROSES', className: 'bg-status-processing-bg border border-status-processing-border text-status-processing' },
        success: { label: 'BERHASIL', className: 'bg-status-success-bg border border-status-success-border text-status-success' },
        failed: { label: 'GAGAL', className: 'bg-status-failed-bg border border-status-failed-border text-status-failed' },
        canceled: { label: 'DIBATALKAN', className: 'bg-status-canceled-bg border border-status-canceled-border text-status-canceled' },
        refunded: { label: 'DIKEMBALIKAN', className: 'bg-status-refunded-bg border border-status-refunded-border text-status-refunded' },
    };
    return (
        map[status] ?? {
            label: status.toUpperCase(),
            className: 'bg-gray-400 text-black',
        }
    );
};

