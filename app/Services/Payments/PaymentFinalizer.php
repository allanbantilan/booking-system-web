<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;

class PaymentFinalizer
{
    public function apply(Payment $payment, string $status, array $raw = [], array $webhook = []): Payment
    {
        return DB::transaction(function () use ($payment, $status, $raw, $webhook): Payment {
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

            if ($status !== 'succeeded') {
                return $payment;
            }

            $reservation = $payment->reservation()->lockForUpdate()->first();
            if (!$reservation) {
                return $payment;
            }

            if ($reservation->status !== 'confirmed') {
                // Capacity was already decremented at checkout creation time (A5).
                // We only need to transition the reservation to confirmed here.
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

            return $payment;
        });
    }
}
