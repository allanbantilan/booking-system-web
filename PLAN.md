# Production Readiness Plan — booking-system-web

## What this plan is

A phased roadmap to take the booking-system-web from its current local/sandbox state to a
production-grade deployment. The PayMaya integration stays on the sandbox API for now.

## Current state

- Laravel 11 + Inertia.js + Vue 3 + Filament admin panel
- PayMaya checkout, webhook, and return flow (sandbox keys)
- MySQL via Laravel Sail (docker-based dev)
- Session: file driver; Cache: database driver; Queue: database driver
- Mail: Mailpit (local only)
- File storage: local disk (Spatie Media Library)
- APP_DEBUG=true, APP_ENV=local
- No CI/CD pipeline
- No error monitoring
- No HTTPS / production server config
- Basic test suite (Feature tests for auth, PayMaya flows)

## Goals

Ship a production deployment where:
1. Real users can register, browse bookings, pay via PayMaya sandbox, and receive receipts
2. Admins can manage bookings, users, and merchant requests via Filament
3. The app is secure, observable, and recoverable after failure
4. Secrets are managed properly and not committed to git

## Out of scope (this plan)

- Switching PayMaya from sandbox to live (waiting on API access)
- Mobile app / React Native frontend
- Multi-tenancy or white-labeling
- Advanced analytics beyond existing stats widgets

---

## Phase 1: Security hardening

### 1.1 Environment & secrets
- Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://yourdomain.com`
- Rotate `APP_KEY` (generate new, never reuse dev key in prod)
- Move all secrets to server environment variables or a secrets manager (never commit `.env` with real keys)
- Add `.env` to `.gitignore` (already present, verify)
- Create `.env.production.example` documenting all required vars with placeholder values

### 1.2 Security headers
- Add `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` headers via middleware
- Enable HSTS once HTTPS is in place
- Verify CSRF protection is active on all state-changing routes (Laravel default: yes, but confirm SPA/Inertia setup)

### 1.3 PayMaya webhook verification
- Verify the webhook signature in `PayMayaWebhookController` — currently unknown if signature check exists
- Log all webhook events for audit trail
- Return correct HTTP status codes so PayMaya retries on failure

### 1.4 Input validation audit
- Audit all `FormRequest` classes for missing rules
- Ensure `booking_id`, `quantity`, `nights` are validated as positive integers in `CreatePayMayaCheckoutRequest`
- Add server-side validation for check-in/check-out date ordering

### 1.5 Rate limiting
- Login: already throttled `5,1` — verify this is sufficient
- Register: already throttled `3,1` — verify
- PayMaya checkout: add rate limiting per user
- API endpoints: add global rate limiting

### 1.6 Authorization
- Audit all controllers for missing authorization checks
- Confirm `ReservationController::cancel` verifies the reservation belongs to `$request->user()`
- Confirm `PayMayaReturnController` already scopes payment by user (it does — verify)
- Add policy classes for Booking, Reservation, Payment models

---

## Phase 2: Infrastructure & deployment

### 2.1 Server setup
- Choose hosting: VPS (DigitalOcean/Linode/AWS EC2) or PaaS (Render/Railway/Fly.io)
- PHP 8.3+, Nginx, MySQL 8.0+
- Set up SSL via Let's Encrypt (certbot)
- Point `APP_URL` to HTTPS domain

### 2.2 Docker / deployment pipeline
- The app already uses Sail for dev — decide: keep Docker for prod or use bare-metal PHP
- Recommended: use a `Dockerfile` for prod with multi-stage build (Node build → PHP serve)
- Set up GitHub Actions CI:
  - Run `php artisan test` on every push
  - Run `npm run build` to catch frontend build errors
  - Deploy on merge to `main`

### 2.3 Database
- Use a managed MySQL instance (AWS RDS, PlanetScale, or DigitalOcean Managed DB)
- Enable automated daily backups with 7-day retention
- Run `php artisan migrate --force` as part of deploy (not manually)
- Review indexes: `reservations.user_id`, `payments.reservation_id`, `payments.checkout_id` — add if missing

### 2.4 Queue workers
- Switch `QUEUE_CONNECTION=redis` (or keep `database` if Redis not available — database is fine for low volume)
- Set up Supervisor to keep `php artisan queue:work` running on the server
- Add a queue failure table: `php artisan queue:failed-table && php artisan migrate`
- Monitor failed jobs — alert when queue depth grows

