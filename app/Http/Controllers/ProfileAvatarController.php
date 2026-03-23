<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileAvatarController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ], [
            'avatar.required' => 'Pilih file gambar terlebih dahulu.',
            'avatar.image'    => 'File harus berupa gambar.',
            'avatar.mimes'    => 'Format yang didukung: JPEG, PNG, WebP.',
            'avatar.max'      => 'Ukuran gambar maksimal 2MB.',
        ]);

        $user = $request->user();

        // Hapus avatar lama jika ada
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return back()->with('avatar_success', 'Foto profil berhasil diperbarui.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return back()->with('avatar_success', 'Foto profil berhasil dihapus.');
    }
}
