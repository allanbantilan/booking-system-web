<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentFinalizer
{
    public function apply(Payment $payment, string $status, array $raw = [], array $webhook = []): Payment
    {
        DB::transaction(function () use ($payment, $status, $raw, $webhook): void {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->find($payment->id);

            if (!$lockedPayment) {
                return;
            }

            if ($lockedPayment->status === 'succeeded') {
                $status = 'succeeded';
            }

            $lockedPayment->status = $status;
            $lockedPayment->raw_response = $raw;
            $lockedPayment->raw_webhook = $webhook;
            $lockedPayment->save();

            if ($status !== 'succeeded') {
                return;
            }

            $now = now();
            $reservation = $lockedPayment->reservation()->lockForUpdate()->first();
            if (!$reservation) {
                Log::warning('Reservation missing during payment finalization.', [
                    'payment_id' => $lockedPayment->id,
                ]);
                return;
            }

            if ($reservation->status !== 'confirmed') {
                $booking = $reservation->booking()->lockForUpdate()->first();
                if ($booking) {
                    if ($booking->capacity < $reservation->quantity) {
                        Log::warning('Booking capacity below reservation quantity during payment finalization.', [
                            'booking_id' => $booking->id,
                            'reservation_id' => $reservation->id,
                            'capacity' => $booking->capacity,
                            'quantity' => $reservation->quantity,
                        ]);
                    }
                    $booking->update([
                        'capacity' => max(0, $booking->capacity - $reservation->quantity),
                    ]);
                }

                $reservation->update(['status' => 'confirmed']);
            }

            Receipt::firstOrCreate(
                ['payment_id' => $lockedPayment->id],
                [
                    'reservation_id' => $reservation->id,
                    'receipt_number' => 'RCPT-' . $now->format('Ymd') . '-' . str_pad((string) $lockedPayment->id, 6, '0', STR_PAD_LEFT),
                    'amount' => $lockedPayment->amount,
                    'currency' => $lockedPayment->currency,
                    'issued_at' => $now,
                    'metadata' => [
                        'reference' => $lockedPayment->reference,
                        'customer_name' => $reservation->user?->name,
                        'booking_title' => $reservation->booking?->title,
                        'booking_date' => $reservation->booking?->event_date?->toIso8601String(),
                    ],
                ]
            );
        });

        return $payment;
    }
}
