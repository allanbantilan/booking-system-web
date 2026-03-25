# Booking System (Web)

Minimal system overview and architecture.

## System Design

- Two interfaces:
    - Customer-facing web app (Inertia + Vue).
    - Admin backend (Filament) with a `merchant` role for companies managing rentable spaces.
- Merchant onboarding:
    - Users can request merchant access from their profile.
    - Requests are reviewed in the backend and approved/rejected by a super admin.
- Core domain:
    - Users (frontend customers and backend staff).
    - Bookings and reservations.
    - Payments and receipts.
    - Categories and media.
- Payments:
    - PayMaya API (sandbox) is used for payment processing.

## Architecture

- Backend: Laravel 12 with Jetstream/Fortify for auth and Spatie Permission for roles.
- Frontend: Vue 3 + Inertia.js served by Laravel.
- Admin: Filament panel for managing bookings, users, categories, media, and settings.
- Media: Spatie Media Library for file uploads and image associations.
- Dev/build: Vite + Tailwind CSS.
