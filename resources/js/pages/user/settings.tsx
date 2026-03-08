import { useForm, usePage } from '@inertiajs/react';
import UserLayout from '@/layouts/user-layout';
import React, { useRef } from 'react';

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

        </UserLayout>
    );
}
