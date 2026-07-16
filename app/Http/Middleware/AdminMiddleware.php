<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang Superadmin (pengawas ekosistem global).
 * Alias tetap 'admin' agar routes/web.php yang lama tidak perlu diubah total.
 */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isSuperadmin()) {
            return $next($request);
        }

        // Panitia yang nyasar ke /admin diarahkan ke dashboard-nya sendiri,
        // bukan dilempar ke form login (mereka sudah login).
        if (Auth::check() && Auth::user()->isOrganizer()) {
            return redirect()->route('organizer.dashboard')
                ->with('error', 'Halaman tersebut khusus Superadmin.');
        }

        Auth::guard('web')->logout();

        return redirect()->route('admin.login')
            ->with('error', 'Anda tidak memiliki hak akses untuk masuk ke halaman Superadmin.');
    }
}