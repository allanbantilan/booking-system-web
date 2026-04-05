<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\CreatePayMayaCheckoutRequest;
use App\Http\Resources\Api\PaymentResource;
use App\Services\Payments\PayMayaCheckoutFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CreatePayMayaCheckoutController extends Controller
{
    /**
     * Create a PayMaya checkout session and reserve a booking.
     */
    public function __invoke(CreatePayMayaCheckoutRequest $request, PayMayaCheckoutFlow $flow): JsonResponse
    {
        $user = $request->user();
        $quantity = (int) $request->input('quantity');
        $nights = (int) $request->input('nights');
        $bookingId = (int) $request->input('booking_id');
        try {
            $result = $flow->create($user, $bookingId, $quantity, $nights);
            $payment = $result['payment'];
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return response()->json([
                'data' => null,
                'message' => 'Unable to create PayMaya checkout. Please try again.',
                'errors' => ['paymaya' => [$exception->getMessage()]],
            ], 502);
        }

        return (new PaymentResource($payment->load('reservation.booking')))
            ->additional([
                'message' => 'Checkout created.',
                'errors' => (object) [],
            ])
            ->response();
    }
}
