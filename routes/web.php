<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MerchantAccountController;
use App\Http\Controllers\PayMayaCheckoutController;
use App\Http\Controllers\PayMayaReturnController;
use App\Http\Controllers\ReservationCancellationRequestController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:3,1');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User-facing booking flow scaffolds (manual CRUD/business logic to be implemented by you).
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/history', [ReservationController::class, 'history'])->name('bookings.history');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::patch('/reservations/{reservationId}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::post('/reservations/{reservation}/cancellation-requests', [ReservationCancellationRequestController::class, 'store'])
        ->name('reservations.cancellation-requests.store');
    Route::post('/payments/paymaya/checkout', PayMayaCheckoutController::class)->name('payments.paymaya.checkout');

    Route::post('/profile/merchant-account', [MerchantAccountController::class, 'store'])
        ->name('merchant-account.store');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ponytail: web guard, not auth:sanctum. Sanctum only honors the session cookie
// when the request Referer is a stateful domain; on the Maya return the Referer
// is pg-sandbox.paymaya.com, so sanctum would ignore the cookie and bounce to login.
Route::get('/payments/paymaya/return', PayMayaReturnController::class)
    ->middleware(['auth:web', 'verified'])
    ->name('payments.paymaya.return');
