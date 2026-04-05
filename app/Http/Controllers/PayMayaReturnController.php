<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Services\Payments\PaymentFinalizer;
use Inertia\Inertia;
use Inertia\Response;

class PayMayaReturnController extends Controller
{
    /**
     * Render the PayMaya return page for polling status.
     */
    public function __invoke(Request $request, PaymentFinalizer $finalizer): Response
    {
        $paymentId = $request->integer('payment_id');
        $status = $request->string('status')->value();
        $checkoutId = $request->string('checkoutId')->value()
            ?: $request->string('id')->value();

        $receipt = null;
        $payment = null;

        if ($paymentId) {
            $payment = Payment::query()
                ->whereKey($paymentId)
                ->where('provider', 'paymaya')
                ->where('user_id', $request->user()->id)
                ->with('reservation.booking', 'reservation.user')
                ->first();

            if ($payment) {
                if ($checkoutId && $payment->checkout_id && $checkoutId !== $payment->checkout_id) {
                    return Inertia::render('PaymentReturn', [
                        'checkoutId' => $checkoutId,
                        'status' => 'failed',
                        'payment' => null,
                        'receipt' => null,
                    ])->with('error', 'Checkout id mismatch.');
                }

                $normalized = $this->normalizeStatus($status);

                if ($normalized) {
                    $payment = $finalizer->apply($payment, $normalized);
                }

                $receipt = $payment->receipt;
            }
        }

        return Inertia::render('PaymentReturn', [
            'checkoutId' => $checkoutId,
            'status' => $payment?->status ?? $this->normalizeStatus($status) ?? $status,
            'payment' => $payment ? [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'reference' => $payment->reference,
                    'reservation' => $payment->reservation
                    ? [
                        'id' => $payment->reservation->id,
                        'quantity' => $payment->reservation->quantity,
                        'customer' => $payment->reservation->user
                            ? [
                                'id' => $payment->reservation->user->id,
                                'name' => $payment->reservation->user->name,
                                'email' => $payment->reservation->user->email,
                            ]
                            : null,
                        'booking' => $payment->reservation->booking
                            ? [
                                'id' => $payment->reservation->booking->id,
                                'title' => $payment->reservation->booking->title,
                                'event_date' => $payment->reservation->booking->event_date,
                            ]
                            : null,
                    ]
                    : null,
            ] : null,
            'receipt' => $receipt ? [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'amount' => (float) $receipt->amount,
                'currency' => $receipt->currency,
                'issued_at' => $receipt->issued_at?->toIso8601String(),
            ] : null,
        ]);
    }

    private function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        return match (strtolower($status)) {
            'success', 'succeeded' => 'succeeded',
            'cancel', 'cancelled', 'canceled', 'failure', 'failed' => 'cancelled',
            default => 'pending',
        };
    }

}
