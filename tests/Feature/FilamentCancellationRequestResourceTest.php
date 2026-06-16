<?php

namespace Tests\Feature;

use App\Filament\Resources\CancellationRequests\CancellationRequestResource;
use App\Models\Booking;
use App\Models\BackendUser;
use App\Models\Reservation;
use App\Models\ReservationCancellationRequest;
use App\Models\User;
use App\Filament\Resources\CancellationRequests\Pages\ListCancellationRequests;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentCancellationRequestResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_only_sees_requests_for_their_own_bookings(): void
    {
        $owner = $this->makeBackendUser('scope-owner@test.local', 'merchant');
        $other = $this->makeBackendUser('scope-other@test.local', 'merchant');

        $ownRequest = $this->makeRequestForMerchant($owner);
        $foreignRequest = $this->makeRequestForMerchant($other);

        $this->actingAs($owner, 'backend');

        $ids = CancellationRequestResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($ownRequest->id, $ids);
        $this->assertNotContains($foreignRequest->id, $ids);
    }

    public function test_super_admin_sees_all_requests(): void
    {
        $admin = $this->makeBackendUser('scope-admin@test.local', 'super_admin');
        $merchant = $this->makeBackendUser('scope-merchant@test.local', 'merchant');

        $first = $this->makeRequestForMerchant($merchant);
        $second = $this->makeRequestForMerchant($this->makeBackendUser('scope-merchant2@test.local', 'merchant'));

        $this->actingAs($admin, 'backend');

        $ids = CancellationRequestResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($first->id, $ids);
        $this->assertContains($second->id, $ids);
    }

    public function test_merchant_can_approve_request_from_table(): void
    {
        $merchant = $this->makeBackendUser('approve-merchant@test.local', 'merchant');
        $request = $this->makeRequestForMerchant($merchant);

        $this->actingAs($merchant, 'backend');

        Livewire::test(ListCancellationRequests::class)
            ->assertCanSeeTableRecords([$request])
            ->callTableAction('approve', $request);

        $request->refresh();

        $this->assertSame(ReservationCancellationRequest::STATUS_APPROVED, $request->status);
        $this->assertSame(StatusType::Cancelled, $request->reservation->fresh()->status);
        $this->assertSame(ReservationCancellationRequest::REFUND_PENDING, $request->refund_status);
    }

    private function makeBackendUser(string $email, string $role): BackendUser
    {
        $user = BackendUser::create([
            'name' => $email,
            'email' => $email,
            'password' => 'password',
        ]);

        $user->assignRole(Role::firstOrCreate([
            'name' => $role,
            'guard_name' => 'backend',
        ]));

        return $user;
    }

    private function makeRequestForMerchant(BackendUser $merchant): ReservationCancellationRequest
    {
        $user = User::factory()->create();
        $booking = Booking::create([
            'title' => 'Beach Stay',
            'description' => 'Room booking.',
            'location' => 'Cebu',
            'event_date' => now()->addDays(10),
            'capacity' => 5,
            'price' => 1000,
            'created_by' => $merchant->id,
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'quantity' => 1,
            'total_price' => 1000,
            'status' => StatusType::Confirmed,
        ]);

        return ReservationCancellationRequest::create([
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'merchant_id' => $merchant->id,
            'status' => ReservationCancellationRequest::STATUS_REQUESTED,
            'requested_at' => now(),
            'expires_at' => now()->addDays(7),
            'refund_status' => ReservationCancellationRequest::REFUND_NOT_REQUIRED,
        ]);
    }
}
