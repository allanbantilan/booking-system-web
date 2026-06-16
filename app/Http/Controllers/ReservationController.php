<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\ReservationCancellationRequest;
use App\Services\Reservations\ReservationCancellationService;
use App\Types\StatusType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function cancel(
        Request $request,
        int $reservationId,
        ReservationCancellationService $cancellations
    ): RedirectResponse
    {
        $reservation = Reservation::query()
            ->whereKey($reservationId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        try {
            $cancellations->requestCancellation($reservation, $request->user(), null);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Cancellation request submitted for merchant review.');
    }

    public function history(Request $request, ReservationCancellationService $cancellations): Response
    {
        $reservations = Reservation::query()
            ->with([
                'booking:id,title,description,location,event_date,capacity,price,discount_percentage,availability_label,quantity_label,meta_line,amenities,category_id',
                'booking.category:id,name,color,badge_label',
                'booking.media',
                'activeCancellationRequest',
                'latestCancellationRequest',
                'payment',
                'receipt',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return Inertia::render('BookingHistory', [
            'reservations' => $reservations->map(function (Reservation $reservation) use ($cancellations): array {
                $eligibility = $cancellations->eligibility($reservation);

                return [
                    'id' => $reservation->id,
                    'quantity' => $reservation->quantity,
                    'total_price' => $reservation->total_price,
                    'status' => $reservation->status,
                    'cancelled_at' => $reservation->cancelled_at?->toIso8601String(),
                    'created_at' => $reservation->created_at?->toIso8601String(),
                    'can_cancel' => $eligibility['can_request'],
                    'cancel_block_reason' => $eligibility['block_reason'],
                    'cancel_block_label' => $eligibility['block_label'],
                    'cancel_policy_label' => $eligibility['policy_label'],
                    'cancellation_eligibility' => $eligibility,
                    'cancellation_request' => $reservation->latestCancellationRequest
                        ? $this->serializeCancellationRequest($reservation->latestCancellationRequest)
                        : null,
                    'nights' => $reservation->nights,
                    'payment' => $reservation->payment
                        ? [
                            'id' => $reservation->payment->id,
                            'status' => $reservation->payment->status,
                        ]
                        : null,
                    'receipt' => $reservation->receipt
                        ? [
                            'id' => $reservation->receipt->id,
                            'receipt_number' => $reservation->receipt->receipt_number,
                            'issued_at' => $reservation->receipt->issued_at?->toIso8601String(),
                        ]
                        : null,
                    'booking' => $reservation->booking
                        ? $this->serializeBooking($reservation->booking)
                        : null,
                ];
            }),
        ]);
    }

    private function canCancelReservation(Reservation $reservation): bool
    {
        return $this->cancellationEligibility($reservation)['can_cancel'];
    }

    /**
     * @return array{can_cancel: bool, cancel_block_reason: ?string, cancel_block_label: ?string, cancel_policy_label: string}
     */
    private function cancellationEligibility(Reservation $reservation): array
    {
        $policy = 'Cancellable within 3 days before receipt is issued.';

        if (!$reservation->created_at) {
            return [
                'can_cancel' => false,
                'cancel_block_reason' => 'status_not_cancellable',
                'cancel_block_label' => 'Cancellation unavailable',
                'cancel_policy_label' => $policy,
            ];
        }

        if ($reservation->status === StatusType::Cancelled) {
            return [
                'can_cancel' => false,
                'cancel_block_reason' => 'already_cancelled',
                'cancel_block_label' => 'Already cancelled',
                'cancel_policy_label' => $policy,
            ];
        }

        if (!in_array($reservation->status, [StatusType::Pending, StatusType::Confirmed], true)) {
            return [
                'can_cancel' => false,
                'cancel_block_reason' => 'status_not_cancellable',
                'cancel_block_label' => 'Status cannot be cancelled',
                'cancel_policy_label' => $policy,
            ];
        }

        if ($reservation->receipt || $reservation->receipt()->exists()) {
            return [
                'can_cancel' => false,
                'cancel_block_reason' => 'receipt_issued',
                'cancel_block_label' => 'Receipt issued',
                'cancel_policy_label' => $policy,
            ];
        }

        if (!now()->lt($reservation->created_at->copy()->addDays(3))) {
            return [
                'can_cancel' => false,
                'cancel_block_reason' => 'outside_window',
                'cancel_block_label' => 'Cancellation window ended',
                'cancel_policy_label' => $policy,
            ];
        }

        return [
            'can_cancel' => true,
            'cancel_block_reason' => null,
            'cancel_block_label' => null,
            'cancel_policy_label' => $policy,
        ];
    }

    private function serializeBooking(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'title' => $booking->title,
            'description' => $booking->description,
            'location' => $booking->location,
            'event_date' => $booking->event_date,
            'booking_type' => $booking->booking_type,
            'capacity' => $booking->capacity,
            'price' => $booking->price,
            'extra_rate' => $booking->extra_rate,
            'discount_percentage' => $booking->discount_percentage,
            'availability_label' => $booking->availability_label,
            'quantity_label' => $booking->quantity_label,
            'meta_line' => $booking->meta_line,
            'amenities' => $booking->amenities,
            'image_urls' => $booking->image_urls,
            'category' => $booking->category
                ? [
                    'id' => $booking->category->id,
                    'name' => $booking->category->name,
                    'color' => $booking->category->color,
                    'badge_label' => $booking->category->badge_label,
                ]
                : null,
        ];
    }

    private function serializeCancellationRequest(ReservationCancellationRequest $request): array
    {
        return [
            'id' => $request->id,
            'status' => $request->status,
            'reason' => $request->reason,
            'merchant_note' => $request->merchant_note,
            'requested_at' => $request->requested_at?->toIso8601String(),
            'reviewed_at' => $request->reviewed_at?->toIso8601String(),
            'expires_at' => $request->expires_at?->toIso8601String(),
            'refund_required' => $request->refund_required,
            'refund_status' => $request->refund_status,
        ];
    }
}
