This is a reminder for your upcoming booking.

Service: {{ $serviceName }}
When: {{ $startsLabel }}
@if ($booking->guest_name)
Guest: {{ $booking->guest_name }}
@endif
@if ($booking->user?->name)
Staff: {{ $booking->user->name }}
@endif
@if ($booking->notes)
Notes: {{ $booking->notes }}
@endif