### 2.5 Cache & sessions
- Switch `CACHE_STORE=redis` and `SESSION_DRIVER=redis` for production
- If Redis not available, `SESSION_DRIVER=database` is acceptable but slower
- Run `php artisan session:table && php artisan migrate` if using database sessions

### 2.6 File storage
- Switch `FILESYSTEM_DISK=s3` for production (Spatie Media Library supports S3)
- Set `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_DEFAULT_REGION`
- Alternative: DigitalOcean Spaces (S3-compatible)
- Update `.env.production.example` with S3 vars

---

## Phase 3: Observability & reliability

### 3.1 Error monitoring
- Integrate Sentry (free tier sufficient to start): `composer require sentry/sentry-laravel`
- Configure `SENTRY_LARAVEL_DSN` in env
- Sentry catches unhandled exceptions, queue failures, and slow queries

### 3.2 Logging
- Set `LOG_CHANNEL=stack`, `LOG_LEVEL=error` in production
- Add structured logging for payment events (checkout created, webhook received, payment finalized)
- Rotate logs: `LOG_STACK=daily` with 14-day retention

### 3.3 Health check endpoint
- Add `GET /health` route returning `{"status":"ok","db":"ok","queue":"ok"}` — used by load balancer / uptime monitor
- Check DB connectivity and queue worker heartbeat in the response

### 3.4 Uptime monitoring
- Set up UptimeRobot or Better Uptime (free) to ping `/health` every 5 minutes
- Alert on Slack/email when down

### 3.5 Payment audit trail
- Ensure every PayMaya event (checkout created, webhook received, status polled) is logged with `payment_id`, `checkout_id`, `status`, `timestamp`
- Store raw webhook payloads in a `webhook_logs` table for debugging

---

## Phase 4: Frontend & performance

### 4.1 Production asset build
- Run `npm run build` (Vite production build) — minified, hashed filenames
- Verify `vite.config.js` is configured for production (no HMR, correct manifest path)
- Remove `resources/js/Pages/Test.vue` (dev-only page)

### 4.2 Caching
- Enable Laravel route caching: `php artisan route:cache`
- Enable config caching: `php artisan config:cache`
- Enable view caching: `php artisan view:cache`
- Add these to deploy script

### 4.3 Image optimization
- Spatie Media Library: configure conversions for thumbnail sizes
- Serve images via CDN (S3 + CloudFront, or DigitalOcean Spaces + CDN)

### 4.4 Remove dev artifacts
- Remove `resources/js/Pages/Test.vue` and its route if any
- Set `APP_DEBUG=false` to suppress stack traces in error pages
- Remove any `dd()`, `dump()`, `ray()` calls from production code

---

## Phase 5: Email

### 5.1 Transactional email provider
- Replace Mailpit with a real SMTP provider: Mailgun, Postmark, or AWS SES
- Configure `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- Set `MAIL_FROM_ADDRESS` to a real domain email (e.g., `noreply@yourdomain.com`)
- Verify SPF/DKIM records for the sending domain to avoid spam folders

### 5.2 Email queue
- Ensure `MerchantCredentialsMail` and `MerchantRequestRejectedMail` are dispatched via queue (`->queue()` not `->send()`)
- Test both emails end-to-end in staging before going live

---

## Phase 6: Testing & deploy checklist

### 6.1 Test coverage gaps
- Add tests for `ReservationController::cancel` authorization
- Add tests for `MerchantAccountController::store`
- Add tests for the Filament admin actions (approve/reject merchant requests)
- Existing PayMaya tests look good — ensure they run in CI

### 6.2 Pre-launch checklist
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_KEY` rotated and securely stored
- [ ] HTTPS configured with valid cert
- [ ] Database backups verified (restore test)
- [ ] Queue worker running via Supervisor
- [ ] Failed jobs table created
- [ ] Redis configured for cache + sessions
- [ ] S3 configured for file storage
- [ ] Sentry DSN configured
- [ ] Real SMTP configured and tested
- [ ] `php artisan route:cache && config:cache && view:cache` run
- [ ] `npm run build` output committed / built in CI
- [ ] `php artisan migrate --force` runs on deploy
- [ ] Health check endpoint responds 200
- [ ] Uptime monitor configured
- [ ] PayMaya webhook URL updated in PayMaya dashboard to production URL
- [ ] PayMaya sandbox keys confirmed working end-to-end
- [ ] Security headers verified with securityheaders.com

---

## Phase 7: Critical backend bugs (fix before launch)

These are data integrity and business logic bugs found by codebase audit. Fix these before any real users.

