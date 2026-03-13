<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PaymentResource;
use App\Models\Payment;
use App\Services\Payments\PayMayaService;
use Illuminate\Http\JsonResponse;

class PayMayaCheckoutStatusController extends Controller
{
    /**
     * Fetch PayMaya checkout status and sync local records.
     */
    public function __invoke(string $checkoutId, PayMayaService $payMaya): JsonResponse
    {
        $payment = Payment::query()
            ->where('provider', 'paymaya')
            ->where('checkout_id', $checkoutId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $response = $payMaya->fetchCheckout($checkoutId);

        $status = $this->normalizeStatus($response['status'] ?? $response['paymentStatus'] ?? null);

        if ($status) {
            $payment->status = $status;
            $payment->raw_response = $response;
            $payment->save();

            if ($status === 'succeeded') {
                $payment->reservation?->update(['status' => 'confirmed']);
            }

            // Keep reservation pending if payment is cancelled/failed in sandbox flow.
        }

        return (new PaymentResource($payment->load('reservation.booking')))
            ->additional([
                'message' => 'Checkout status synced.',
                'errors' => (object) [],
            ])
            ->response();
    }

    private function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        $upper = strtoupper($status);

        if (in_array($upper, ['PAYMENT_SUCCESS', 'SUCCESS', 'COMPLETED', 'CAPTURED', 'PAID', 'AUTHORIZED'], true)) {
            return 'succeeded';
        }

        if (in_array($upper, ['PAYMENT_FAILED', 'FAILED', 'EXPIRED'], true)) {
            return 'failed';
        }

        if (in_array($upper, ['CANCELLED', 'CANCELED'], true)) {
            return 'cancelled';
        }

        return 'pending';
    }
}
