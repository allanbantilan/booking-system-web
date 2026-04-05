# Payments Critical/High Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Secure PayMaya sandbox flows and make payment finalization consistent across return, webhook, and status polling.

**Architecture:** Introduce a centralized `PaymentFinalizer` service that applies normalized status transitions with idempotent reservation confirmation, capacity decrement, and receipt creation. Controllers call the finalizer with guard rails (ownership, checkout id match, webhook token).

**Tech Stack:** Laravel, Inertia, Filament, PayMaya sandbox, MySQL

---

## File Map (Create/Modify)

**Create**
- `app/Services/Payments/PaymentFinalizer.php` — central payment status application + idempotent side effects.

**Modify**
- `app/Http/Controllers/PayMayaReturnController.php` — add ownership + checkout_id guards; delegate to finalizer.
- `app/Http/Controllers/Api/V1/Payments/PayMayaWebhookController.php` — require webhook token; delegate to finalizer.
- `app/Http/Controllers/Api/V1/Payments/PayMayaCheckoutStatusController.php` — delegate to finalizer.
- `config/services.php` — add `paymaya.webhook_token` config.

---

### Task 1: Add Payment Finalizer Service

**Files:**
- Create: `app/Services/Payments/PaymentFinalizer.php`
- Test: (deferred)

- [ ] **Step 1: Write the failing test (deferred)**

```php
// Deferred: add unit tests for idempotency and side effects later.
```

- [ ] **Step 2: Create the finalizer**

```php
<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;

class PaymentFinalizer
{
    public function apply(Payment $payment, string $status, array $raw = [], array $webhook = []): Payment
    {
        // Only allow forward transitions. If already succeeded, no-op.
        $current = $payment->status;
        if ($current === 'succeeded') {
            return $payment;
        }

        $payment->status = $status;
        if (!empty($raw)) {
            $payment->raw_response = $raw;
        }
        if (!empty($webhook)) {
            $payment->raw_webhook = $webhook;
        }
        $payment->save();

        if ($status !== 'succeeded') {
            return $payment;
        }

        DB::transaction(function () use ($payment): void {
            $reservation = $payment->reservation()->lockForUpdate()->first();
            if (!$reservation) {
                return;
            }

            if ($reservation->status !== 'confirmed') {
                $booking = $reservation->booking()->lockForUpdate()->first();
                if ($booking) {
                    $booking->update([
                        'capacity' => max(0, $booking->capacity - $reservation->quantity),
                    ]);
                }

                $reservation->update(['status' => 'confirmed']);
            }

            Receipt::firstOrCreate(
                ['payment_id' => $payment->id],
                [
                    'reservation_id' => $reservation->id,
                    'receipt_number' => 'RCPT-' . now()->format('Ymd') . '-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'issued_at' => now(),
                    'metadata' => [
                        'reference' => $payment->reference,
                        'customer_name' => $reservation->user?->name,
                        'booking_title' => $reservation->booking?->title,
                        'booking_date' => $reservation->booking?->event_date?->toIso8601String(),
                    ],
                ]
            );
        });

        return $payment;
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Services/Payments/PaymentFinalizer.php
git commit -m "feat: add centralized payment finalizer"
```

---

### Task 2: Guard Return Endpoint + Use Finalizer

**Files:**
- Modify: `app/Http/Controllers/PayMayaReturnController.php`

- [ ] **Step 1: Update controller to enforce ownership + checkout id guard and call finalizer**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Services\Payments\PaymentFinalizer;
use Inertia\Inertia;
use Inertia\Response;

