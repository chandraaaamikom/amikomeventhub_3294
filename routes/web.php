<?php

use Illuminate\Support\Facades\Route;

// Import Controllers Utama
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartCheckoutController;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Auth\SocialiteController;

// Import Controllers Admin
use App\Http\Controllers\Admin\AuthController; // Tambahkan ini untuk Auth Pertemuan 8
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\QrisController;

// Import Controllers Panitia (Soal 1c — Multi-Tenant SaaS)
use App\Http\Controllers\Organizer\DashboardController as OrganizerDashboardController;
use App\Http\Controllers\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Organizer\TransactionController as OrganizerTransactionController;


// Rute fallback bawaan Laravel agar melempar ke form login admin jika unauthenticated
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// ---------------------------------------------------------
// HALAMAN PUBLIK
// ---------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/kategori', [HomeController::class, 'kategori'])->name('kategori');
Route::get('/tentang-kami', [HomeController::class, 'about'])->name('about');
Route::get('/category/{id}', [HomeController::class, 'index'])->name('category.show');
Route::get('/event/{id}', [EventController::class, 'show'])->name('events.show');

// Profil publik penyelenggara (Soal 1b — riwayat review tampil di sini)
Route::get('/penyelenggara/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{id}/add', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/{id}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/cart/checkout', [CartCheckoutController::class, 'checkout'])->name('cart.checkout');
Route::post('/cart/checkout', [CartCheckoutController::class, 'process'])->name('cart.checkout.process');

Route::get('/checkout/{id}', [EventController::class, 'checkout'])->name('checkout');
Route::post('/checkout/{id}/payment', [EventController::class, 'createPayment'])->name('checkout.process');

// PERBAIKAN: Rute callback dibuat bersih tanpa withoutMiddleware agar tidak memicu error 404
Route::post('/payment/callback', [EventController::class, 'handlePaymentCallback'])->name('payment.callback');

// Offline QRIS payment (custom QR image flow)
Route::post('/checkout/{id}/offline', [EventController::class, 'createOfflinePayment'])->name('checkout.offline');
Route::post('/checkout/confirm-offline', [EventController::class, 'confirmOfflinePayment'])->name('checkout.offline.confirm');
Route::get('/my-ticket/{order_id?}', [EventController::class, 'ticket'])->name('ticket');
Route::get('/payment/{order_id}', 
    [\App\Http\Controllers\CheckoutController::class, 'payment'])
    ->name('checkout.payment');

Route::get('/success/{order_id}', 
    [\App\Http\Controllers\CheckoutController::class, 'success'])
    ->name('checkout.success');

Route::get('/user/login', [UserAuthController::class, 'showLogin'])->name('user.login');
Route::post('/user/login', [UserAuthController::class, 'login'])->name('user.login.post');
Route::post('/user/logout', [UserAuthController::class, 'logout'])->name('user.logout');
Route::get('/user/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard')->middleware('auth');

// SSO Google (Soal 1a)
Route::get('/auth/google', [SocialiteController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [SocialiteController::class, 'callback'])->name('auth.google.callback');

// ---------------------------------------------------------
// PANEL SUPERADMIN
// ---------------------------------------------------------
Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
    
    // 1. Rute Autentikasi (Bisa diakses tanpa login)
    // Urutan diperbaiki: login POST dipindah ke atas resouce/middleware agar tidak bentrok
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // 2. Rute Terproteksi (Wajib Login & Harus Superadmin)
    Route::middleware(['auth', 'admin'])->group(function () {
        
        // Dashboard & Laporan Transaksi
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/transactions', [DashboardController::class, 'transactions'])->name('transactions.index');

        // Kelola QRIS
        Route::get('/qris', [QrisController::class, 'index'])->name('qris.index');
        Route::post('/qris', [QrisController::class, 'update'])->name('qris.update');
        
        // Kelola Event
        Route::get('/events', [AdminEventController::class, 'index'])->name('events.index');
        Route::resource('events', AdminEventController::class)->except(['index']);
        
        // Kelola Kategori
        Route::resource('categories', CategoryController::class);
        
        // MODUL PARTNER (Tugas UTS Soal 2 & 3)
        Route::resource('partners', PartnerController::class);
        
    });
});

// ---------------------------------------------------------
// PANEL PANITIA (Soal 1c — Multi-Tenant SaaS)
// ---------------------------------------------------------
Route::middleware(['auth', 'organizer'])->prefix('organizer')->as('organizer.')->group(function () {
    Route::get('/dashboard', [OrganizerDashboardController::class, 'index'])->name('dashboard');

    Route::get('/events', [OrganizerEventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [OrganizerEventController::class, 'create'])->name('events.create');
    Route::post('/events', [OrganizerEventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}/edit', [OrganizerEventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [OrganizerEventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [OrganizerEventController::class, 'destroy'])->name('events.destroy');

    Route::get('/transactions', [OrganizerTransactionController::class, 'index'])->name('transactions.index');
});

//callback midtrans
Route::post('/midtrans/callback', [\App\Http\Controllers\MidtransWebhookController::class, 'handle']);

// Rating & Review (Soal 1b)
Route::middleware('auth')->group(function () {
    Route::post('/event/{event}/review', [ReviewController::class, 'store'])->name('reviews.store');
    Route::put('/review/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/review/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});