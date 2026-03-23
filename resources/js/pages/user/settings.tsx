import { useForm, usePage, router } from '@inertiajs/react';
import axios from 'axios';
import DOMPurify from 'dompurify';
import React, { useRef, useState } from 'react';
import UserLayout from '@/layouts/user-layout';

const TIER_CONFIG: Record<string, { icon: string; label: string; ring: string }> = {
    platinum: { icon: '💎', label: 'Platinum', ring: 'ring-purple-400' },
    gold:     { icon: '🥇', label: 'Gold',     ring: 'ring-yellow-400' },
    silver:   { icon: '🥈', label: 'Silver',   ring: 'ring-blue-400'   },
    bronze:   { icon: '🥉', label: 'Bronze',   ring: 'ring-orange-400' },
};

export default function Settings() {
    const { auth } = usePage().props as any;
    const user = auth?.user;
    const tier = user?.tier ?? 'bronze';
    const tc = TIER_CONFIG[tier] ?? TIER_CONFIG.bronze;
    const initials = user?.name
        ? user.name.split(' ').map((w: string) => w[0]).slice(0, 2).join('').toUpperCase()
        : '?';

    // Avatar upload state
    const avatarInputRef = useRef<HTMLInputElement>(null);
    const [avatarPreview, setAvatarPreview] = useState<string | null>(null);
    const [avatarFile, setAvatarFile] = useState<File | null>(null);
    const [avatarUploading, setAvatarUploading] = useState(false);
    const [avatarError, setAvatarError] = useState('');
    const [avatarSuccess, setAvatarSuccess] = useState(false);

    const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            setAvatarError('Ukuran gambar maksimal 2MB.');
            return;
        }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            setAvatarError('Format yang didukung: JPEG, PNG, WebP.');
            return;
        }
        setAvatarError('');
        setAvatarFile(file);
        // Revoke URL lama untuk mencegah memory leak
        setAvatarPreview(prev => {
            if (prev) URL.revokeObjectURL(prev);
            return URL.createObjectURL(file);
        });
    };

    const submitAvatar = async () => {
        if (!avatarFile) return;
        setAvatarUploading(true);
        setAvatarError('');
        const formData = new FormData();
        formData.append('avatar', avatarFile);
        formData.append('_method', 'POST');
        try {
            await axios.post('/dashboard/settings/avatar', formData, {
                headers: { 'Content-Type': 'multipart/form-data', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
            });
            setAvatarSuccess(true);
            setAvatarFile(null);
            setAvatarPreview(null);
            setTimeout(() => setAvatarSuccess(false), 3000);
            router.reload({ only: ['auth'] });
        } catch (e: any) {
            setAvatarError(e.response?.data?.errors?.avatar?.[0] ?? 'Gagal mengunggah foto.');
        } finally {
            setAvatarUploading(false);
        }
    };

    const removeAvatar = async () => {
        setAvatarUploading(true);
        try {
            await axios.delete('/dashboard/settings/avatar', {
                headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
            });
            setAvatarPreview(null);
            setAvatarFile(null);
            setAvatarSuccess(true);
            setTimeout(() => setAvatarSuccess(false), 3000);
            router.reload({ only: ['auth'] });
        } catch {
            setAvatarError('Gagal menghapus foto.');
        } finally {
            setAvatarUploading(false);
        }
    };

    // Form for Profile Information (Assuming Fortify default route for profile updates is 'user-profile-information.update')
    // We will just scaffold the UI for now, logic can be wired later depending on how Fortify is truly set up for the user model.
    const {
        data: profileData,
        setData: setProfileData,
        put: updateProfile,
        processing: profileProcessing,
        errors: profileErrors,
        recentlySuccessful: profileSuccessful,
    } = useForm({
        name: user?.name || '',
        username: user?.username || '', // Assuming a username field exists, or we might need to fallback to something
        email: user?.email || '',
        phone: user?.phone || '', // Assuming phone exists
    });

    // Form for Password Update (Using Fortify's typical 'user-password.update' route path)
    const {
        data: pwdData,
        setData: setPwdData,
        put: updatePassword,
        processing: pwdProcessing,
        errors: pwdErrors,
        reset: resetPwd,
        recentlySuccessful: pwdSuccessful,
    } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    // 2FA state
    type TwoFaStep =
        | 'idle'
        | 'enabling-confirm'
        | 'setup'
        | 'recovery'
        | 'disabling-confirm';
    const [twoFaStep, setTwoFaStep] = useState<TwoFaStep>('idle');
    const [twoFaPassword, setTwoFaPassword] = useState('');
    const [qrSvg, setQrSvg] = useState('');
    const [secretKey, setSecretKey] = useState('');
    const [otpCode, setOtpCode] = useState('');
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
    const [twoFaLoading, setTwoFaLoading] = useState(false);
    const [twoFaError, setTwoFaError] = useState('');
    const twoFactorEnabled = !!user?.two_factor_confirmed_at;
    const appName = ((usePage().props as any).name ?? 'Krysta').replace(/[<>&"]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[c] ?? c));

    const enableTwoFactor = async () => {
        setTwoFaLoading(true);
        setTwoFaError('');
        try {
            await axios.post('/user/confirm-password', {
                password: twoFaPassword,
            });
            await axios.post('/user/two-factor-authentication');
            const [qrRes, secretRes] = await Promise.all([
                axios.get('/user/two-factor-qr-code'),
                axios.get('/user/two-factor-secret-key'),
            ]);
            setQrSvg(qrRes.data.svg);
            setSecretKey(secretRes.data.secretKey);
            setTwoFaPassword('');
            setTwoFaStep('setup');
        } catch (e: any) {
            setTwoFaError(
                e.response?.data?.errors?.password?.[0] ?? 'Terjadi kesalahan.',
            );
        } finally {
            setTwoFaLoading(false);
        }
    };

    const confirmTwoFactor = async () => {
        setTwoFaLoading(true);
        setTwoFaError('');
        try {
            await axios.post('/user/confirmed-two-factor-authentication', {
                code: otpCode,
            });
            const codesRes = await axios.get('/user/two-factor-recovery-codes');
            setRecoveryCodes(codesRes.data);
            setOtpCode('');
            setTwoFaStep('recovery');
            router.reload({ only: ['auth'] });
        } catch (e: any) {
            setTwoFaError(
                e.response?.data?.errors?.code?.[0] ?? 'Kode tidak valid.',
            );
        } finally {
            setTwoFaLoading(false);
        }
    };

    const downloadRecoveryCodes = () => {
        const html = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recovery Codes - ${appName}</title>
    <style>
        body { font-family: 'Courier New', monospace; padding: 48px; color: #111; }
        h2 { font-size: 20px; margin-bottom: 6px; }
        .subtitle { color: #555; font-size: 13px; margin-bottom: 8px; }
        .warning { background: #fffbeb; border: 1px solid #fbbf24; border-radius: 8px; padding: 12px 16px; font-size: 12px; color: #92400e; margin-bottom: 24px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-width: 380px; }
        code { border: 1px solid #d1d5db; background: #f9fafb; padding: 8px 12px; border-radius: 6px; text-align: center; letter-spacing: 2px; font-size: 13px; }
        .footer { margin-top: 32px; font-size: 11px; color: #9ca3af; }
    </style>
</head>
<body>
    <h2>Recovery Codes — ${appName}</h2>
    <p class="subtitle">Dibuat pada: ${new Date().toLocaleString('id-ID')}</p>
    <div class="warning">Simpan kode ini di tempat yang aman. Setiap kode hanya bisa digunakan sekali. Gunakan jika kamu tidak bisa mengakses aplikasi authenticator.</div>
    <div class="grid">${recoveryCodes.map((code) => `<code>${code}</code>`).join('')}</div>
    <p class="footer">${appName} · Jangan bagikan kode ini kepada siapapun.</p>
</body>
</html>`;
        const blob = new Blob([html], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        const win = window.open(url, '_blank');
        if (win) {
            win.addEventListener('load', () => {
                win.print();
                URL.revokeObjectURL(url);
            });
        }
    };

    const disableTwoFactor = async () => {
        setTwoFaLoading(true);
        setTwoFaError('');
        try {
            await axios.post('/user/confirm-password', {
                password: twoFaPassword,
            });
            await axios.delete('/user/two-factor-authentication');
            setTwoFaPassword('');
            setTwoFaStep('idle');
            router.reload({ only: ['auth'] });
        } catch (e: any) {
            setTwoFaError(
                e.response?.data?.errors?.password?.[0] ?? 'Terjadi kesalahan.',
            );
        } finally {
            setTwoFaLoading(false);
        }
    };

    const submitProfile = (e: React.FormEvent) => {
        e.preventDefault();
        updateProfile('/user/profile-information', {
            preserveScroll: true,
        });
    };

    const submitPassword = (e: React.FormEvent) => {
        e.preventDefault();
        updatePassword('/settings/password', {
            preserveScroll: true,
            onSuccess: () => resetPwd(),
            onError: (errors) => {
                if (errors.password) {
                    resetPwd('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    resetPwd('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <UserLayout title="Pengaturan">
            <h2 className="mb-6 text-2xl font-bold text-white">Profil</h2>

            {/* Avatar Section */}
            <div className="mb-6 rounded-xl border border-[#31334c] bg-[#1e1f29] p-6 md:p-8">
                <p className="mb-4 text-sm font-semibold text-gray-400 uppercase tracking-wide">Foto Profil</p>
                <div className="flex flex-col items-start gap-5 sm:flex-row sm:items-center">
                    {/* Avatar preview */}
                    <div className="relative shrink-0">
                        <div className={`flex h-20 w-20 items-center justify-center overflow-hidden rounded-full ring-2 ${tc.ring} bg-white/10`}>
                            {avatarPreview ? (
                                <img src={avatarPreview} alt="Preview" className="h-full w-full object-cover" />
                            ) : user?.avatar_url ? (
                                <img src={user.avatar_url} alt="Avatar" className="h-full w-full object-cover" />
                            ) : (
                                <span className="text-xl font-bold text-white">{initials}</span>
                            )}
                        </div>
                        {/* Tier badge */}
                        <span className="absolute -bottom-1 -right-1 text-base leading-none">{tc.icon}</span>
                    </div>

                    {/* Controls */}
                    <div className="flex flex-col gap-2">
                        <div className="flex flex-wrap gap-2">
                            <button
                                type="button"
                                onClick={() => avatarInputRef.current?.click()}
                                className="rounded-lg border border-[#31334c] bg-white/5 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10"
                            >
                                {user?.avatar_url || avatarPreview ? 'Ganti Foto' : 'Unggah Foto'}
                            </button>
                            {(user?.avatar_url && !avatarPreview) && (
                                <button
                                    type="button"
                                    onClick={removeAvatar}
                                    disabled={avatarUploading}
                                    className="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-2 text-sm font-medium text-red-400 transition hover:bg-red-500/20 disabled:opacity-50"
                                >
                                    Hapus Foto
                                </button>
                            )}
                            {avatarFile && (
                                <button
                                    type="button"
                                    onClick={submitAvatar}
                                    disabled={avatarUploading}
                                    className="rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] px-4 py-2 text-sm font-bold text-white transition hover:opacity-90 disabled:opacity-50"
                                >
                                    {avatarUploading ? 'Mengunggah...' : 'Simpan Foto'}
                                </button>
                            )}
                            {avatarPreview && !avatarFile && (
                                <button
                                    type="button"
                                    onClick={() => { if (avatarPreview) URL.revokeObjectURL(avatarPreview); setAvatarPreview(null); setAvatarFile(null); }}
                                    className="text-sm text-gray-500 hover:text-gray-300"
                                >
                                    Batal
                                </button>
                            )}
                        </div>
                        <p className="text-xs text-gray-500">Format: JPEG, PNG, WebP · Maks. 2MB</p>
                        {avatarError && <p className="text-xs text-red-400">{avatarError}</p>}
                        {avatarSuccess && <p className="text-xs text-green-400">Foto profil berhasil diperbarui.</p>}
                    </div>

                    <input
                        ref={avatarInputRef}
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        className="hidden"
                        onChange={handleAvatarChange}
                    />
                </div>
            </div>

            {/* Profil Form Section */}
            <div className="mb-8 rounded-xl border border-[#31334c] bg-[#1e1f29] p-6 md:p-8">
                <form onSubmit={submitProfile} className="flex flex-col gap-6">
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        {/* Nama */}
                        <div>
                            <label
                                htmlFor="name"
                                className="mb-2 block text-sm font-bold text-white"
                            >
                                Nama
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={profileData.name}
                                onChange={(e) =>
                                    setProfileData('name', e.target.value)
                                }
                                className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-3 text-sm text-white transition outline-none focus:border-primary focus:ring-primary"
                                placeholder="Ferry Oktariansyah"
                            />
                            {profileErrors.name && (
                                <p className="mt-1 text-sm text-red-500">
                                    {profileErrors.name}
                                </p>
                            )}
                        </div>

                        {/* Username */}
                        <div>
                            <label
                                htmlFor="username"
                                className="mb-2 block text-sm font-bold text-white"
                            >
                                Username
                            </label>
                            <input
                                id="username"
                                type="text"
                                value={profileData.username}
                                onChange={(e) =>
                                    setProfileData('username', e.target.value)
                                }
                                className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-3 text-sm text-white transition outline-none focus:border-primary focus:ring-primary"
                                placeholder="Budi"
                            />
                            {profileErrors.username && (
                                <p className="mt-1 text-sm text-red-500">
                                    {profileErrors.username}
                                </p>
                            )}
                        </div>

                        {/* Alamat Email */}
                        <div>
                            <label
                                htmlFor="email"
                                className="mb-2 block text-sm font-bold text-white"
                            >
                                Alamat Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={profileData.email}
                                onChange={(e) =>
                                    setProfileData('email', e.target.value)
                                }
                                className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-3 text-sm text-white transition outline-none focus:border-primary focus:ring-primary"
                                placeholder="blaskishare@gmail.com"
                            />
                            {profileErrors.email && (
                                <p className="mt-1 text-sm text-red-500">
                                    {profileErrors.email}
                                </p>
                            )}
                        </div>

                        {/* No. Handphone */}
                        <div>
                            <label
                                htmlFor="phone"
                                className="mb-2 block text-sm font-bold text-white"
                            >
                                No. Handphone
                            </label>
                            <input
                                id="phone"
                                type="text"
                                value={profileData.phone}
                                onChange={(e) =>
                                    setProfileData('phone', e.target.value)
                                }
                                className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-3 text-sm text-white transition outline-none focus:border-primary focus:ring-primary"
                                placeholder="08xxxxxxxxxx"
                            />
                            {profileErrors.phone && (
                                <p className="mt-1 text-sm text-red-500">
                                    {profileErrors.phone}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-2 flex items-center justify-end gap-4">
                        {profileSuccessful && (
                            <p className="text-sm font-medium text-green-500">
                                Berhasil disimpan.
                            </p>
                        )}
                        <button
                            type="submit"
                            disabled={profileProcessing}
                            className="min-w-[140px] rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] px-6 py-2.5 font-bold text-white transition hover:opacity-90 disabled:opacity-50"
                        >
                            {profileProcessing ? 'Menyimpan...' : 'Ubah Profil'}
                        </button>
                    </div>
                </form>
            </div>

            {user?.google_id ? (
                <div className="mb-8 rounded-xl border border-[#31334c] bg-[#1e1f29] p-6 md:p-8">
                    <div className="flex items-center gap-3">
                        <svg
                            viewBox="0 0 24 24"
                            className="h-5 w-5 shrink-0"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                fill="#4285F4"
                            />
                            <path
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                fill="#34A853"
                            />
                            <path
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"
                                fill="#FBBC05"
                            />
                            <path
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                fill="#EA4335"
                            />
                        </svg>
                        <p className="text-sm text-gray-400">
                            Akun kamu terhubung melalui Google. Kata sandi
                            dikelola oleh Google.
                        </p>
                    </div>
                </div>
            ) : (
                <>
                    <h2 className="mb-6 text-2xl font-bold text-white">
                        Ubah Kata Sandi
                    </h2>

                    {/* Ubah Kata Sandi Form Section */}
                    <div className="mb-8 rounded-xl border border-[#31334c] bg-[#1e1f29] p-6 md:p-8">
                        <form
                            onSubmit={submitPassword}
                            className="flex flex-col gap-6"
                        >
                            {/* Kata Sandi Saat Ini */}
                            <div className="w-full">
                                <label
                                    htmlFor="current_password"
                                    className="mb-2 block text-sm font-bold text-white"
                                >
                                    Kata Sandi Saat Ini
                                </label>
                                <input
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    type="password"
                                    value={pwdData.current_password}
                                    onChange={(e) =>
                                        setPwdData(
                                            'current_password',
                                            e.target.value,
                                        )
                                    }
                                    className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-3 text-sm text-white transition outline-none focus:border-primary focus:ring-primary"
                                    placeholder="Masukkan kata sandi saat ini"
                                />
                                {pwdErrors.current_password && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {pwdErrors.current_password}
                                    </p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Kata Sandi Baru */}
                                <div>
                                    <label
                                        htmlFor="password"
                                        className="mb-2 block text-sm font-bold text-white"
                                    >
                                        Kata Sandi Baru
                                    </label>
                                    <input
                                        id="password"
                                        ref={passwordInput}
                                        type="password"
                                        value={pwdData.password}
                                        onChange={(e) =>
                                            setPwdData(
                                                'password',
                                                e.target.value,
                                            )
                                        }
                                        className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-3 text-sm text-white transition outline-none focus:border-primary focus:ring-primary"
                                        placeholder="Masukkan kata sandi baru"
                                    />
                                    {pwdErrors.password && (
                                        <p className="mt-1 text-sm text-red-500">
                                            {pwdErrors.password}
                                        </p>
                                    )}
                                </div>

                                {/* Konfirmasi Kata Sandi Baru */}
                                <div>
                                    <label
                                        htmlFor="password_confirmation"
                                        className="mb-2 block text-sm font-bold text-white"
                                    >
                                        Konfirmasi Kata Sandi Baru
                                    </label>
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        value={pwdData.password_confirmation}
                                        onChange={(e) =>
                                            setPwdData(
                                                'password_confirmation',
                                                e.target.value,
                                            )
                                        }
                                        className="block w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-3 text-sm text-white transition outline-none focus:border-primary focus:ring-primary"
                                        placeholder="Masukkan konfirmasi kata sandi baru"
                                    />
                                    {pwdErrors.password_confirmation && (
                                        <p className="mt-1 text-sm text-red-500">
                                            {pwdErrors.password_confirmation}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="mt-2 flex items-center justify-end gap-4">
                                {pwdSuccessful && (
                                    <p className="text-sm font-medium text-green-500">
                                        Sandi diperbarui.
                                    </p>
                                )}
                                <button
                                    type="submit"
                                    disabled={pwdProcessing}
                                    className="min-w-[140px] rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] px-6 py-2.5 font-bold text-white transition hover:opacity-90 disabled:opacity-50"
                                >
                                    {pwdProcessing
                                        ? 'Menyimpan...'
                                        : 'Ubah Sandi'}
                                </button>
                            </div>
                        </form>
                    </div>

                    <h2 className="mb-6 text-2xl font-bold text-white">
                        Autentikasi Dua Faktor (2FA)
                    </h2>

                    <div className="mb-8 rounded-xl border border-[#31334c] bg-[#1e1f29] p-6 md:p-8">
                        {/* Status badge */}
                        <div className="mb-6 flex items-center gap-3">
                            <span
                                className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ${twoFactorEnabled ? 'bg-green-500/20 text-green-400' : 'bg-zinc-700/50 text-zinc-400'}`}
                            >
                                <span
                                    className={`h-1.5 w-1.5 rounded-full ${twoFactorEnabled ? 'bg-green-400' : 'bg-zinc-500'}`}
                                />
                                {twoFactorEnabled ? 'Aktif' : 'Tidak Aktif'}
                            </span>
                            <p className="text-sm text-zinc-400">
                                {twoFactorEnabled
                                    ? 'Akun kamu dilindungi dengan autentikasi dua faktor.'
                                    : 'Tambahkan lapisan keamanan ekstra menggunakan aplikasi authenticator.'}
                            </p>
                        </div>

                        {/* Idle — not enabled */}
                        {!twoFactorEnabled && twoFaStep === 'idle' && (
                            <button
                                onClick={() => {
                                    setTwoFaError('');
                                    setTwoFaStep('enabling-confirm');
                                }}
                                className="rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] px-6 py-2.5 font-bold text-white transition hover:opacity-90"
                            >
                                Aktifkan 2FA
                            </button>
                        )}

                        {/* Idle — already enabled */}
                        {twoFactorEnabled && twoFaStep === 'idle' && (
                            <button
                                onClick={() => {
                                    setTwoFaError('');
                                    setTwoFaStep('disabling-confirm');
                                }}
                                className="rounded-lg bg-red-600 px-6 py-2.5 font-bold text-white transition hover:bg-red-700"
                            >
                                Nonaktifkan 2FA
                            </button>
                        )}

                        {/* Step: confirm password before enabling */}
                        {twoFaStep === 'enabling-confirm' && (
                            <div className="flex max-w-sm flex-col gap-4">
                                <p className="text-sm text-zinc-400">
                                    Masukkan kata sandi kamu untuk melanjutkan.
                                </p>
                                <input
                                    type="password"
                                    value={twoFaPassword}
                                    onChange={(e) =>
                                        setTwoFaPassword(e.target.value)
                                    }
                                    onKeyDown={(e) =>
                                        e.key === 'Enter' && enableTwoFactor()
                                    }
                                    placeholder="Kata sandi"
                                    className="w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-3 text-sm text-white transition outline-none focus:border-primary focus:ring-primary"
                                />
                                {twoFaError && (
                                    <p className="text-sm text-red-500">
                                        {twoFaError}
                                    </p>
                                )}
                                <div className="flex gap-3">
                                    <button
                                        onClick={enableTwoFactor}
                                        disabled={
                                            twoFaLoading || !twoFaPassword
                                        }
                                        className="rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] px-6 py-2.5 font-bold text-white transition hover:opacity-90 disabled:opacity-50"
                                    >
                                        {twoFaLoading
                                            ? 'Memproses...'
                                            : 'Lanjutkan'}
                                    </button>
                                    <button
                                        onClick={() => {
                                            setTwoFaStep('idle');
                                            setTwoFaPassword('');
                                            setTwoFaError('');
                                        }}
                                        className="text-sm text-zinc-400 transition hover:text-white"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* Step: scan QR & enter OTP */}
                        {twoFaStep === 'setup' && (
                            <div className="flex flex-col gap-5">
                                <p className="text-sm text-zinc-400">
                                    Scan QR code berikut menggunakan aplikasi
                                    authenticator (Google Authenticator, Authy,
                                    dll).
                                </p>
                                <div
                                    className="w-fit rounded-xl bg-white p-3"
                                    dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(qrSvg, { USE_PROFILES: { svg: true, svgFilters: true } }) }}
                                />
                                {secretKey && (
                                    <div>
                                        <p className="mb-1 text-xs text-zinc-500">
                                            Atau masukkan kode manual:
                                        </p>
                                        <code className="block rounded-lg border border-[#31334c] bg-[#1A1A24] px-3 py-2 text-sm tracking-widest text-zinc-300">
                                            {secretKey}
                                        </code>
                                    </div>
                                )}
                                <div className="flex max-w-sm flex-col gap-2">
                                    <label className="text-sm font-bold text-white">
                                        Kode OTP
                                    </label>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        maxLength={6}
                                        value={otpCode}
                                        onChange={(e) =>
                                            setOtpCode(
                                                e.target.value.replace(
                                                    /\D/g,
                                                    '',
                                                ),
                                            )
                                        }
                                        onKeyDown={(e) =>
                                            e.key === 'Enter' &&
                                            confirmTwoFactor()
                                        }
                                        placeholder="000000"
                                        className="w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-3 text-sm tracking-widest text-white transition outline-none focus:border-primary focus:ring-primary"
                                    />
                                    {twoFaError && (
                                        <p className="text-sm text-red-500">
                                            {twoFaError}
                                        </p>
                                    )}
                                    <button
                                        onClick={confirmTwoFactor}
                                        disabled={
                                            twoFaLoading || otpCode.length !== 6
                                        }
                                        className="w-fit rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] px-6 py-2.5 font-bold text-white transition hover:opacity-90 disabled:opacity-50"
                                    >
                                        {twoFaLoading
                                            ? 'Memverifikasi...'
                                            : 'Konfirmasi'}
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* Step: show recovery codes */}
                        {twoFaStep === 'recovery' && (
                            <div className="flex flex-col gap-4">
                                <div className="rounded-lg border border-yellow-500/30 bg-yellow-500/10 p-4">
                                    <p className="mb-1 text-sm font-semibold text-yellow-400">
                                        Simpan kode recovery ini!
                                    </p>
                                    <p className="text-xs text-zinc-400">
                                        Kode ini hanya ditampilkan sekali.
                                        Gunakan jika kamu tidak bisa mengakses
                                        aplikasi authenticator.
                                    </p>
                                </div>
                                <div className="grid max-w-sm grid-cols-2 gap-2">
                                    {recoveryCodes.map((code) => (
                                        <code
                                            key={code}
                                            className="rounded-lg border border-[#31334c] bg-[#1A1A24] px-3 py-2 text-center text-sm tracking-widest text-zinc-300"
                                        >
                                            {code}
                                        </code>
                                    ))}
                                </div>
                                <div className="flex gap-3">
                                    <button
                                        onClick={downloadRecoveryCodes}
                                        className="w-fit rounded-lg bg-zinc-700 px-6 py-2.5 font-bold text-white transition hover:bg-zinc-600"
                                    >
                                        Unduh PDF
                                    </button>
                                    <button
                                        onClick={() => setTwoFaStep('idle')}
                                        className="w-fit rounded-lg bg-gradient-to-r from-primary to-[#9b4dec] px-6 py-2.5 font-bold text-white transition hover:opacity-90"
                                    >
                                        Selesai
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* Step: confirm password before disabling */}
                        {twoFaStep === 'disabling-confirm' && (
                            <div className="flex max-w-sm flex-col gap-4">
                                <p className="text-sm text-zinc-400">
                                    Masukkan kata sandi kamu untuk menonaktifkan
                                    2FA.
                                </p>
                                <input
                                    type="password"
                                    value={twoFaPassword}
                                    onChange={(e) =>
                                        setTwoFaPassword(e.target.value)
                                    }
                                    onKeyDown={(e) =>
                                        e.key === 'Enter' && disableTwoFactor()
                                    }
                                    placeholder="Kata sandi"
                                    className="w-full rounded-lg border border-[#31334c] bg-[#1A1A24] p-3 text-sm text-white transition outline-none focus:border-primary focus:ring-primary"
                                />
                                {twoFaError && (
                                    <p className="text-sm text-red-500">
                                        {twoFaError}
                                    </p>
                                )}
                                <div className="flex gap-3">
                                    <button
                                        onClick={disableTwoFactor}
                                        disabled={
                                            twoFaLoading || !twoFaPassword
                                        }
                                        className="rounded-lg bg-red-600 px-6 py-2.5 font-bold text-white transition hover:bg-red-700 disabled:opacity-50"
                                    >
                                        {twoFaLoading
                                            ? 'Memproses...'
                                            : 'Nonaktifkan'}
                                    </button>
                                    <button
                                        onClick={() => {
                                            setTwoFaStep('idle');
                                            setTwoFaPassword('');
                                            setTwoFaError('');
                                        }}
                                        className="text-sm text-zinc-400 transition hover:text-white"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </>
            )}
        </UserLayout>
    );
}
