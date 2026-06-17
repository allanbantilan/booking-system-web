<?php

namespace Tests\Feature;

use App\Filament\Resources\CancellationRequests\CancellationRequestResource;
use App\Filament\Resources\CancellationRequests\Pages\ListCancellationRequests;
use App\Models\BackendUser;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationCancellationRequest;
use App\Models\User;
use App\Services\Reservations\ReservationCancellationService;
use App\Types\CancellationRefundStatus;
use App\Types\CancellationRequestStatus;
use App\Types\StatusType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

        $this->assertSame(CancellationRequestStatus::Approved, $request->status);
        $this->assertSame(StatusType::Cancelled, $request->reservation->fresh()->status);
        $this->assertSame(CancellationRefundStatus::Pending, $request->refund_status);
    }

    public function test_customer_requested_cancellation_shows_for_booking_merchant(): void
    {
        $merchant = $this->makeBackendUser('requested-merchant@test.local', 'merchant');
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

        $request = app(ReservationCancellationService::class)
            ->requestCancellation($reservation, $user, 'Schedule changed');

        $this->actingAs($merchant, 'backend');

        $ids = CancellationRequestResource::getEloquentQuery()->pluck('id')->all();

        $this->assertSame($merchant->id, $request->merchant_id);
        $this->assertContains($request->id, $ids);
    }

    public function test_merchant_can_mark_pending_refund_as_processed(): void
    {
        $merchant = $this->makeBackendUser('refund-merchant@test.local', 'merchant');
        $request = $this->makeRequestForMerchant($merchant);
        $request->update([
            'status' => CancellationRequestStatus::Approved,
            'refund_required' => true,
            'refund_status' => CancellationRefundStatus::Pending,
        ]);

        $this->actingAs($merchant, 'backend');

        Livewire::test(ListCancellationRequests::class)
            ->assertCanSeeTableRecords([$request])
            ->callTableAction('markRefundProcessed', $request);

        $this->assertSame(
            CancellationRefundStatus::Processed,
            $request->fresh()->refund_status,
        );
    }

    public function test_merchant_can_refund_pending_refund_via_paymaya(): void
    {
        config([
            'services.paymaya.base_url' => 'https://pg-sandbox.paymaya.com',
            'services.paymaya.secret_key' => 'sandbox-secret',
        ]);
        Http::fake([
            'https://pg-sandbox.paymaya.com/p3/refund' => Http::response(['status' => 'REFUNDED'], 200),
        ]);

        $merchant = $this->makeBackendUser('paymaya-refund-merchant@test.local', 'merchant');
        $request = $this->makeApprovedRefundRequest($merchant, [
            'transactionReferenceNo' => 'TXN-REFUND-1',
        ]);

        $this->actingAs($merchant, 'backend');

        Livewire::test(ListCancellationRequests::class)
            ->assertCanSeeTableRecords([$request])
            ->callTableAction('refundViaPayMaya', $request);

        $this->assertSame(
            CancellationRefundStatus::Processed,
            $request->fresh()->refund_status,
        );
        Http::assertSent(fn ($httpRequest) => $httpRequest->url() === 'https://pg-sandbox.paymaya.com/p3/refund'
            && $httpRequest['transactionReferenceNo'] === 'TXN-REFUND-1'
            && $httpRequest['merchant']['name'] === config('app.name')
            && (float) $httpRequest['amount']['value'] === 1000.0
        );
    }

    public function test_paymaya_refund_without_transaction_reference_stays_pending(): void
    {
        $merchant = $this->makeBackendUser('missing-refund-reference@test.local', 'merchant');
        $request = $this->makeApprovedRefundRequest($merchant);

        $this->actingAs($merchant, 'backend');

        Livewire::test(ListCancellationRequests::class)
            ->assertCanSeeTableRecords([$request])
            ->callTableAction('refundViaPayMaya', $request);

        $this->assertSame(
            CancellationRefundStatus::Pending,
            $request->fresh()->refund_status,
        );
        Http::assertNothingSent();
    }

    public function test_paymaya_refund_fetches_checkout_when_transaction_reference_is_missing(): void
    {
        config([
            'services.paymaya.base_url' => 'https://pg-sandbox.paymaya.com',
            'services.paymaya.secret_key' => 'sandbox-secret',
        ]);
        Http::fake([
            'https://pg-sandbox.paymaya.com/checkout/v1/checkouts/CHK-REFUND-*' => Http::response([
                'status' => 'PAYMENT_SUCCESS',
                'transactionReferenceNo' => 'TXN-FETCHED-1',
            ], 200),
            'https://pg-sandbox.paymaya.com/p3/refund' => Http::response(['status' => 'REFUNDED'], 200),
        ]);

        $merchant = $this->makeBackendUser('fetch-refund-reference@test.local', 'merchant');
        $request = $this->makeApprovedRefundRequest($merchant);

        $this->actingAs($merchant, 'backend');

        Livewire::test(ListCancellationRequests::class)
            ->assertCanSeeTableRecords([$request])
            ->callTableAction('refundViaPayMaya', $request);

        $this->assertSame(
            CancellationRefundStatus::Processed,
            $request->fresh()->refund_status,
        );
        $this->assertSame(
            'TXN-FETCHED-1',
            $request->reservation->payment->fresh()->raw_response['transactionReferenceNo'],
        );
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
            'status' => CancellationRequestStatus::Requested,
            'requested_at' => now(),
            'expires_at' => now()->addDays(7),
            'refund_status' => CancellationRefundStatus::NotRequired,
        ]);
    }

    private function makeApprovedRefundRequest(BackendUser $merchant, array $rawWebhook = []): ReservationCancellationRequest
    {
        $request = $this->makeRequestForMerchant($merchant);
        $request->update([
            'status' => CancellationRequestStatus::Approved,
            'refund_required' => true,
            'refund_status' => CancellationRefundStatus::Pending,
        ]);

        Payment::create([
            'reservation_id' => $request->reservation_id,
            'user_id' => $request->user_id,
            'provider' => 'paymaya',
            'status' => 'succeeded',
            'amount' => 1000,
            'currency' => 'PHP',
            'checkout_id' => 'CHK-REFUND-'.$request->id,
            'reference' => 'PMY-REFUND-'.$request->id,
            'raw_webhook' => $rawWebhook,
        ]);

        return $request->fresh(['reservation.payment']);
    }
}