### 7.1 Double capacity decrement (DATA INTEGRITY BUG)
- **Files**: `ReservationController.php` (~line 59) and `PaymentFinalizer.php` (~line 39)
- When a user pays via PayMaya, booking capacity is decremented TWICE — once in `ReservationController::store()` and again in `PaymentFinalizer::apply()`. This silently oversells capacity.
- **Fix**: Remove the capacity decrement from `ReservationController::store()`. Only `PaymentFinalizer::apply()` should reduce capacity (when payment succeeds).

### 7.2 No availability check before reservation
- **File**: `ReservationController.php` (~line 30)
- Validates `quantity > capacity` but does not block when capacity is already 0. A race condition allows overbooking.
- **Fix**: Lock the booking row for update, re-read capacity after lock, throw `ValidationException` if `$booking->capacity < $quantity`.

### 7.3 Race condition in PaymentFinalizer
- **File**: `PaymentFinalizer.php` (~line 13)
- Two simultaneous PayMaya webhooks can both pass the `if ($payment->status === 'succeeded') return` guard before either writes the status, creating duplicate receipts.
- **Fix**: Use `Payment::lockForUpdate()->find($payment->id)` at the start of `apply()`.

### 7.4 No refund logic on reservation cancellation
- **File**: `ReservationController.php` (cancel method)
- Cancelling a reservation deletes it without checking or refunding an associated succeeded payment.
- **Fix**: Before deleting, check `$reservation->payment?->status === 'succeeded'` — mark payment as `refunded` and add a `refunded_at` column. Wire up PayMaya refund API when available.

### 7.5 No webhook signature validation
- **File**: `PayMayaWebhookController.php`
- Only checks a token header. No HMAC signature validation on the payload body. A leaked token allows fabricated payments.
- **Fix**: Implement HMAC signature verification using the PayMaya secret key on the raw request body.

### 7.6 Hardcoded 3-day cancellation window
- **File**: `ReservationController.php` (~line 140)
- `$reservation->created_at->addDays(3)` is hardcoded.
- **Fix**: Move to `config('booking.cancellation_window_days', 3)` in a new `config/booking.php`.

### 7.7 Duplicate status normalizer code
- **Files**: `PayMayaCheckoutStatusController.php` and `PayMayaWebhookController.php` both have identical `normalizeStatus()` methods.
- **Fix**: Extract to `App\Services\Payments\PaymentStatusNormalizer`.

### 7.8 Missing database indexes
- **Fix**: Add migration with:
  - `reservations` → index on `user_id`
  - `bookings` → index on `created_by`, `category_id`
  - `payments` → index on `checkout_id`, `reservation_id`

### 7.9 No pending reservation expiry
- Pending reservations (awaiting payment) can exist forever, holding capacity slots permanently.
- **Fix**: Add a scheduled queue job that deletes pending reservations older than 24 hours and restores the booking capacity.

### 7.10 No confirmation email after payment
- Users receive no email after a successful payment or reservation.
- **Fix**: Dispatch `ReservationCreated` and `PaymentSucceeded` events in `PaymentFinalizer::apply()`, each with a queued Mailable.

---

## Phase 8: UI/UX improvements

### 8.1 Confirmation dialog before cancel
- **File**: `BookingHistory.vue`
- Cancel button fires immediately without a confirmation dialog. Users can accidentally cancel.
- **Fix**: Add a modal confirmation: "Are you sure you want to cancel this reservation?"

### 8.2 Inline form validation on BookingShow
- **File**: `BookingShow.vue`
- Quantity and date inputs don't show why "Reserve Now" is disabled or what the server rejected.
- **Fix**: Show inline error messages under each input. Add `max="booking.capacity"` to quantity input client-side.

### 8.3 Sold-out visual state
- **File**: `Bookings.vue`
- Fully booked items show "Slots left: 0" but the booking card looks identical to available ones.
- **Fix**: Add a "Sold Out" badge, grey out the card, and disable the action button when capacity is 0.

### 8.4 Loading spinner during checkout
- **File**: `BookingShow.vue`
- `isProcessing` changes button text but shows no visual loader.
- **Fix**: Add a spinner icon inside the button while `isProcessing` is true.

### 8.5 Empty state CTA on BookingHistory
- **File**: `BookingHistory.vue`
- Empty state says "No booking history yet." with no action.
- **Fix**: Add a "Browse Available Bookings" button linking to `bookings.index`.

