<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PaymentResource;
use App\Models\Payment;
use App\Services\Payments\PayMayaService;
use App\Services\Payments\PaymentFinalizer;
use App\Services\Payments\PaymentStatusNormalizer;
use Illuminate\Http\JsonResponse;

class PayMayaCheckoutStatusController extends Controller
{
    /**
     * Fetch PayMaya checkout status and sync local records.
     */
    public function __invoke(string $checkoutId, PayMayaService $payMaya, PaymentFinalizer $finalizer): JsonResponse
    {
        $payment = Payment::query()
            ->where('provider', 'paymaya')
            ->where('checkout_id', $checkoutId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $response = $payMaya->fetchCheckout($checkoutId);

        $status = PaymentStatusNormalizer::normalize($response['status'] ?? $response['paymentStatus'] ?? null);

        if ($status) {
            $finalizer->apply($payment, $status, $response);
        }

        return (new PaymentResource($payment->load('reservation.booking')))
            ->additional([
                'message' => 'Checkout status synced.',
                'errors' => (object) [],
            ])
            ->response();
    }

}
