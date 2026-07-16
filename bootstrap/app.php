<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // DAFTARKAN ALIAS ADMIN MIDDLEWARE DI SINI (Sesuai Modul Pertemuan 8)
        $middleware->alias([
            'admin'     => \App\Http\Middleware\AdminMiddleware::class,
            'organizer' => \App\Http\Middleware\OrganizerMiddleware::class,
        ]);

        // PERBAIKAN MODUL 12: Mengecualikan route webhook Midtrans dari blokir CSRF
        $middleware->validateCsrfTokens(except: [
            '/midtrans/callback', 
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();