### 8.6 Mobile layout for booking history table
- **File**: `BookingHistory.vue`
- 7-column table is unreadable on mobile.
- **Fix**: Replace with card layout on small screens using Tailwind `hidden sm:table-cell` / responsive card rows.

### 8.7 Booking context on PaymentReturn page
- **File**: `PaymentReturn.vue`
- After redirect back from PayMaya, user sees payment status but no booking details (what they paid for, quantity, price).
- **Fix**: Pass and display booking title, quantity, and amount on this page.

### 8.8 Available-only filter on Bookings list
- **File**: `Bookings.vue`
- No way to filter out fully-booked items.
- **Fix**: Add a "Show available only" toggle that filters `capacity > 0`.

### 8.9 Payment retry on failed checkout
- **File**: `PaymentReturn.vue`
- When payment fails, user has no "Try Again" button — must navigate back manually.
- **Fix**: Add a "Retry Payment" button on the failed state that returns to `bookings.show`.

### 8.10 Success/error toast notifications
- Flash messages are shown in small grey text that users often miss.
- **Fix**: Integrate a toast/notification component (e.g. `vue-toastification`) triggered by Inertia flash data.

---

## Phase 9: Additional bugs found by plan-eng-review

### 9.1 Direct-reserve route bypasses payment (SECURITY BUG)
- **File**: `ReservationController.php:50-61`, route: `POST /bookings/{id}/reserve`
- Any authenticated user can call this route directly and get a `confirmed` reservation with zero payment. The frontend doesn't use this route (it uses the PayMaya path), but the route is publicly accessible.
- **Fix**: Either remove the route entirely (if all bookings require payment) or gate it behind an admin/merchant role using a policy class.

### 9.2 Overbooking window at checkout creation
- **File**: `PayMayaCheckoutFlow.php:27-35`
- The booking row is locked and capacity is checked, but NOT decremented at checkout time. Two simultaneous users can both see capacity=1, both get checkout URLs, and both pay. The second payment to finalize decrements capacity from 0 to 0 (max guard), silently overbooking.
- **Fix**: Decrement capacity when creating the pending reservation (as a capacity "hold"). Restore the hold if the PayMaya API call fails (line 113) or if the pending reservation expires (24h job in 7.9).

### 9.3 Payment status saved outside transaction (SPLIT-BRAIN BUG)
- **File**: `PaymentFinalizer.php:17-24` vs. `PaymentFinalizer.php:30`
- `$payment->save()` at line 24 runs BEFORE the `DB::transaction` at line 30. If the transaction rolls back (deadlock, reservation not found), the payment row shows `status='succeeded'` permanently. On webhook retry, the idempotency guard at line 13 (`if ($payment->status === 'succeeded') return`) short-circuits — the reservation will NEVER be confirmed.
- **Fix**: Move all writes (payment save + capacity decrement + receipt + reservation confirm) inside a single `DB::transaction` with `Payment::lockForUpdate()` at the start.

### 9.4 Orphaned payment record after checkout API failure
- **File**: `PayMayaCheckoutFlow.php:107-115`
- When the PayMaya API call throws, the reservation is deleted but the payment record is left with `status='failed'` and a broken `reservation_id` FK. If PayMaya later sends a late/duplicate webhook for that `checkout_id`, `PaymentFinalizer` finds the payment, calls `$payment->reservation()->lockForUpdate()->first()` → gets null → silently returns. The webhook is dropped with no error logged, no retry possible.
- **Fix**: Also delete (or null out `checkout_id` on) the payment record at line 108 before re-throwing, so no dangling payment can be matched by a future webhook.

### 9.5 Cancel uses stale reservation quantity
- **File**: `ReservationController.php:69-93`
- `$reservation` is read outside the transaction at line 69-72. Inside the transaction, the booking row is locked but `$reservation` is never re-fetched. The capacity restoration at line 86 uses the potentially-stale `$reservation->quantity`. An admin correction between the outer read and the inner transaction could cause incorrect capacity restoration.
- **Fix**: Add `$reservation = Reservation::lockForUpdate()->findOrFail($reservationId)` inside the transaction.

### 9.6 normalizeStatus returns 'pending' for unknown webhook statuses
- **Files**: `PayMayaWebhookController.php:89`, `PayMayaCheckoutStatusController.php:61`
- Both `normalizeStatus()` methods return `'pending'` as catch-all for unrecognized status strings. If PayMaya sends `VOIDED`, `REFUNDED`, `AUTHORIZING`, or any future status, `PaymentFinalizer::apply($payment, 'pending')` is called. Since the guard only checks `if status === 'succeeded'`, a succeeded payment can be DOWNGRADED back to pending by any unknown webhook event.
- **Fix**: Return `null` for unrecognized statuses (skip processing) instead of defaulting to `'pending'`.

