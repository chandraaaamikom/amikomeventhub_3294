<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Jangan titipkan 'role' ke Auth::attempt(). Nilainya sekarang bisa
        // 'superadmin' atau 'admin' (akun lama), dan attempt() hanya cocok persis.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Email atau Password yang Anda berikan tidak terdaftar di database kami.');
        }

        $user = Auth::user();

        // Superadmin: silakan masuk.
        if ($user->isSuperadmin()) {
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        // Panitia salah pintu — arahkan ke panelnya sendiri, sesinya tetap hidup.
        if ($user->isOrganizer()) {
            $request->session()->regenerate();

            return redirect()->route('organizer.dashboard')
                ->with('success', 'Anda masuk sebagai panitia. Ini panel organisasi Anda.');
        }

        // Pembeli biasa: tolak, dan buang sesinya.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Akun ini tidak memiliki hak akses Superadmin.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}