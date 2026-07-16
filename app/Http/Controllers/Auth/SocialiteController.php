<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Langkah 1: lempar pengguna ke halaman izin Google.
     */
    public function redirect()
    {
        if (blank(config('services.google.client_id'))) {
            return redirect()->route('user.login')
                ->with('error', 'Login Google belum dikonfigurasi. Hubungi administrator.');
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Langkah 2: Google mengembalikan pengguna ke sini beserta kode otorisasi.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth gagal: ' . $e->getMessage());

            return redirect()->route('user.login')
                ->with('error', 'Gagal masuk dengan Google. Silakan coba lagi.');
        }

        if (blank($googleUser->getEmail())) {
            return redirect()->route('user.login')
                ->with('error', 'Akun Google Anda tidak membagikan alamat email.');
        }

        $user = $this->findOrCreateUser($googleUser);

        if ($user->role === User::ROLE_ORGANIZER && ! $user->currentOrganization()) {
            Auth::logout();

            return redirect()->route('user.login')
                ->with('error', 'Akun panitia Anda belum terhubung ke organisasi mana pun.');
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return $this->redirectByRole($user);
    }

    /**
     * Cocokkan berdasarkan google_id dulu, baru email.
     */
    protected function findOrCreateUser($googleUser): User
    {
        // Kasus 1: sudah pernah login via Google.
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            $user->update(['avatar' => $googleUser->getAvatar()]);

            return $user;
        }

        // Kasus 2: emailnya sudah terdaftar (daftar manual dulu, sekarang pakai Google).
        // Aman karena Google sudah memverifikasi kepemilikan email tersebut.
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar(),
            ]);

            return $user;
        }

        // Kasus 3: benar-benar pengguna baru.
        return User::create([
            'name'      => $googleUser->getName() ?: Str::before($googleUser->getEmail(), '@'),
            'email'     => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar'    => $googleUser->getAvatar(),
            'password'  => null,
            'role'      => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);
    }

    protected function redirectByRole(User $user)
    {
        if ($user->isSuperadmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isOrganizer()) {
            return redirect()->route('organizer.dashboard');
        }

        return redirect()->intended(route('user.dashboard'))
            ->with('success', 'Selamat datang, ' . $user->name . '!');
    }
}