<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;

class PaymentFinalizer
{
    public function apply(Payment $payment, string $status, array $raw = [], array $webhook = []): Payment
    {
        if ($payment->status === 'succeeded') {
            return $payment;
        }

        $payment->status = $status;
        if (!empty($raw)) {
            $payment->raw_response = $raw;
        }
        if (!empty($webhook)) {
            $payment->raw_webhook = $webhook;
        }
        $payment->save();

        if ($status !== 'succeeded') {
            return $payment;
        }

        DB::transaction(function () use ($payment): void {
            $reservation = $payment->reservation()->lockForUpdate()->first();
            if (!$reservation) {
                return;
            }

            if ($reservation->status !== 'confirmed') {
                $booking = $reservation->booking()->lockForUpdate()->first();
                if ($booking) {
                    $booking->update([
                        'capacity' => max(0, $booking->capacity - $reservation->quantity),
                    ]);
                }

                $reservation->update(['status' => 'confirmed']);
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
        });

        return $payment;
    }
}
