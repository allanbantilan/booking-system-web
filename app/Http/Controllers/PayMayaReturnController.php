<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Receipt;
use Inertia\Inertia;
use Inertia\Response;

class PayMayaReturnController extends Controller
{
    /**
     * Render the PayMaya return page for polling status.
     */
    public function __invoke(Request $request): Response
    {
        $paymentId = $request->integer('payment_id');
        $status = $request->string('status')->value();

        $receipt = null;
        $payment = null;

        if ($paymentId) {
            $payment = Payment::query()
                ->whereKey($paymentId)
                ->where('provider', 'paymaya')
                ->with('reservation.booking', 'reservation.user')
                ->first();

            if ($payment) {
                $normalized = $this->normalizeStatus($status);

                if ($normalized) {
                    $payment->status = $normalized;
                    $payment->save();

                    if ($normalized === 'succeeded') {
                        $payment->reservation?->update(['status' => 'confirmed']);
                        $receipt = $this->createReceipt($payment);
                    }
                }
            }
        }

        return Inertia::render('PaymentReturn', [
            'checkoutId' => $request->string('checkoutId')->value()
                ?: $request->string('id')->value(),
            'status' => $status,
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

    private function createReceipt(Payment $payment): ?Receipt
    {
        if (!$payment->reservation) {
            return null;
        }

        return Receipt::firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'reservation_id' => $payment->reservation->id,
                'receipt_number' => $this->generateReceiptNumber($payment->id),
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'issued_at' => now(),
                'metadata' => [
                    'reference' => $payment->reference,
                    'customer_name' => $payment->reservation?->user?->name,
                    'booking_title' => $payment->reservation?->booking?->title,
                    'booking_date' => $payment->reservation?->booking?->event_date?->toIso8601String(),
                ],
            ]
        );
    }

    private function generateReceiptNumber(int $paymentId): string
    {
        return 'RCPT-' . now()->format('Ymd') . '-' . str_pad((string) $paymentId, 6, '0', STR_PAD_LEFT);
    }
}
