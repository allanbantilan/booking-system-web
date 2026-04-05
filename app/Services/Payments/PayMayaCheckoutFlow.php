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
    public function create(User $user, int $bookingId, int $quantity, int $nights): array
    {
        [$booking, $reservation, $payment] = DB::transaction(function () use ($bookingId, $quantity, $nights, $user): array {
            $booking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($bookingId);

            if ($quantity > $booking->capacity) {
                throw ValidationException::withMessages([
                    'quantity' => ["Only {$booking->capacity} slots left for this booking."],
                ]);
            }

            $basePrice = $this->discountedPrice($booking->price, $booking->discount_percentage);
            $extraRate = $booking->extra_rate !== null
                ? $this->discountedPrice((float) $booking->extra_rate, $booking->discount_percentage)
                : null;
            $defaults = Booking::typeDefaults((string) $booking->booking_type);
            $requiresNights = (bool) ($defaults['nights_required'] ?? false);
            $stayLength = $requiresNights ? max(1, $nights) : 1;

            if ($requiresNights && $nights < 1) {
                throw ValidationException::withMessages([
                    'nights' => ['Nights is required for this booking type.'],
                ]);
            }

            $reservation = Reservation::query()->create([
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'quantity' => $quantity,
                'nights' => $stayLength,
                'total_price' => $this->calculateTotal($basePrice, $extraRate, $quantity, $stayLength, $requiresNights),
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

    private function calculateTotal(float $basePrice, ?float $extraRate, int $quantity, int $nights, bool $requiresNights): float
    {
        if (!$requiresNights) {
            return $basePrice * $quantity;
        }

        if ($extraRate === null) {
            return $basePrice * $quantity * $nights;
        }

        $extraNights = max(0, $nights - 1);

        return ($basePrice * $quantity) + ($extraRate * $quantity * $extraNights);
    }

    private function discountedPrice(float $price, int $discountPercentage): float
    {
        $discount = max(0, min(100, $discountPercentage));

        return round($price * (1 - ($discount / 100)), 2);
    }
}
