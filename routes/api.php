<?php

use App\Http\Controllers\Api\V1\Payments\CreatePayMayaCheckoutController;
use App\Http\Controllers\Api\V1\Payments\PayMayaCheckoutStatusController;
use App\Http\Controllers\Api\V1\Payments\PayMayaWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/payments/paymaya/checkout', CreatePayMayaCheckoutController::class)
        ->name('api.payments.paymaya.checkout');
    Route::get('/payments/paymaya/checkout/{checkoutId}', PayMayaCheckoutStatusController::class)
        ->name('api.payments.paymaya.status');
});

Route::post('/payments/paymaya/webhook', PayMayaWebhookController::class)
    ->name('api.payments.paymaya.webhook');
