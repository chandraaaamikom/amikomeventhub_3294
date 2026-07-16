<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang panel panitia (tenant). Menempelkan organisasi aktif ke request
 * supaya controller di bawahnya tidak perlu menebak tenant mana yang dipakai.
 */
class OrganizerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('user.login')
                ->with('error', 'Silakan masuk terlebih dahulu.');
        }

        // Superadmin boleh mengintip panel panitia untuk keperluan pengawasan.
        if ($user->isSuperadmin()) {
            $organization = $request->route('organization')
                ?? \App\Models\Organization::query()->first();

            $request->attributes->set('organization', $organization);

            return $next($request);
        }

        $organization = $user->currentOrganization();

        if (! $organization) {
            return redirect()->route('home')
                ->with('error', 'Akun Anda belum terhubung ke organisasi penyelenggara mana pun.');
        }

        if (! $organization->is_active) {
            return redirect()->route('home')
                ->with('error', 'Organisasi Anda sedang dinonaktifkan oleh Superadmin.');
        }

        $request->attributes->set('organization', $organization);

        return $next($request);
    }
}