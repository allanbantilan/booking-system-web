<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\Reservations\ReservationCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationCancellationRequestController extends Controller
{
    public function store(
        Request $request,
        Reservation $reservation,
        ReservationCancellationService $cancellations
    ): RedirectResponse {
        if ((int) $reservation->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $cancellations->requestCancellation(
                $reservation,
                $request->user(),
                $validated['reason'] ?? null,
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Cancellation request submitted for merchant review.');
    }
}
