import { useForm, usePage, router } from '@inertiajs/react';
import UserLayout from '@/layouts/user-layout';
import React, { useRef, useState } from 'react';
import axios from 'axios';

export default function Settings() {
    const { auth } = usePage().props as any;
    const user = auth?.user;

    // Form for Profile Information (Assuming Fortify default route for profile updates is 'user-profile-information.update')
    // We will just scaffold the UI for now, logic can be wired later depending on how Fortify is truly set up for the user model.
    const { data: profileData, setData: setProfileData, put: updateProfile, processing: profileProcessing, errors: profileErrors, recentlySuccessful: profileSuccessful } = useForm({
        name: user?.name || '',
        username: user?.username || '', // Assuming a username field exists, or we might need to fallback to something
        email: user?.email || '',
        phone: user?.phone || '', // Assuming phone exists
    });

    // Form for Password Update (Using Fortify's typical 'user-password.update' route path)
    const { data: pwdData, setData: setPwdData, put: updatePassword, processing: pwdProcessing, errors: pwdErrors, reset: resetPwd, recentlySuccessful: pwdSuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    // 2FA state
    type TwoFaStep = 'idle' | 'enabling-confirm' | 'setup' | 'recovery' | 'disabling-confirm';
    const [twoFaStep, setTwoFaStep] = useState<TwoFaStep>('idle');
    const [twoFaPassword, setTwoFaPassword] = useState('');
    const [qrSvg, setQrSvg] = useState('');
    const [secretKey, setSecretKey] = useState('');
    const [otpCode, setOtpCode] = useState('');
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
    const [twoFaLoading, setTwoFaLoading] = useState(false);
    const [twoFaError, setTwoFaError] = useState('');
    const twoFactorEnabled = !!user?.two_factor_confirmed_at;
    const appName = (usePage().props as any).name ?? 'Krysta';

    const enableTwoFactor = async () => {
        setTwoFaLoading(true);
        setTwoFaError('');
        try {
            await axios.post('/user/confirm-password', { password: twoFaPassword });
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
            setTwoFaError(e.response?.data?.errors?.password?.[0] ?? 'Terjadi kesalahan.');
        } finally {
            setTwoFaLoading(false);
        }
    };

    const confirmTwoFactor = async () => {
        setTwoFaLoading(true);
        setTwoFaError('');
        try {
            await axios.post('/user/confirmed-two-factor-authentication', { code: otpCode });
            const codesRes = await axios.get('/user/two-factor-recovery-codes');
            setRecoveryCodes(codesRes.data);
            setOtpCode('');
            setTwoFaStep('recovery');
            router.reload({ only: ['auth'] });
        } catch (e: any) {
            setTwoFaError(e.response?.data?.errors?.code?.[0] ?? 'Kode tidak valid.');
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
    <div class="grid">${recoveryCodes.map(code => `<code>${code}</code>`).join('')}</div>
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
            await axios.post('/user/confirm-password', { password: twoFaPassword });
            await axios.delete('/user/two-factor-authentication');
            setTwoFaPassword('');
            setTwoFaStep('idle');
            router.reload({ only: ['auth'] });
        } catch (e: any) {
            setTwoFaError(e.response?.data?.errors?.password?.[0] ?? 'Terjadi kesalahan.');
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
            <h2 className="text-2xl font-bold text-white mb-6">Profil</h2>
            
            {/* Profil Form Section */}
            <div className="bg-[#1e1f29] rounded-xl border border-[#31334c] p-6 md:p-8 mb-8">
                <form onSubmit={submitProfile} className="flex flex-col gap-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Nama */}
                        <div>
                            <label htmlFor="name" className="block text-sm font-bold text-white mb-2">Nama</label>
                            <input
                                id="name"
                                type="text"
                                value={profileData.name}
                                onChange={(e) => setProfileData('name', e.target.value)}
                                className="w-full bg-[#1A1A24] text-white border border-[#31334c] text-sm rounded-lg focus:ring-primary focus:border-primary block p-3 outline-none transition"
                                placeholder="Ferry Oktariansyah"
                            />
                            {profileErrors.name && <p className="mt-1 text-sm text-red-500">{profileErrors.name}</p>}
                        </div>

                        {/* Username */}
                        <div>
                            <label htmlFor="username" className="block text-sm font-bold text-white mb-2">Username</label>
                            <input
                                id="username"
                                type="text"
                                value={profileData.username}
                                onChange={(e) => setProfileData('username', e.target.value)}
                                className="w-full bg-[#1A1A24] text-white border border-[#31334c] text-sm rounded-lg focus:ring-primary focus:border-primary block p-3 outline-none transition"
                                placeholder="Resyus"
                            />
                            {profileErrors.username && <p className="mt-1 text-sm text-red-500">{profileErrors.username}</p>}
                        </div>

                        {/* Alamat Email */}
                        <div>
                            <label htmlFor="email" className="block text-sm font-bold text-white mb-2">Alamat Email</label>
                            <input
                                id="email"
                                type="email"
                                value={profileData.email}
                                onChange={(e) => setProfileData('email', e.target.value)}
                                className="w-full bg-[#1A1A24] text-white border border-[#31334c] text-sm rounded-lg focus:ring-primary focus:border-primary block p-3 outline-none transition"
                                placeholder="blaskishare@gmail.com"
                            />
                            {profileErrors.email && <p className="mt-1 text-sm text-red-500">{profileErrors.email}</p>}
                        </div>

                        {/* No. Handphone */}
                        <div>
                            <label htmlFor="phone" className="block text-sm font-bold text-white mb-2">No. Handphone</label>
                            <input
                                id="phone"
                                type="text"
                                value={profileData.phone}
                                onChange={(e) => setProfileData('phone', e.target.value)}
                                className="w-full bg-[#1A1A24] text-white border border-[#31334c] text-sm rounded-lg focus:ring-primary focus:border-primary block p-3 outline-none transition"
                                placeholder="+62 898 3120 199"
                            />
                            {profileErrors.phone && <p className="mt-1 text-sm text-red-500">{profileErrors.phone}</p>}
                        </div>
                    </div>
                    
                    <div className="flex justify-end mt-2 items-center gap-4">
                        {profileSuccessful && <p className="text-sm text-green-500 font-medium">Berhasil disimpan.</p>}
                        <button 
                            type="submit" 
                            disabled={profileProcessing}
                            className="bg-gradient-to-r from-primary to-[#9b4dec] text-white font-bold py-2.5 px-6 rounded-lg hover:opacity-90 transition disabled:opacity-50 min-w-[140px]"
                        >
                            {profileProcessing ? 'Menyimpan...' : 'Ubah Profil'}
                        </button>
                    </div>
                </form>
            </div>

            <h2 className="text-2xl font-bold text-white mb-6">Ubah Kata Sandi</h2>

            {/* Ubah Kata Sandi Form Section */}
            <div className="bg-[#1e1f29] rounded-xl border border-[#31334c] p-6 md:p-8 mb-8">
                <form onSubmit={submitPassword} className="flex flex-col gap-6">
                    {/* Kata Sandi Saat Ini */}
                    <div className="w-full">
                        <label htmlFor="current_password" className="block text-sm font-bold text-white mb-2">Kata Sandi Saat Ini</label>
                        <input
                            id="current_password"
                            ref={currentPasswordInput}
                            type="password"
                            value={pwdData.current_password}
                            onChange={(e) => setPwdData('current_password', e.target.value)}
                            className="w-full bg-[#1A1A24] text-white border border-[#31334c] text-sm rounded-lg focus:ring-primary focus:border-primary block p-3 outline-none transition"
                            placeholder="Masukkan kata sandi saat ini"
                        />
                        {pwdErrors.current_password && <p className="mt-1 text-sm text-red-500">{pwdErrors.current_password}</p>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Kata Sandi Baru */}
                        <div>
                            <label htmlFor="password" className="block text-sm font-bold text-white mb-2">Kata Sandi Baru</label>
                            <input
                                id="password"
                                ref={passwordInput}
                                type="password"
                                value={pwdData.password}
                                onChange={(e) => setPwdData('password', e.target.value)}
                                className="w-full bg-[#1A1A24] text-white border border-[#31334c] text-sm rounded-lg focus:ring-primary focus:border-primary block p-3 outline-none transition"
                                placeholder="Masukkan kata sandi baru"
                            />
                            {pwdErrors.password && <p className="mt-1 text-sm text-red-500">{pwdErrors.password}</p>}
                        </div>

                        {/* Konfirmasi Kata Sandi Baru */}
                        <div>
                            <label htmlFor="password_confirmation" className="block text-sm font-bold text-white mb-2">Konfirmasi Kata Sandi Baru</label>
                            <input
                                id="password_confirmation"
                                type="password"
                                value={pwdData.password_confirmation}
                                onChange={(e) => setPwdData('password_confirmation', e.target.value)}
                                className="w-full bg-[#1A1A24] text-white border border-[#31334c] text-sm rounded-lg focus:ring-primary focus:border-primary block p-3 outline-none transition"
                                placeholder="Masukkan konfirmasi kata sandi baru"
                            />
                            {pwdErrors.password_confirmation && <p className="mt-1 text-sm text-red-500">{pwdErrors.password_confirmation}</p>}
                        </div>
                    </div>

                    <div className="flex justify-end mt-2 items-center gap-4">
                        {pwdSuccessful && <p className="text-sm text-green-500 font-medium">Sandi diperbarui.</p>}
                        <button 
                            type="submit" 
                            disabled={pwdProcessing}
                            className="bg-gradient-to-r from-primary to-[#9b4dec] text-white font-bold py-2.5 px-6 rounded-lg hover:opacity-90 transition disabled:opacity-50 min-w-[140px]"
                        >
                            {pwdProcessing ? 'Menyimpan...' : 'Ubah Sandi'}
                        </button>
                    </div>
                </form>
            </div>

            <h2 className="text-2xl font-bold text-white mb-6">Autentikasi Dua Faktor (2FA)</h2>

            <div className="bg-[#1e1f29] rounded-xl border border-[#31334c] p-6 md:p-8 mb-8">
                {/* Status badge */}
                <div className="flex items-center gap-3 mb-6">
                    <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ${twoFactorEnabled ? 'bg-green-500/20 text-green-400' : 'bg-zinc-700/50 text-zinc-400'}`}>
                        <span className={`w-1.5 h-1.5 rounded-full ${twoFactorEnabled ? 'bg-green-400' : 'bg-zinc-500'}`} />
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
                        onClick={() => { setTwoFaError(''); setTwoFaStep('enabling-confirm'); }}
                        className="bg-gradient-to-r from-primary to-[#9b4dec] text-white font-bold py-2.5 px-6 rounded-lg hover:opacity-90 transition"
                    >
                        Aktifkan 2FA
                    </button>
                )}

                {/* Idle — already enabled */}
                {twoFactorEnabled && twoFaStep === 'idle' && (
                    <button
                        onClick={() => { setTwoFaError(''); setTwoFaStep('disabling-confirm'); }}
                        className="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-6 rounded-lg transition"
                    >
                        Nonaktifkan 2FA
                    </button>
                )}

                {/* Step: confirm password before enabling */}
                {twoFaStep === 'enabling-confirm' && (
                    <div className="flex flex-col gap-4 max-w-sm">
                        <p className="text-sm text-zinc-400">Masukkan kata sandi kamu untuk melanjutkan.</p>
                        <input
                            type="password"
                            value={twoFaPassword}
                            onChange={(e) => setTwoFaPassword(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && enableTwoFactor()}
                            placeholder="Kata sandi"
                            className="w-full bg-[#1A1A24] text-white border border-[#31334c] text-sm rounded-lg focus:ring-primary focus:border-primary p-3 outline-none transition"
                        />
                        {twoFaError && <p className="text-sm text-red-500">{twoFaError}</p>}
                        <div className="flex gap-3">
                            <button
                                onClick={enableTwoFactor}
                                disabled={twoFaLoading || !twoFaPassword}
                                className="bg-gradient-to-r from-primary to-[#9b4dec] text-white font-bold py-2.5 px-6 rounded-lg hover:opacity-90 transition disabled:opacity-50"
                            >
                                {twoFaLoading ? 'Memproses...' : 'Lanjutkan'}
                            </button>
                            <button onClick={() => { setTwoFaStep('idle'); setTwoFaPassword(''); setTwoFaError(''); }} className="text-zinc-400 hover:text-white text-sm transition">
                                Batal
                            </button>
                        </div>
                    </div>
                )}

                {/* Step: scan QR & enter OTP */}
                {twoFaStep === 'setup' && (
                    <div className="flex flex-col gap-5">
                        <p className="text-sm text-zinc-400">Scan QR code berikut menggunakan aplikasi authenticator (Google Authenticator, Authy, dll).</p>
                        <div
                            className="bg-white p-3 rounded-xl w-fit"
                            dangerouslySetInnerHTML={{ __html: qrSvg }}
                        />
                        {secretKey && (
                            <div>
                                <p className="text-xs text-zinc-500 mb-1">Atau masukkan kode manual:</p>
                                <code className="text-sm bg-[#1A1A24] text-zinc-300 border border-[#31334c] px-3 py-2 rounded-lg block tracking-widest">{secretKey}</code>
                            </div>
                        )}
                        <div className="flex flex-col gap-2 max-w-sm">
                            <label className="text-sm font-bold text-white">Kode OTP</label>
                            <input
                                type="text"
                                inputMode="numeric"
                                maxLength={6}
                                value={otpCode}
                                onChange={(e) => setOtpCode(e.target.value.replace(/\D/g, ''))}
                                onKeyDown={(e) => e.key === 'Enter' && confirmTwoFactor()}
                                placeholder="000000"
                                className="w-full bg-[#1A1A24] text-white border border-[#31334c] text-sm rounded-lg focus:ring-primary focus:border-primary p-3 outline-none transition tracking-widest"
                            />
                            {twoFaError && <p className="text-sm text-red-500">{twoFaError}</p>}
                            <button
                                onClick={confirmTwoFactor}
                                disabled={twoFaLoading || otpCode.length !== 6}
                                className="bg-gradient-to-r from-primary to-[#9b4dec] text-white font-bold py-2.5 px-6 rounded-lg hover:opacity-90 transition disabled:opacity-50 w-fit"
                            >
                                {twoFaLoading ? 'Memverifikasi...' : 'Konfirmasi'}
                            </button>
                        </div>
                    </div>
                )}

                {/* Step: show recovery codes */}
                {twoFaStep === 'recovery' && (
                    <div className="flex flex-col gap-4">
                        <div className="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
                            <p className="text-sm text-yellow-400 font-semibold mb-1">Simpan kode recovery ini!</p>
                            <p className="text-xs text-zinc-400">Kode ini hanya ditampilkan sekali. Gunakan jika kamu tidak bisa mengakses aplikasi authenticator.</p>
                        </div>
                        <div className="grid grid-cols-2 gap-2 max-w-sm">
                            {recoveryCodes.map((code) => (
                                <code key={code} className="text-sm bg-[#1A1A24] text-zinc-300 border border-[#31334c] px-3 py-2 rounded-lg text-center tracking-widest">
                                    {code}
                                </code>
                            ))}
                        </div>
                        <div className="flex gap-3">
                            <button
                                onClick={downloadRecoveryCodes}
                                className="bg-zinc-700 hover:bg-zinc-600 text-white font-bold py-2.5 px-6 rounded-lg transition w-fit"
                            >
                                Unduh PDF
                            </button>
                            <button
                                onClick={() => setTwoFaStep('idle')}
                                className="bg-gradient-to-r from-primary to-[#9b4dec] text-white font-bold py-2.5 px-6 rounded-lg hover:opacity-90 transition w-fit"
                            >
                                Selesai
                            </button>
                        </div>
                    </div>
                )}

                {/* Step: confirm password before disabling */}
                {twoFaStep === 'disabling-confirm' && (
                    <div className="flex flex-col gap-4 max-w-sm">
                        <p className="text-sm text-zinc-400">Masukkan kata sandi kamu untuk menonaktifkan 2FA.</p>
                        <input
                            type="password"
                            value={twoFaPassword}
                            onChange={(e) => setTwoFaPassword(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && disableTwoFactor()}
                            placeholder="Kata sandi"
                            className="w-full bg-[#1A1A24] text-white border border-[#31334c] text-sm rounded-lg focus:ring-primary focus:border-primary p-3 outline-none transition"
                        />
                        {twoFaError && <p className="text-sm text-red-500">{twoFaError}</p>}
                        <div className="flex gap-3">
                            <button
                                onClick={disableTwoFactor}
                                disabled={twoFaLoading || !twoFaPassword}
                                className="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-6 rounded-lg transition disabled:opacity-50"
                            >
                                {twoFaLoading ? 'Memproses...' : 'Nonaktifkan'}
                            </button>
                            <button onClick={() => { setTwoFaStep('idle'); setTwoFaPassword(''); setTwoFaError(''); }} className="text-zinc-400 hover:text-white text-sm transition">
                                Batal
                            </button>
                        </div>
                    </div>
                )}
            </div>

        </UserLayout>
    );
}
