<?php

namespace Webkul\Booking\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Booking\Models\Booking;
use Webkul\Booking\Models\BookingAvailability;
use Webkul\Booking\Repositories\BookingRepository;
use Webkul\Booking\Repositories\BookingServiceRepository;
use Webkul\Booking\States\Confirmed;
use Webkul\Booking\States\Pending;

class BookingServiceManager
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly BookingServiceRepository $bookingServiceRepository,
    ) {}

    public function book(array $payload): Booking
    {
        $service = $this->bookingServiceRepository->findOrFail($payload['booking_service_id']);

        if (! $service->is_active) {
            throw new RuntimeException('Booking service is inactive.');
        }

        $startsAt = Carbon::parse($payload['starts_at']);
        $endsAt = (clone $startsAt)->addMinutes($service->duration_minutes);

        if (! $this->isSlotAvailable((int) ($payload['user_id'] ?? 0), $startsAt, $endsAt)) {
            throw new InvalidArgumentException('Selected slot is outside staff availability or already booked.');
        }

        return DB::transaction(function () use ($payload, $service, $startsAt, $endsAt): Booking {
            $booking = $this->bookingRepository->create([
                'booking_service_id' => $service->id,
                'user_id' => $payload['user_id'] ?? null,
                'person_id' => $payload['person_id'] ?? null,
                'lead_id' => $payload['lead_id'] ?? null,
                'guest_name' => $payload['guest_name'] ?? null,
                'guest_email' => $payload['guest_email'] ?? null,
                'guest_phone' => $payload['guest_phone'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => ! empty($payload['auto_confirm']) ? Confirmed::class : Pending::class,
                'notes' => $payload['notes'] ?? null,
            ]);

            $activity = $this->activityRepository->create([
                'type' => 'meeting',
                'title' => $service->name.' booking',
                'comment' => $payload['notes'] ?? null,
                'schedule_from' => $startsAt,
                'schedule_to' => $endsAt,
                'user_id' => $payload['user_id'] ?? auth()->guard('user')->id(),
            ]);

            $this->bookingRepository->update(['activity_id' => $activity->id], $booking->id);
            $booking = $this->bookingRepository->find($booking->id);

            if (! empty($payload['lead_id'])) {
                $activity->leads()->attach($payload['lead_id']);
            }

            if (! empty($payload['person_id'])) {
                $activity->persons()->attach($payload['person_id']);
            }

            return $booking->fresh(['service', 'user', 'activity']);
        });
    }

    public function isSlotAvailable(int $userId, Carbon $startsAt, Carbon $endsAt): bool
    {
        if ($userId > 0) {
            $availability = BookingAvailability::query()
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->where('weekday', $startsAt->dayOfWeek)
                ->get()
                ->first(function (BookingAvailability $row) use ($startsAt, $endsAt): bool {
                    $dayStart = Carbon::parse($startsAt->toDateString().' '.$row->starts_at);
                    $dayEnd = Carbon::parse($startsAt->toDateString().' '.$row->ends_at);

                    return $startsAt->greaterThanOrEqualTo($dayStart) && $endsAt->lessThanOrEqualTo($dayEnd);
                });

            if (! $availability) {
                return false;
            }
        }

        $overlap = Booking::query()
            ->where('user_id', $userId ?: null)
            ->where('status', '!=', 'cancelled')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        return ! $overlap;
    }
}
