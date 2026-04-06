# Rental/Accommodation Date Range Checkout Design

## Summary
Add server-validated check-in and check-out dates for rental and accommodation bookings. Replace the nights input with date pickers, compute stay length from the date range, and use that computed length for pricing. Other booking types (including event) continue without dates.

## Goals
- Capture `check_in_date` and `check_out_date` at checkout for rental/accommodation bookings.
- Validate dates on the server and persist them on reservations.
- Compute stay length from date range and use it for total price calculations.
- Keep non-rental/accommodation booking flows unchanged.

## Non-Goals
- Availability blocking, blackout calendars, or inventory by date.
- Enforcing `booking.event_date` as an upper bound.
- Changing pricing logic beyond replacing nights with computed stay length.

## Data Model
- Add nullable `check_in_date` and `check_out_date` (date) columns to `reservations`.
- Existing reservations remain valid with null dates.

## Validation Rules
Applied during PayMaya checkout creation:
- For booking types `rental` and `accommodation`:
  - `check_in_date` is required, valid date, must be today or later.
  - `check_out_date` is required, valid date, must be after `check_in_date`.
- For other booking types:
  - Dates are ignored and not required.

## Pricing & Stay Length
- Compute `stay_length` as the number of days between `check_in_date` and `check_out_date` (minimum 1 day because `check_out_date` must be after `check_in_date`).
- Use `stay_length` in the existing pricing logic where nights are currently used.
- Nights input remains only for booking types that still require a duration but are not rental/accommodation (if any). Otherwise, do not send nights for rental/accommodation.

## UI/UX
- In the booking details/checkout panel (`BookingShow` page), show date picker inputs for rental and accommodation types:
  - `Check-in` (date)
  - `Check-out` (date)
- Remove or hide the nights input for rental/accommodation.
- Display computed duration label (Days/Nights) based on booking type defaults.
- Live-update total price when dates change.

## API Payload
- Include `check_in_date` and `check_out_date` in the checkout request payload for rental/accommodation.
- Keep `quantity` and `booking_id` unchanged.
- Continue sending `nights` only for non-rental/accommodation types that require it.

## Error Handling
- Validation errors return field-specific messages for `check_in_date` and `check_out_date`.
- Checkout flow rejects invalid or missing dates for rental/accommodation.

## Testing
- Request validation tests:
  - Rental/accommodation: missing dates -> validation errors.
  - Rental/accommodation: `check_in_date` < today -> validation error.
  - Rental/accommodation: `check_out_date` <= `check_in_date` -> validation error.
  - Non-rental/accommodation: dates optional and ignored.
- Pricing test:
  - Given a date range, computed stay length is used in total price calculation.

## Rollout Notes
- Migration is additive and backward-compatible.
- No data backfill required.
