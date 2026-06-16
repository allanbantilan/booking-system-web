<?php

use App\Http\Controllers\Api\V1\Payments\PayMayaWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Checkout/status run through the web Inertia flow (routes/web.php).
// Only the server-to-server webhook lives on the API surface.
Route::post('/payments/paymaya/webhook', PayMayaWebhookController::class)
    ->name('api.payments.paymaya.webhook');
