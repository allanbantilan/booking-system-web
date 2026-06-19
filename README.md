# BookBound Booking System

BookBound is a Laravel booking platform for listing, reserving, paying for, and managing rentable inventory. It serves two audiences from one codebase:

- Customers browse bookings, reserve dates or slots, pay through PayMaya, and track receipts or cancellation requests.
- Administrators and merchants manage inventory, reservations, users, roles, payments, receipts, cancellation requests, and merchant applications through a Filament back office.

The app is built as a modern Laravel + Vue/Inertia application: Laravel owns auth, data, payments, policies, queues, and admin tooling; Vue owns the customer-facing experience.

## What the app does

### Customer portal

- Public landing page for BookBound.
- User registration, login, email verification, profile management, two-factor auth, and API token management through Jetstream/Fortify/Sanctum.
- Booking catalogue with category, search, and price filters.
- Booking detail pages with images, location, description, capacity, pricing, amenities, merchant contact details, and date/quantity controls.
- PayMaya checkout flow for PHP payments.
- Booking history with reservation status, payment status, receipt details, cancellation state, and refund metadata.
- Cancellation request flow with eligibility rules and merchant review.

### Merchant and admin portal

The Filament admin panel provides operational screens for:

- Bookings and confirmed bookings.
- Reservations.
- Cancellation requests and refund handling.
- Categories and media.
- Frontend users.
- Backend users.
- Roles and permissions.
- Merchant account requests.
- Sales, retention, and frequent-booking widgets.

Customers can request merchant access from their profile. A super admin reviews the request, then approves or rejects it from the backend.

## Core workflows

### Booking and payment

1. Customer opens the booking catalogue.
2. Customer filters by category, keyword, or price.
3. Customer opens a booking and chooses quantity, dates, or nights depending on booking type.
4. App creates a pending reservation and payment record.
5. App starts PayMaya checkout and redirects customer to the PayMaya payment portal.
6. PayMaya return/webhook updates payment and reservation status.
7. Customer sees the result in booking history.

Capacity is held while checkout is pending so concurrent checkouts cannot overbook the same inventory.

### Cancellation

1. Customer requests cancellation from booking history.
2. App checks status, active requests, booking start date, and cutoff rules.
3. Merchant reviews the request in Filament.
4. Merchant approves, rejects, or processes refund status.
5. Approved cancellation restores capacity and marks refund as pending or processed.

### Merchant onboarding

1. Customer submits a merchant account request from profile.
2. Admin reviews request in Filament.
3. Approved merchants receive backend credentials.
4. Merchant uses the backend to manage their listings and related reservations.

## Tech stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Vue 3, Inertia.js 2, TypeScript, Vite 7
- **UI:** Tailwind CSS 4, PrimeVue 4, PrimeIcons
- **Admin:** Filament 5
- **Auth:** Laravel Jetstream, Fortify, Sanctum
- **Permissions:** Spatie Laravel Permission
- **Media:** Spatie Laravel Media Library
- **Payments:** PayMaya / Maya Checkout
- **Database:** MySQL 8.4 through Laravel Sail
- **Mail:** Mailpit in local Docker setup
- **Testing:** PHPUnit, Vitest, vue-tsc

## Main routes

| Area | Route | Purpose |
| --- | --- | --- |
| Public | `/` | Landing page |
| Auth | `/login`, `/register` | Customer auth |
| Customer | `/dashboard` | Account dashboard |
| Customer | `/bookings` | Booking catalogue |
| Customer | `/bookings/{booking}` | Booking details and checkout form |
| Customer | `/bookings/history` | Reservation and payment history |
| Customer | `/payments/paymaya/return` | PayMaya browser return |
| API | `/api/payments/paymaya/webhook` | PayMaya server webhook |
| Admin | `/admin` | Filament back office |

## Local setup

### Requirements

- PHP 8.2+
- Composer
- Node.js and npm
- Docker, if using Laravel Sail

### Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

### Run without Sail

```bash
php artisan serve
npm run dev
```

### Run with Sail

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm run dev
```

The Sail stack includes:

- Laravel app on `APP_PORT` or port `80`.
- Vite on `VITE_PORT` or port `5173`.
- MySQL on `FORWARD_DB_PORT` or port `3306`.
- Mailpit UI on `MAILPIT_PORT` or port `8025`.
- phpMyAdmin on `FORWARD_PHPMYADMIN_PORT` or port `8080`.

## Environment

Important `.env` values:

```env
APP_NAME=BookBound
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

PAYMAYA_BASE_URL=https://pg-sandbox.paymaya.com
PAYMAYA_PUBLIC_KEY=
PAYMAYA_SECRET_KEY=
PAYMAYA_MERCHANT_ID=
PAYMAYA_MERCHANT_NAME="${APP_NAME}"
PAYMAYA_REDIRECT_SUCCESS=
PAYMAYA_REDIRECT_CANCEL=
PAYMAYA_REDIRECT_FAILURE=

SEED_DEMO=false
SEED_EXTERNAL_IMAGES=false
```

Set the PayMaya keys before testing real checkout. Keep `SEED_DEMO=false` outside local/demo environments.

## Useful commands

```bash
# Laravel tests
composer test
php artisan test

# Frontend checks
npm run test:frontend
npm run typecheck
npm run build

# Code style
./vendor/bin/pint

# Sail shortcuts
make up
make down
make test
make npm-dev
make npm-build

# Swagger/OpenAPI generation
make swagger
```

## Project structure

```text
app/
  Filament/              Back-office resources, pages, widgets
  Http/Controllers/      Web and API controllers
  Models/                Domain models
  Services/              Payment and reservation services
database/
  migrations/            Schema history
  seeders/               Demo, user, role, category, booking data
resources/
  js/                    Vue/Inertia customer app
  css/                   App styles
routes/
  web.php                Browser routes
  api.php                API/webhook routes
tests/
  Feature/               Laravel feature tests
  Unit/                  Laravel unit tests
  frontend/              Vitest tests
```

## Domain model

- **User:** customer account for booking and payment.
- **BackendUser:** Filament/admin account for staff and merchants.
- **Booking:** bookable inventory item with type, capacity, price, media, category, amenities, and merchant owner.
- **Category:** groups bookings and controls display metadata.
- **Reservation:** customer hold or confirmed booking.
- **Payment:** PayMaya checkout/payment state for a reservation.
- **Receipt:** issued proof of completed reservation/payment.
- **MerchantRequest:** customer request to become a merchant.
- **ReservationCancellationRequest:** cancellation review and refund workflow.

## Booking types

The app supports different booking behavior by type:

- **Event:** quantity-based booking.
- **Accommodation:** date-range booking using nights.
- **Rental:** date-range booking using days.
- **Service:** slot-based booking.
- **Package:** package-based booking.

Types affect labels, date requirements, total calculation, and capacity usage.

## Testing notes

Run backend and frontend checks before shipping changes:

```bash
php artisan test
npm run test:frontend
npm run typecheck
npm run build
```

Payment work should include PayMaya checkout, return, and webhook tests. Cancellation work should include eligibility, merchant approval/rejection, capacity restoration, and refund status tests.
