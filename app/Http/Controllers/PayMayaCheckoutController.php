<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payments\CreatePayMayaCheckoutRequest;
use App\Services\Payments\PayMayaCheckoutFlow;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

class PayMayaCheckoutController extends Controller
{
    /**
     * Start PayMaya checkout and redirect to the payment portal.
     */
    public function __invoke(CreatePayMayaCheckoutRequest $request, PayMayaCheckoutFlow $flow): Response
    {
        $user = $request->user();
        $quantity = (int) $request->input('quantity');
        $nights = (int) $request->input('nights');
        $bookingId = (int) $request->input('booking_id');

        try {
            $result = $flow->create($user, $bookingId, $quantity, $nights);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return Inertia::location($result['checkout_url']);
    }
}
