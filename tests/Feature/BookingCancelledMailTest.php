<?php

namespace Tests\Feature;

use App\Mail\BookingCancelledMail;
use App\Models\BackendUser;
use App\Models\Booking;
use App\Models\Reservation;
use App\Models\ReservationCancellationRequest;
use App\Models\User;
use App\Services\Reservations\ReservationCancellationService;
use App\Types\CancellationRequestStatus;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingCancelledMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_cancellation_sends_booking_cancelled_mail(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $booking = Booking::create([
            'title' => 'Mountain Cabin',
            'description' => 'desc',
            'location' => 'loc',
            'event_date' => now()->addWeek(),
            'capacity' => 5,
            'price' => 150,
            'created_by' => null,
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => 1,
            'total_price' => 150,
            'status' => StatusType::Confirmed,
            'check_in_date' => now()->addWeek(),
            'check_out_date' => now()->addWeek()->addDay(),
        ]);
        $merchant = BackendUser::create([
            'name' => 'Mia Merchant',
            'email' => 'mia.merchant@example.com',
            'password' => Hash::make('password'),
        ]);
        $request = ReservationCancellationRequest::create([
            'reservation_id' => $reservation->id,
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'status' => CancellationRequestStatus::Requested,
            'requested_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        app(ReservationCancellationService::class)->approve($request, $merchant);

        Mail::assertSent(BookingCancelledMail::class, fn ($mail) => $mail->hasTo($user->email));
    }
}
