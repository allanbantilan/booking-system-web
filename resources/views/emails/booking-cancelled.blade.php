<p>Hi {{ $reservation->user?->name }},</p>

<p>Your booking for <strong>{{ $reservation->booking?->title }}</strong> has been cancelled.</p>

<p>If a refund applies, it will be processed separately. Questions? Just reply to this email.</p>

<p>{{ config('app.name') }}</p>
