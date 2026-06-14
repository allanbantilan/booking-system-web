<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
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
    public function create(User $user, int $bookingId, int $quantity, int $nights, ?string $checkInDate, ?string $checkOutDate): array
    {
        [$booking, $reservation, $payment] = DB::transaction(function () use ($bookingId, $quantity, $nights, $user, $checkInDate, $checkOutDate): array {
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
            $requiresDateRange = in_array($booking->booking_type, [Booking::TYPE_RENTAL, Booking::TYPE_ACCOMMODATION], true);

            $checkIn = null;
            $checkOut = null;
            $stayLength = $requiresNights ? max(1, $nights) : 1;

            if ($requiresDateRange) {
                if (!$checkInDate || !$checkOutDate) {
                    throw ValidationException::withMessages([
                        'check_in_date' => ['Check-in date is required for this booking type.'],
                        'check_out_date' => ['Check-out date is required for this booking type.'],
                    ]);
                }

                $checkIn = Carbon::parse($checkInDate)->startOfDay();
                $checkOut = Carbon::parse($checkOutDate)->startOfDay();
                $today = now()->startOfDay();

                if ($checkIn->lt($today)) {
                    throw ValidationException::withMessages([
                        'check_in_date' => ['Check-in date must be today or later.'],
                    ]);
                }

                if ($checkOut->lte($checkIn)) {
                    throw ValidationException::withMessages([
                        'check_out_date' => ['Check-out date must be after check-in date.'],
                    ]);
                }

                $stayLength = max(1, $checkIn->diffInDays($checkOut));
            } elseif ($requiresNights && $nights < 1) {
                throw ValidationException::withMessages([
                    'nights' => ['Nights is required for this booking type.'],
                ]);
            }

            $reservation = Reservation::query()->create([
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'quantity' => $quantity,
                'nights' => $stayLength,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
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

            // Hold capacity immediately so concurrent checkouts cannot both
            // claim the last slot. The hold is released by PaymentFinalizer
            // on success (it sets status=confirmed and leaves capacity as-is),
            // or restored by the pending-expiry job / failure path below.
            $booking->decrement('capacity', $quantity);

            return [$booking, $reservation, $payment];
        });

        try {
            $checkout = $this->payMaya->createCheckout($reservation, $booking, $user, $payment);
        } catch (\Throwable $exception) {
            // PayMaya API failed — release the capacity hold and clean up.
            $this->releaseHold($booking, $reservation, $payment, $quantity, $exception->getMessage());

            throw new RuntimeException('Unable to create PayMaya checkout. ' . $exception->getMessage(), 0, $exception);
        }

        $response = $checkout['response'] ?? [];
        $checkoutUrl = $response['redirectUrl'] ?? $response['checkoutUrl'] ?? null;

        if (!$checkoutUrl) {
            // API responded but gave us no redirect URL — the hold would otherwise
            // leak, so release it and clean up before failing.
            $this->releaseHold($booking, $reservation, $payment, $quantity, 'Missing PayMaya checkout URL.');

            throw new RuntimeException('Missing PayMaya checkout URL.');
        }

        $payment->update([
            'status' => 'pending',
            'checkout_id' => $response['checkoutId'] ?? $response['id'] ?? null,
            'checkout_url' => $checkoutUrl,
            'raw_request' => $checkout['payload'] ?? null,
            'raw_response' => $response,
        ]);

        return [
            'payment' => $payment,
            'checkout_url' => $checkoutUrl,
        ];
    }

    /**
     * Roll back a capacity hold when checkout cannot complete before reaching
     * PayMaya: restore the held slots and remove the pending reservation. The
     * payment row is removed by the reservations -> payments cascade delete (it
     * has no checkout_id at this point, so no webhook can ever match it).
     */
    private function releaseHold(Booking $booking, Reservation $reservation, Payment $payment, int $quantity, string $reason): void
    {
        DB::transaction(function () use ($booking, $reservation, $payment, $quantity, $reason): void {
            Booking::lockForUpdate()->findOrFail($booking->id)->increment('capacity', $quantity);
            // Record the reason before the cascade delete in case anything is
            // observing the payment model events.
            $payment->forceFill([
                'status' => 'failed',
                'raw_response' => ['error' => $reason],
            ])->save();
            $reservation->delete();
        });
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
