<p>Hi {{ $reservation->user?->name }},</p>

<p>Your booking for <strong>{{ $reservation->booking?->title }}</strong> is confirmed.</p>

<ul>
    <li>Quantity: {{ $reservation->quantity }}</li>
    @if ($reservation->check_in_date)
        <li>Check-in: {{ $reservation->check_in_date->toFormattedDateString() }}</li>
    @endif
    @if ($reservation->check_out_date)
        <li>Check-out: {{ $reservation->check_out_date->toFormattedDateString() }}</li>
    @endif
    <li>Total: {{ $reservation->total_price }}</li>
</ul>

<p>Thank you for booking with {{ config('app.name') }}.</p>