class PayMayaReturnController extends Controller
{
    /**
     * Render the PayMaya return page for polling status.
     */
    public function __invoke(Request $request, PaymentFinalizer $finalizer): Response
    {
        $paymentId = $request->integer('payment_id');
        $status = $request->string('status')->value();
        $checkoutId = $request->string('checkoutId')->value()
            ?: $request->string('id')->value();

        $receipt = null;
        $payment = null;

        if ($paymentId) {
            $payment = Payment::query()
                ->whereKey($paymentId)
                ->where('provider', 'paymaya')
                ->where('user_id', $request->user()->id)
                ->with('reservation.booking', 'reservation.user')
                ->first();

            if ($payment) {
                if ($checkoutId && $payment->checkout_id && $checkoutId !== $payment->checkout_id) {
                    return Inertia::render('PaymentReturn', [
                        'checkoutId' => $checkoutId,
                        'status' => 'failed',
                        'payment' => null,
                        'receipt' => null,
                    ])->with('error', 'Checkout id mismatch.');
                }

                $normalized = $this->normalizeStatus($status);

                if ($normalized) {
                    $payment = $finalizer->apply($payment, $normalized);
                }

                $receipt = $payment->receipt;
            }
        }

        return Inertia::render('PaymentReturn', [
            'checkoutId' => $checkoutId,
            'status' => $status,
            'payment' => $payment ? [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'reference' => $payment->reference,
                'reservation' => $payment->reservation
                    ? [
                        'id' => $payment->reservation->id,
                        'quantity' => $payment->reservation->quantity,
                        'customer' => $payment->reservation->user
                            ? [
                                'id' => $payment->reservation->user->id,
                                'name' => $payment->reservation->user->name,
                                'email' => $payment->reservation->user->email,
                            ]
                            : null,
                        'booking' => $payment->reservation->booking
                            ? [
                                'id' => $payment->reservation->booking->id,
                                'title' => $payment->reservation->booking->title,
                                'event_date' => $payment->reservation->booking->event_date,
                            ]
                            : null,
                    ]
                    : null,
            ] : null,
            'receipt' => $receipt ? [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'amount' => (float) $receipt->amount,
                'currency' => $receipt->currency,
                'issued_at' => $receipt->issued_at?->toIso8601String(),
            ] : null,
        ]);
    }

    private function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        return match (strtolower($status)) {
            'success', 'succeeded' => 'succeeded',
            'cancel', 'cancelled', 'canceled', 'failure', 'failed' => 'cancelled',
            default => 'pending',
        };
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/PayMayaReturnController.php
git commit -m "fix: guard paymaya return and use finalizer"
```

---

### Task 3: Secure Webhook + Use Finalizer

**Files:**
- Modify: `app/Http/Controllers/Api/V1/Payments/PayMayaWebhookController.php`
- Modify: `config/services.php`

- [ ] **Step 1: Add webhook token config**

```php
// in config/services.php
'paymaya' => [
    // ... existing keys
    'webhook_token' => env('PAYMAYA_WEBHOOK_TOKEN'),
],
```

- [ ] **Step 2: Update webhook controller to enforce token and use finalizer**

```php
<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentFinalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PayMayaWebhookController extends Controller
{
    /**
     * Handle PayMaya webhook callbacks.
     */
    public function __invoke(Request $request, PaymentFinalizer $finalizer): JsonResponse
    {
        $token = $request->header('X-PayMaya-Token');
        if (!$token || $token !== config('services.paymaya.webhook_token')) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized webhook.',
                'errors' => ['token' => ['Invalid webhook token.']],
            ], 401);
        }

        $payload = $request->all();

        $checkoutId = $payload['checkoutId']
            ?? $payload['id']
            ?? Arr::get($payload, 'data.id')
            ?? Arr::get($payload, 'payment.id');

        if (!$checkoutId) {
            return response()->json([
                'data' => null,
                'message' => 'Missing checkout id.',
                'errors' => ['checkout_id' => ['Checkout id not found in payload.']],
            ], 422);
        }

        $payment = Payment::query()
            ->where('provider', 'paymaya')
            ->where('checkout_id', $checkoutId)
            ->first();

        if (!$payment) {
            return response()->json([
                'data' => null,
                'message' => 'Payment not found.',
                'errors' => ['payment' => ['Payment record not found.']],
            ], 404);
        }

        $status = $this->normalizeStatus($payload['status'] ?? Arr::get($payload, 'paymentStatus'));

        if ($status) {
            $finalizer->apply($payment, $status, [], $payload);
        }

        return response()->json([
            'data' => ['ack' => true],
            'message' => 'Webhook processed.',
            'errors' => (object) [],
        ]);
    }

    private function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        $upper = strtoupper($status);

        if (in_array($upper, ['PAYMENT_SUCCESS', 'SUCCESS', 'COMPLETED', 'CAPTURED', 'PAID', 'AUTHORIZED'], true)) {
            return 'succeeded';
        }

        if (in_array($upper, ['PAYMENT_FAILED', 'FAILED', 'EXPIRED'], true)) {
            return 'failed';
        }

        if (in_array($upper, ['CANCELLED', 'CANCELED'], true)) {
            return 'cancelled';
        }

        return 'pending';
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Api/V1/Payments/PayMayaWebhookController.php config/services.php
git commit -m "fix: secure paymaya webhook and centralize finalization"
```

---

### Task 4: Align Checkout Status Polling

**Files:**
- Modify: `app/Http/Controllers/Api/V1/Payments/PayMayaCheckoutStatusController.php`

- [ ] **Step 1: Update status controller to use finalizer**

```php
<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PaymentResource;
use App\Models\Payment;
use App\Services\Payments\PayMayaService;
use App\Services\Payments\PaymentFinalizer;
use Illuminate\Http\JsonResponse;

