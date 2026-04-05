<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reservation = $this->whenLoaded('reservation');
        $booking = $reservation ? $reservation->booking : null;

        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'status' => $this->status,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'checkout_id' => $this->checkout_id,
            'checkout_url' => $this->checkout_url,
            'reference' => $this->reference,
            'reservation' => $reservation
                ? [
                    'id' => $reservation->id,
                    'status' => $reservation->status,
                    'quantity' => $reservation->quantity,
                    'nights' => $reservation->nights,
                    'total_price' => (float) $reservation->total_price,
                    'booking' => $booking
                        ? [
                            'id' => $booking->id,
                            'title' => $booking->title,
                            'event_date' => $booking->event_date,
                            'booking_type' => $booking->booking_type,
                            'extra_rate' => $booking->extra_rate,
                        ]
                        : null,
                ]
                : null,
        ];
    }
}
