<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Payments\PaymentFinalizer;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingConfirmedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirming_a_payment_sends_booking_confirmed_mail(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $booking = Booking::create([
            'title' => 'Sea View Room', 'description' => 'desc', 'location' => 'loc',
            'event_date' => now()->addWeek(), 'capacity' => 5, 'price' => 200, 'created_by' => null,
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id, 'booking_id' => $booking->id, 'quantity' => 1,
            'total_price' => 200, 'status' => StatusType::Pending,
            'check_in_date' => now()->addWeek(), 'check_out_date' => now()->addWeek()->addDay(),
        ]);
        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => 200, 'currency' => 'PHP', 'status' => 'pending',
            'reference' => 'TEST-REF-1',
            'user_id' => $user->id,
            'provider' => 'paymaya',
        ]);

        app(PaymentFinalizer::class)->apply($payment, 'succeeded');

        Mail::assertSent(BookingConfirmedMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_duplicate_apply_does_not_resend_confirmation_mail(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $booking = Booking::create([
            'title' => 'Sea View Room', 'description' => 'desc', 'location' => 'loc',
            'event_date' => now()->addWeek(), 'capacity' => 5, 'price' => 200, 'created_by' => null,
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id, 'booking_id' => $booking->id, 'quantity' => 1,
            'total_price' => 200, 'status' => StatusType::Pending,
            'check_in_date' => now()->addWeek(), 'check_out_date' => now()->addWeek()->addDay(),
        ]);
        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => 200, 'currency' => 'PHP', 'status' => 'pending',
            'reference' => 'TEST-REF-2',
            'user_id' => $user->id,
            'provider' => 'paymaya',
        ]);

        app(PaymentFinalizer::class)->apply($payment, 'succeeded');
        app(PaymentFinalizer::class)->apply($payment->fresh(), 'succeeded');

        Mail::assertSent(BookingConfirmedMail::class, 1);
    }
}
