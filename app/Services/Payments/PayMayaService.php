<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PayMayaService
{
    public function createCheckout(Reservation $reservation, Booking $booking, User $user, Payment $payment): array
    {
        $publicKey = config('services.paymaya.public_key');
        $baseUrl = rtrim((string) config('services.paymaya.base_url'), '/');

        if (!$publicKey) {
            throw new RuntimeException('PayMaya public key is not configured.');
        }

        $payload = $this->buildCheckoutPayload($reservation, $booking, $user, $payment);

        $response = Http::baseUrl($baseUrl)
            ->withBasicAuth($publicKey, '')
            ->connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->post('/checkout/v1/checkouts', $payload);

        return [
            'payload' => $payload,
            'response' => $this->handleResponse($response),
        ];
    }

    public function fetchCheckout(string $checkoutId): array
    {
        $secretKey = config('services.paymaya.secret_key');
        $baseUrl = rtrim((string) config('services.paymaya.base_url'), '/');

        if (!$secretKey) {
            throw new RuntimeException('PayMaya secret key is not configured.');
        }

        $response = Http::baseUrl($baseUrl)
            ->withBasicAuth($secretKey, '')
            ->connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->get('/checkout/v1/checkouts/' . $checkoutId);

        return $this->handleResponse($response);
    }

    private function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        $message = $response->json('message')
            ?? $response->json('error')
            ?? $response->body();

        throw new RuntimeException('PayMaya request failed: ' . $message);
    }

    private function buildCheckoutPayload(Reservation $reservation, Booking $booking, User $user, Payment $payment): array
    {
        $totalAmount = (float) $reservation->total_price;
        $currency = 'PHP';
        $redirectSuccess = $this->buildRedirectUrl('success', $payment->id);
        $redirectCancel = $this->buildRedirectUrl('cancel', $payment->id);
        $redirectFailure = $this->buildRedirectUrl('failure', $payment->id);

        [$firstName, $lastName] = $this->splitName($user->name ?? 'Customer');

        return [
            'totalAmount' => [
                'value' => $totalAmount,
                'currency' => $currency,
            ],
            'buyer' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $user->email,
            ],
            'items' => [
                [
                    'name' => $booking->title,
                    'quantity' => $reservation->quantity,
                    'totalAmount' => [
                        'value' => $totalAmount,
                        'currency' => $currency,
                    ],
                ],
            ],
            'redirectUrl' => [
                'success' => $redirectSuccess,
                'cancel' => $redirectCancel,
                'failure' => $redirectFailure,
            ],
            'requestReferenceNumber' => $payment->reference ?? Str::uuid()->toString(),
            'metadata' => [
                'reservation_id' => $reservation->id,
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
            ],
        ];
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        $first = Arr::get($parts, 0, 'Customer');
        $last = Arr::get($parts, 1, '');

        return [$first, $last];
    }

    private function buildRedirectUrl(string $status, int $paymentId): string
    {
        $base = config('services.paymaya.redirect_' . $status)
            ?: rtrim(config('app.url'), '/') . '/payments/paymaya/return';

        $separator = str_contains($base, '?') ? '&' : '?';

        return $base . $separator . http_build_query([
            'status' => $status,
            'payment_id' => $paymentId,
        ]);
    }
}
