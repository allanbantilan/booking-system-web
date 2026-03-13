<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PayMayaCheckoutFlow
{
    public function __construct(private PayMayaService $payMaya)
    {
    }

    /**
     * @return array{payment: Payment, checkout_url: string}
     */
    public function create(User $user, int $bookingId, int $quantity): array
    {
        [$booking, $reservation, $payment] = DB::transaction(function () use ($bookingId, $quantity, $user): array {
            $booking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($bookingId);

            if ($quantity > $booking->capacity) {
                throw ValidationException::withMessages([
                    'quantity' => ["Only {$booking->capacity} slots left for this booking."],
                ]);
            }

            $unitPrice = $this->discountedPrice($booking->price, $booking->discount_percentage);

            $reservation = Reservation::query()->create([
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'quantity' => $quantity,
                'total_price' => $unitPrice * $quantity,
                'status' => 'pending',
            ]);

            $payment = Payment::query()->create([
                'reservation_id' => $reservation->id,
                'user_id' => $user->id,
                'provider' => 'paymaya',
                'status' => 'initiated',
                'amount' => $reservation->total_price,
                'currency' => 'PHP',
                'reference' => 'PMY-' . Str::upper(Str::random(10)),
                'raw_request' => null,
            ]);

            return [$booking, $reservation, $payment];
        });

        try {
            $checkout = $this->payMaya->createCheckout($reservation, $booking, $user, $payment);
        } catch (\Throwable $exception) {
            $payment->update([
                'status' => 'failed',
                'raw_response' => ['error' => $exception->getMessage()],
            ]);

            $reservation->delete();

            throw new RuntimeException('Unable to create PayMaya checkout. ' . $exception->getMessage(), 0, $exception);
        }

        $response = $checkout['response'] ?? [];
        $checkoutUrl = $response['redirectUrl'] ?? $response['checkoutUrl'] ?? null;

        $payment->update([
            'status' => 'pending',
            'checkout_id' => $response['checkoutId'] ?? $response['id'] ?? null,
            'checkout_url' => $checkoutUrl,
            'raw_request' => $checkout['payload'] ?? null,
            'raw_response' => $response,
        ]);

        if (!$checkoutUrl) {
            throw new RuntimeException('Missing PayMaya checkout URL.');
        }

        return [
            'payment' => $payment,
            'checkout_url' => $checkoutUrl,
        ];
    }

    private function discountedPrice(float $price, int $discountPercentage): float
    {
        $discount = max(0, min(100, $discountPercentage));

        return round($price * (1 - ($discount / 100)), 2);
    }
}
