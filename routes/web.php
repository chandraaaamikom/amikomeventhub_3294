<?php

use Illuminate\Support\Facades\Route;

// Import Controllers Utama
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartCheckoutController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\Auth\SocialiteController;

// Import Controllers Admin
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\QrisController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;

// Import Controllers Panitia (Soal 1c — Multi-Tenant SaaS)
use App\Http\Controllers\Organizer\CheckinController;
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

// ---------------------------------------------------------
// KERANJANG & CHECKOUT
// ---------------------------------------------------------
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{id}/add', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/{id}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/cart/checkout', [CartCheckoutController::class, 'checkout'])->name('cart.checkout');
Route::post('/cart/checkout', [CartCheckoutController::class, 'process'])->name('cart.checkout.process');

Route::get('/checkout/{id}', [EventController::class, 'checkout'])->name('checkout');
Route::post('/checkout/{id}/payment', [EventController::class, 'createPayment'])->name('checkout.process');
Route::post('/checkout/{orderId}/sync', [EventController::class, 'syncPayment'])->name('checkout.sync');

Route::get('/my-ticket/{order_id?}', [EventController::class, 'ticket'])->name('ticket');
Route::get('/payment/{order_id}', [CheckoutController::class, 'payment'])->name('checkout.payment');
Route::get('/success/{order_id}', [CheckoutController::class, 'success'])->name('checkout.success');

// ---------------------------------------------------------
// AUTENTIKASI PENGGUNA
// ---------------------------------------------------------
Route::get('/user/login', [UserAuthController::class, 'showLogin'])->name('user.login');
Route::post('/user/login', [UserAuthController::class, 'login'])->name('user.login.post');
Route::get('/user/register', [UserAuthController::class, 'showRegister'])->name('user.register');
Route::post('/user/register', [UserAuthController::class, 'register'])->name('user.register.post');
Route::post('/user/logout', [UserAuthController::class, 'logout'])->name('user.logout');
Route::get('/user/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard')->middleware('auth');

// SSO Google (Soal 1a)
Route::get('/auth/google', [SocialiteController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [SocialiteController::class, 'callback'])->name('auth.google.callback');

// Rating & Review (Soal 1b)
Route::middleware('auth')->group(function () {
    Route::post('/event/{event}/review', [ReviewController::class, 'store'])->name('reviews.store');
    Route::put('/review/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/review/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});

// ---------------------------------------------------------
// PANEL SUPERADMIN
// ---------------------------------------------------------
Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {

    // Autentikasi (tanpa login)
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Terproteksi — wajib login & superadmin
    Route::middleware(['auth', 'admin'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/transactions', [DashboardController::class, 'transactions'])->name('transactions.index');

        Route::get('/qris', [QrisController::class, 'index'])->name('qris.index');
        Route::post('/qris', [QrisController::class, 'update'])->name('qris.update');

        Route::get('/events', [AdminEventController::class, 'index'])->name('events.index');
        Route::resource('events', AdminEventController::class)->except(['index']);

        Route::resource('categories', CategoryController::class);

        Route::resource('partners', PartnerController::class);

        // Kelola Organisasi / Tenant (Soal 1c — pengawas ekosistem)
        Route::get('/organizations', [AdminOrganizationController::class, 'index'])->name('organizations.index');
        Route::get('/organizations/create', [AdminOrganizationController::class, 'create'])->name('organizations.create');
        Route::post('/organizations', [AdminOrganizationController::class, 'store'])->name('organizations.store');
        Route::get('/organizations/{organization}/edit', [AdminOrganizationController::class, 'edit'])->name('organizations.edit');
        Route::put('/organizations/{organization}', [AdminOrganizationController::class, 'update'])->name('organizations.update');
        Route::patch('/organizations/{organization}/toggle', [AdminOrganizationController::class, 'toggle'])->name('organizations.toggle');
        Route::post('/organizations/{organization}/members', [AdminOrganizationController::class, 'assignMember'])->name('organizations.members.add');
        Route::delete('/organizations/{organization}/members/{user}', [AdminOrganizationController::class, 'removeMember'])->name('organizations.members.remove');

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

    // QR Check-in Scanner (Soal 2)
    Route::get('/checkin', [CheckinController::class, 'index'])->name('checkin.index');
    Route::post('/checkin/process', [CheckinController::class, 'process'])->name('checkin.process');
});

// ---------------------------------------------------------
// WEBHOOK MIDTRANS
// ---------------------------------------------------------
Route::post('/midtrans/callback', [MidtransWebhookController::class, 'handle'])->name('midtrans.callback');
