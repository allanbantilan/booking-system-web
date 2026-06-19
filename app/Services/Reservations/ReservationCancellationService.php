<?php

namespace App\Services\Reservations;

use App\Mail\BookingCancelledMail;
use App\Models\BackendUser;
use App\Models\Booking;
use App\Models\Reservation;
use App\Models\ReservationCancellationRequest;
use App\Models\User;
use App\Services\Payments\PayMayaService;
use App\Types\CancellationRefundStatus;
use App\Types\CancellationRequestStatus;
use App\Types\StatusType;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ReservationCancellationService
{
    public function eligibility(Reservation $reservation): array
    {
        $reservation->loadMissing(['booking', 'activeCancellationRequest']);
        $policy = 'Cancellation requests close 3 days before booking start. Refund is pending merchant approval.';

        if ($reservation->status === StatusType::Cancelled) {
            return $this->blocked('already_cancelled', 'Reservation already cancelled.', $policy);
        }

        if (! in_array($reservation->status, [StatusType::Pending, StatusType::Confirmed], true)) {
            return $this->blocked('status_not_cancellable', 'Status cannot be cancelled.', $policy);
        }

        if ($reservation->activeCancellationRequest) {
            return $this->blocked('active_request_exists', 'Cancellation request is already pending merchant review.', $policy);
        }

        $startDate = $this->resolveStartDate($reservation);
        if (! $startDate) {
            return $this->blocked('missing_booking_date', 'Cancellation is unavailable because this booking has no start date.', $policy);
        }

        if (! now()->lt($startDate->copy()->subDays(3))) {
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
            if (! $eligibility['can_request']) {
                throw ValidationException::withMessages([
                    'error' => [$eligibility['block_label']],
                ]);
            }

            return ReservationCancellationRequest::create([
                'reservation_id' => $reservation->id,
                'user_id' => $user->id,
                'booking_id' => $reservation->booking_id,
                'merchant_id' => $reservation->booking?->created_by,
                'status' => CancellationRequestStatus::Requested,
                'reason' => $reason,
                'requested_at' => now(),
                'expires_at' => $eligibility['expires_at'],
                'refund_required' => false,
                'refund_status' => CancellationRefundStatus::NotRequired,
            ]);
        });
    }

    public function approve(ReservationCancellationRequest $request, BackendUser $merchant): ReservationCancellationRequest
    {
        $request = DB::transaction(function () use ($request, $merchant): ReservationCancellationRequest {
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
                'status' => CancellationRequestStatus::Approved,
                'reviewed_at' => now(),
                'refund_required' => true,
                'refund_status' => CancellationRefundStatus::Pending,
            ]);

            return $request->fresh(['booking', 'reservation']);
        });

        $reservation = $request->reservation;
        if ($reservation) {
            $reservation->loadMissing(['user', 'booking']);
            if ($reservation->user) {
                Mail::to($reservation->user->email)->send(new BookingCancelledMail($reservation));
            }
        }

        return $request;
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
                'status' => CancellationRequestStatus::Rejected,
                'merchant_note' => $note,
                'reviewed_at' => now(),
            ]);

            return $request->fresh(['booking', 'reservation']);
        });
    }

    public function markRefundProcessed(ReservationCancellationRequest $request, BackendUser $merchant): ReservationCancellationRequest
    {
        return DB::transaction(function () use ($request, $merchant): ReservationCancellationRequest {
            $request = ReservationCancellationRequest::query()
                ->with(['booking', 'reservation'])
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->authorizeMerchant($request, $merchant);

            if ($request->status !== CancellationRequestStatus::Approved) {
                throw ValidationException::withMessages([
                    'refund_status' => ['Only approved cancellations can be marked as refunded.'],
                ]);
            }

            if ($request->refund_status !== CancellationRefundStatus::Pending) {
                throw ValidationException::withMessages([
                    'refund_status' => ['Refund is not pending.'],
                ]);
            }

            $request->update([
                'refund_status' => CancellationRefundStatus::Processed,
            ]);

            return $request->fresh(['booking', 'reservation']);
        });
    }

    public function refundViaPayMaya(ReservationCancellationRequest $request, BackendUser $merchant): ReservationCancellationRequest
    {
        $request = ReservationCancellationRequest::query()
            ->with(['booking', 'reservation.payment'])
            ->findOrFail($request->id);

        $this->authorizeMerchant($request, $merchant);
        $this->ensureRefundPending($request);

        $payment = $request->reservation?->payment;
        if (! $payment || $payment->provider !== 'paymaya') {
            throw ValidationException::withMessages([
                'refund_status' => ['No PayMaya payment found for this reservation.'],
            ]);
        }

        $payMaya = app(PayMayaService::class);
        $transactionReferenceNo = $this->mayaTransactionReference($payment->raw_webhook ?? [], $payment->raw_response ?? []);
        if (! $transactionReferenceNo && $payment->checkout_id) {
            try {
                $payment->raw_response = $payMaya->fetchCheckout($payment->checkout_id);
                $payment->save();
                $transactionReferenceNo = $this->mayaTransactionReference($payment->raw_response ?? []);
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages([
                    'refund_status' => [$exception->getMessage()],
                ]);
            }
        }

        if (! $transactionReferenceNo) {
            throw ValidationException::withMessages([
                'refund_status' => ['Missing Maya transaction reference.'],
            ]);
        }

        try {
            $payMaya->refund(
                $transactionReferenceNo,
                (float) $payment->amount,
                'Reservation cancellation refund #'.$request->id,
                'refund-'.$request->id.'-'.$payment->id,
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'refund_status' => [$exception->getMessage()],
            ]);
        }

        return $this->markRefundProcessed($request, $merchant);
    }

    public function expireOverdueRequests(): int
    {
        return DB::transaction(function (): int {
            $requests = ReservationCancellationRequest::query()
                ->where('status', CancellationRequestStatus::Requested)
                ->where('expires_at', '<=', now())
                ->lockForUpdate()
                ->get();

            foreach ($requests as $request) {
                $request->update([
                    'status' => CancellationRequestStatus::Expired,
                    'refund_required' => false,
                    'refund_status' => CancellationRefundStatus::NotRequired,
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

        if ((int) $request->merchant_id === (int) $merchant->id) {
            return;
        }

        if ((int) $request->booking?->created_by === (int) $merchant->id) {
            return;
        }

        throw new AuthorizationException('You cannot review this cancellation request.');
    }

    private function ensureReviewable(ReservationCancellationRequest $request): void
    {
        if ($request->status !== CancellationRequestStatus::Requested) {
            throw ValidationException::withMessages([
                'status' => ['Cancellation request has already been reviewed.'],
            ]);
        }

        if (! now()->lt($request->expires_at)) {
            throw ValidationException::withMessages([
                'status' => ['Cancellation request has expired.'],
            ]);
        }
    }

    private function ensureRefundPending(ReservationCancellationRequest $request): void
    {
        if ($request->status !== CancellationRequestStatus::Approved) {
            throw ValidationException::withMessages([
                'refund_status' => ['Only approved cancellations can be refunded.'],
            ]);
        }

        if ($request->refund_status !== CancellationRefundStatus::Pending) {
            throw ValidationException::withMessages([
                'refund_status' => ['Refund is not pending.'],
            ]);
        }
    }

    private function mayaTransactionReference(array ...$payloads): ?string
    {
        $paths = [
            'transactionReferenceNo',
            'transactionReferenceNumber',
            'payment.transactionReferenceNo',
            'payment.transactionReferenceNumber',
            'data.transactionReferenceNo',
            'data.transactionReferenceNumber',
        ];

        foreach ($payloads as $payload) {
            foreach ($paths as $path) {
                $value = Arr::get($payload, $path);
                if (is_string($value) && $value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }
}
