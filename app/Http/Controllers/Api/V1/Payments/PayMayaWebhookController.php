<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentFinalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PayMayaWebhookController extends Controller
{
    /**
     * Handle PayMaya webhook callbacks.
     */
    public function __invoke(Request $request, PaymentFinalizer $finalizer): JsonResponse
    {
        $token = $request->header('X-PayMaya-Token');
        if (!$token || $token !== config('services.paymaya.webhook_token')) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized webhook.',
                'errors' => ['token' => ['Invalid webhook token.']],
            ], 401);
        }

        $payload = $request->all();

        $checkoutId = $payload['checkoutId']
            ?? $payload['id']
            ?? Arr::get($payload, 'data.id')
            ?? Arr::get($payload, 'payment.id');

        if (!$checkoutId) {
            return response()->json([
                'data' => null,
                'message' => 'Missing checkout id.',
                'errors' => ['checkout_id' => ['Checkout id not found in payload.']],
            ], 422);
        }

        $payment = Payment::query()
            ->where('provider', 'paymaya')
            ->where('checkout_id', $checkoutId)
            ->first();

        if (!$payment) {
            return response()->json([
                'data' => null,
                'message' => 'Payment not found.',
                'errors' => ['payment' => ['Payment record not found.']],
            ], 404);
        }

        $status = $this->normalizeStatus($payload['status'] ?? Arr::get($payload, 'paymentStatus'));

        if ($status) {
            $finalizer->apply($payment, $status, [], $payload);
        }

        return response()->json([
            'data' => ['ack' => true],
            'message' => 'Webhook processed.',
            'errors' => (object) [],
        ]);
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
