<?php

namespace App\Services\Reservations;

use App\Models\BackendUser;
use App\Models\Booking;
use App\Models\Reservation;
use App\Models\ReservationCancellationRequest;
use App\Models\User;
use App\Types\StatusType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationCancellationService
{
    public function eligibility(Reservation $reservation): array
    {
        $reservation->loadMissing(['booking', 'activeCancellationRequest']);
        $policy = 'Cancellation requests close 3 days before booking start. Refund is pending merchant approval.';

        if ($reservation->status === StatusType::Cancelled) {
            return $this->blocked('already_cancelled', 'Reservation already cancelled.', $policy);
        }

        if (!in_array($reservation->status, [StatusType::Pending, StatusType::Confirmed], true)) {
            return $this->blocked('status_not_cancellable', 'Status cannot be cancelled.', $policy);
        }

        if ($reservation->activeCancellationRequest) {
            return $this->blocked('active_request_exists', 'Cancellation request is already pending merchant review.', $policy);
        }

        $startDate = $this->resolveStartDate($reservation);
        if (!$startDate) {
            return $this->blocked('missing_booking_date', 'Cancellation is unavailable because this booking has no start date.', $policy);
        }

        if (!now()->lt($startDate->copy()->subDays(3))) {
            return $this->blocked('within_cutoff', 'Cancellation is closed within 3 days of booking start.', $policy);
        }

        return [
            'can_request' => true,
            'block_reason' => null,
            'block_label' => null,
            'policy_label' => $policy,
            'start_date' => $startDate,
            'expires_at' => $startDate->copy()->subDays(3),
        ];
    }

    public function requestCancellation(Reservation $reservation, User $user, ?string $reason): ReservationCancellationRequest
    {
        return DB::transaction(function () use ($reservation, $user, $reason): ReservationCancellationRequest {
            $reservation = Reservation::query()
                ->with(['booking', 'activeCancellationRequest'])
                ->lockForUpdate()
                ->findOrFail($reservation->id);

            if ((int) $reservation->user_id !== (int) $user->id) {
                abort(404);
            }

            $eligibility = $this->eligibility($reservation);
            if (!$eligibility['can_request']) {
                throw ValidationException::withMessages([
                    'error' => [$eligibility['block_label']],
                ]);
            }

            return ReservationCancellationRequest::create([
                'reservation_id' => $reservation->id,
                'user_id' => $user->id,
                'booking_id' => $reservation->booking_id,
                'merchant_id' => $reservation->booking?->created_by,
                'status' => ReservationCancellationRequest::STATUS_REQUESTED,
                'reason' => $reason,
                'requested_at' => now(),
                'expires_at' => $eligibility['expires_at'],
                'refund_required' => false,
                'refund_status' => ReservationCancellationRequest::REFUND_NOT_REQUIRED,
            ]);
        });
    }

    public function approve(ReservationCancellationRequest $request, BackendUser $merchant): ReservationCancellationRequest
    {
        throw new \BadMethodCallException('Merchant approval is implemented in the review task.');
    }

    public function reject(ReservationCancellationRequest $request, BackendUser $merchant, string $note): ReservationCancellationRequest
    {
        throw new \BadMethodCallException('Merchant rejection is implemented in the review task.');
    }

    public function expireOverdueRequests(): int
    {
        throw new \BadMethodCallException('Expiry is implemented in the expiry task.');
    }

    private function resolveStartDate(Reservation $reservation): ?Carbon
    {
        $reservation->loadMissing('booking');
        $type = $reservation->booking?->booking_type;

        if (in_array($type, [Booking::TYPE_ACCOMMODATION, Booking::TYPE_RENTAL], true)) {
            return $reservation->check_in_date?->copy()->startOfDay();
        }

        return $reservation->booking?->event_date?->copy()->startOfDay();
    }

    private function blocked(string $reason, string $label, string $policy): array
    {
        return [
            'can_request' => false,
            'block_reason' => $reason,
            'block_label' => $label,
            'policy_label' => $policy,
            'start_date' => null,
            'expires_at' => null,
        ];
    }
}
