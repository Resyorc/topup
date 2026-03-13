import Swal from 'sweetalert2';

const swal = Swal.mixin({
    background: '#1e1f29',
    color: '#e5e7eb',
    confirmButtonColor: '#a855f7',
    cancelButtonColor: '#374151',
    customClass: {
        popup: 'rounded-2xl border border-[#31334c] shadow-2xl',
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