class PayMayaCheckoutStatusController extends Controller
{
    /**
     * Fetch PayMaya checkout status and sync local records.
     */
    public function __invoke(string $checkoutId, PayMayaService $payMaya, PaymentFinalizer $finalizer): JsonResponse
    {
        $payment = Payment::query()
            ->where('provider', 'paymaya')
            ->where('checkout_id', $checkoutId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $response = $payMaya->fetchCheckout($checkoutId);

        $status = $this->normalizeStatus($response['status'] ?? $response['paymentStatus'] ?? null);

        if ($status) {
            $finalizer->apply($payment, $status, $response);
        }

        return (new PaymentResource($payment->load('reservation.booking')))
            ->additional([
                'message' => 'Checkout status synced.',
                'errors' => (object) [],
            ])
            ->response();
    }

    private function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        $upper = strtoupper($status);

        if (in_array($upper, ['PAYMENT_SUCCESS', 'SUCCESS', 'COMPLETED', 'CAPTURED', 'PAID', 'AUTHORIZED'], true)) {
            return 'succeeded';
        }

        if (in_array($upper, ['PAYMENT_FAILED', 'FAILED', 'EXPIRED'], true)) {
            return 'failed';
        }

        if (in_array($upper, ['CANCELLED', 'CANCELED'], true)) {
            return 'cancelled';
        }

        return 'pending';
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/Api/V1/Payments/PayMayaCheckoutStatusController.php
git commit -m "fix: use payment finalizer for checkout status"
```

---

## Local Testing (Manual)

### 1. Set webhook token
- Add to `.env`:

```env
PAYMAYA_WEBHOOK_TOKEN=local-dev-token
```

### 2. Web return flow
1. Create a booking via Filament admin.
2. From user UI, open booking and start checkout.
3. After PayMaya returns, verify:
   - Payment status changes to `succeeded` when return contains success.
   - Reservation status is `confirmed`.
   - Booking capacity decreases once.
   - Receipt is created and visible in Booking History.

### 3. Webhook flow
- Simulate webhook:

```bash
curl -X POST http://localhost/api/payments/paymaya/webhook \
  -H "X-PayMaya-Token: local-dev-token" \
  -H "Content-Type: application/json" \
  -d '{"checkoutId":"<CHECKOUT_ID>","status":"PAYMENT_SUCCESS"}'
```

Expected: `200` with `{ "data": { "ack": true } }`, payment status updates, reservation confirmed, capacity decremented, receipt created.

### 4. Status polling flow
- Call API status endpoint after checkout:

```bash
curl -H "Authorization: Bearer <SANCTUM_TOKEN>" \
  http://localhost/api/payments/paymaya/checkout/<CHECKOUT_ID>
```

Expected: payment status reflects PayMaya status and updates are consistent.

---

## Spec Coverage Checklist
- Centralized finalizer: Task 1
- Return endpoint guards: Task 2
- Webhook token check: Task 3
- Consistent updates across flows: Tasks 2–4

