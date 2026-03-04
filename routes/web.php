<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User-facing booking flow scaffolds (manual CRUD/business logic to be implemented by you).
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::post('/events/{eventId}/book', [BookingController::class, 'store'])->name('bookings.store');
    Route::patch('/bookings/{bookingId}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/bookings/history', [BookingController::class, 'history'])->name('bookings.history');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});