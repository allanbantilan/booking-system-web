<?php

namespace App\Services\Reservations;

use App\Models\BackendUser;
use App\Models\Booking;
use App\Models\Reservation;
use App\Models\ReservationCancellationRequest;
use App\Models\User;
use App\Types\StatusType;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
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
        return DB::transaction(function () use ($request, $merchant): ReservationCancellationRequest {
            $request = ReservationCancellationRequest::query()
                ->with(['booking', 'reservation'])
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->authorizeMerchant($request, $merchant);
            $this->ensureReviewable($request);

            $reservation = Reservation::query()
                ->lockForUpdate()
                ->findOrFail($request->reservation_id);

            if (in_array($reservation->status, [StatusType::Pending, StatusType::Confirmed], true)) {
                Booking::query()
                    ->lockForUpdate()
                    ->whereKey($reservation->booking_id)
                    ->increment('capacity', $reservation->quantity);
            }

            $reservation->update([
                'status' => StatusType::Cancelled,
                'cancelled_at' => now(),
            ]);

            $request->update([
                'status' => ReservationCancellationRequest::STATUS_APPROVED,
                'reviewed_at' => now(),
                'refund_required' => true,
                'refund_status' => ReservationCancellationRequest::REFUND_PENDING,
            ]);

            return $request->fresh(['booking', 'reservation']);
        });
    }

    public function reject(ReservationCancellationRequest $request, BackendUser $merchant, string $note): ReservationCancellationRequest
    {
        return DB::transaction(function () use ($request, $merchant, $note): ReservationCancellationRequest {
            $request = ReservationCancellationRequest::query()
                ->with(['booking', 'reservation'])
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->authorizeMerchant($request, $merchant);
            $this->ensureReviewable($request);

            $request->update([
                'status' => ReservationCancellationRequest::STATUS_REJECTED,
                'merchant_note' => $note,
                'reviewed_at' => now(),
            ]);

            return $request->fresh(['booking', 'reservation']);
        });
    }

    public function expireOverdueRequests(): int
    {
        return DB::transaction(function (): int {
            $requests = ReservationCancellationRequest::query()
                ->where('status', ReservationCancellationRequest::STATUS_REQUESTED)
                ->where('expires_at', '<=', now())
                ->lockForUpdate()
                ->get();

            foreach ($requests as $request) {
                $request->update([
                    'status' => ReservationCancellationRequest::STATUS_EXPIRED,
                    'refund_required' => false,
                    'refund_status' => ReservationCancellationRequest::REFUND_NOT_REQUIRED,
                ]);
            }

            return $requests->count();
        });
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

    private function authorizeMerchant(ReservationCancellationRequest $request, BackendUser $merchant): void
    {
        if ($merchant->hasAnyRole(['admin', 'super_admin'])) {
            return;
        }

        if ((int) $request->booking?->created_by === (int) $merchant->id) {
            return;
        }

        throw new AuthorizationException('You cannot review this cancellation request.');
    }

    private function ensureReviewable(ReservationCancellationRequest $request): void
    {
        if ($request->status !== ReservationCancellationRequest::STATUS_REQUESTED) {
            throw ValidationException::withMessages([
                'status' => ['Cancellation request has already been reviewed.'],
            ]);
        }

        if (!now()->lt($request->expires_at)) {
            throw ValidationException::withMessages([
                'status' => ['Cancellation request has expired.'],
            ]);
        }
    }
}
