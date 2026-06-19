<?php

namespace App\Services\Payments;

use App\Mail\BookingConfirmedMail;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PaymentFinalizer
{
    public function apply(Payment $payment, string $status, array $raw = [], array $webhook = []): Payment
    {
        $justConfirmed = false;

        $payment = DB::transaction(function () use ($payment, $status, $raw, $webhook, &$justConfirmed): Payment {
            // Re-read with a lock so concurrent webhooks queue behind each other.
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            // Idempotency: if already succeeded, nothing to do.
            if ($payment->status === 'succeeded') {
                return $payment;
            }

            if (!empty($raw)) {
                $payment->raw_response = $raw;
            }
            if (!empty($webhook)) {
                $payment->raw_webhook = $webhook;
            }
            $payment->status = $status;
            $payment->save();

            $reservation = $payment->reservation()->lockForUpdate()->first();

            if ($status !== 'succeeded') {
                // Payment failed or was cancelled. Release the capacity hold that
                // was taken at checkout (A5) — but only once, while the reservation
                // is still pending, so duplicate webhooks stay idempotent. A
                // confirmed reservation is left untouched (refunds are out of scope).
                if ($reservation && $reservation->status->value === 'pending') {
                    $booking = $reservation->booking()->lockForUpdate()->first();
                    if ($booking) {
                        $booking->increment('capacity', $reservation->quantity);
                    }
                    $reservation->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);
                }

                return $payment;
            }

            if (!$reservation) {
                return $payment;
            }

            if ($reservation->status->value !== 'confirmed') {
                // If a prior failed/cancelled webhook already released the hold,
                // re-take it before confirming so capacity stays accurate.
                if ($reservation->status->value === 'cancelled') {
                    $booking = $reservation->booking()->lockForUpdate()->first();
                    if ($booking) {
                        $booking->decrement('capacity', $reservation->quantity);
                    }
                }

                // For a pending reservation the capacity is already held from
                // checkout creation (A5); just transition it to confirmed.
                $reservation->update([
                    'status' => 'confirmed',
                    'cancelled_at' => null,
                ]);
                $justConfirmed = true;
            }

            Receipt::firstOrCreate(
                ['payment_id' => $payment->id],
                [
                    'reservation_id' => $reservation->id,
                    'receipt_number' => 'RCPT-' . now()->format('Ymd') . '-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'issued_at' => now(),
                    'metadata' => [
                        'reference' => $payment->reference,
                        'customer_name' => $reservation->user?->name,
                        'booking_title' => $reservation->booking?->title,
                        'booking_date' => $reservation->booking?->event_date?->toIso8601String(),
                    ],
                ]
            );

            return $payment;
        });

        if ($justConfirmed) {
            $reservation = $payment->reservation()->with(['user', 'booking'])->first();
            if ($reservation?->user) {
                try {
                    Mail::to($reservation->user->email)->send(new BookingConfirmedMail($reservation));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return $payment;
    }
}