### 9.7 Cancel should update reservation status, not delete the row
- **File**: `ReservationController.php:91`
- `$reservation->delete()` permanently removes the row, breaking booking history and orphaning the payment record.
- **Fix**: `$reservation->update(['status' => 'cancelled', 'cancelled_at' => now()])`. Add `cancelled_at` column via migration. Show cancelled reservations in history with a "Cancelled" badge.

### 9.8 normalizeStatus duplicated in two controllers
- **Files**: `PayMayaWebhookController.php:69-90`, `PayMayaCheckoutStatusController.php:41-62`
- Identical private methods in two classes. Any new PayMaya status string must be updated in two places.
- **Fix**: Extract to `App\Services\Payments\PaymentStatusNormalizer::normalize(string $status): ?string`.

---

## GSTACK REVIEW REPORT

**Reviewed:** 2026-06-14 | **Branch:** main | **Reviewer:** plan-eng-review skill  
**Mode:** Builder | **Design doc:** `~/.gstack/projects/allanbantilan-booking-system-web/20260614-231828-main-design-production-readiness.md`

### Architecture findings (A1–A5)

| ID | Finding | Decision |
|----|---------|----------|
| A1 | `POST /bookings/{id}/reserve` creates confirmed reservations with zero payment — any authenticated user can bypass PayMaya | **Fix: gate or remove** |
| A2 | `PaymentFinalizer::apply()` idempotency check has no `lockForUpdate` — concurrent webhooks both pass the guard | **Fix: add lockForUpdate** |
| A3 | Payment `status='succeeded'` is saved BEFORE the transaction — rollback leaves succeeded payment with unconfirmed reservation, and webhook retry is permanently short-circuited | **Fix: move into transaction** |
| A4 | Webhook verifies token header only, no HMAC signature on payload body | **Defer (sandbox only)** |
| A5 | No capacity hold at checkout creation — two simultaneous users can both pay for the last slot | **Fix: hold at checkout, restore on failure/expiry** |

### Code quality findings (CQ1–CQ2)

| ID | Finding | Decision |
|----|---------|----------|
| CQ1 | `cancel()` hard-deletes reservation row — breaks history, orphans payment | **Fix: update status to 'cancelled'** |
| CQ2 | `normalizeStatus()` copy-pasted in two controllers | **Fix: extract to PaymentStatusNormalizer** |

### Test findings (T1)

| ID | Finding | Decision |
|----|---------|----------|
| T1 | Zero tests for `ReservationController` (cancel, store, authorization) | **Write tests first, then implement fixes** |

### Performance findings (P1)

| ID | Finding | Decision |
|----|---------|----------|
| P1 | Missing indexes: `reservations.user_id`, `payments.checkout_id`, `bookings.category_id` | **Add now via migration** |

### Outside voice additions (from independent review)

| ID | Finding | Status |
|----|---------|--------|
| OV1 | Orphaned payment after checkout API failure — late webhook silently dropped | Added as **Phase 9.4** |
| OV2 | Cancel uses stale `$reservation->quantity` outside transaction | Added as **Phase 9.5** |
| OV3 | `normalizeStatus` returns `'pending'` for unknown statuses — can downgrade a succeeded payment | Added as **Phase 9.6** |

### Implementation order

Ship P0 group first (before any real users):

1. Write `ReservationController` tests (T1)
2. Fix A3 + A2 together: move `PaymentFinalizer::apply()` into a single atomic transaction with `lockForUpdate`
3. Fix A1: remove or gate `reservations.store` route
4. Fix A5: add capacity hold in `PayMayaCheckoutFlow`, restore on failure
5. Fix CQ1: cancel → status update instead of delete (requires 9.5 stale-quantity fix)
6. Fix 9.4: clean up orphaned payment on checkout failure
7. Fix 9.6: return null for unknown webhook statuses
8. Fix CQ2 + P1: extract PaymentStatusNormalizer, add DB indexes migration

---

## Key constraints

- PayMaya stays on sandbox API (`PAYMAYA_BASE_URL=https://pg-sandbox.paymaya.com`) until real API access is granted
- No live payments will be processed — all payment flows are test-only
- The app should be fully functional for real users except for actual money movement
