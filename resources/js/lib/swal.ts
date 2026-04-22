import Swal from 'sweetalert2';

const swal = Swal.mixin({
    background: 'var(--color-bg-card)',
    color: 'var(--color-text-primary)',
    confirmButtonColor: 'var(--color-primary-light)',
    cancelButtonColor: 'var(--color-border-light)',
    customClass: {
        popup: 'rounded-2xl border border-[var(--color-border-light)] shadow-2xl',
        confirmButton: 'rounded-xl px-5 py-2.5 font-bold text-sm',
        cancelButton: 'rounded-xl px-5 py-2.5 font-bold text-sm',
    },
});

export const swalError = (message: string) =>
    swal.fire({ icon: 'error', title: 'Oops!', text: message, confirmButtonText: 'Tutup' });

export const swalWarning = (message: string) =>
    swal.fire({ icon: 'warning', title: 'Perhatian', text: message, confirmButtonText: 'OK' });

export const swalSuccess = (message: string) =>
    swal.fire({ icon: 'success', title: 'Berhasil!', text: message, confirmButtonText: 'OK' });

export const swalInfo = (message: string) =>
    swal.fire({ icon: 'info', title: 'Info', text: message, confirmButtonText: 'OK' });

export default swal;


