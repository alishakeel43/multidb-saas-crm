<?php

namespace Webkul\Booking\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Booking\Models\Booking;
use Webkul\Booking\Models\BookingAvailability;
use Webkul\Booking\Repositories\BookingRepository;
use Webkul\Booking\Repositories\BookingServiceRepository;
use Webkul\Booking\States\Cancelled;
use Webkul\Booking\States\Confirmed;
use Webkul\Booking\States\Pending;

class BookingServiceManager
{
    /**
     * Soft default when a staff member has no availability rows yet (Mon–Fri 09:00–17:00).
     */
    public const DEFAULT_WEEKDAYS = [1, 2, 3, 4, 5];

    public const DEFAULT_START = '09:00:00';

    public const DEFAULT_END = '17:00:00';

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly BookingServiceRepository $bookingServiceRepository,
    ) {}

    public function book(array $payload): Booking
    {
        $service = $this->bookingServiceRepository->findOrFail($payload['booking_service_id']);

        if (! $service->is_active) {
            throw new RuntimeException(trans('booking::app.messages.service-inactive'));
        }

        $userId = (int) ($payload['user_id'] ?? 0);

        if ($userId <= 0) {
            throw new InvalidArgumentException(trans('booking::app.messages.staff-required'));
        }

        $startsAt = Carbon::parse($payload['starts_at']);
        $endsAt = (clone $startsAt)->addMinutes((int) $service->duration_minutes);
        $blockedUntil = (clone $endsAt)->addMinutes((int) ($service->buffer_minutes ?? 0));

        $availabilityReason = $this->availabilityFailureReason($userId, $startsAt, $endsAt);

        if ($availabilityReason !== null) {
            throw new InvalidArgumentException($availabilityReason);
        }

        if ($this->hasOverlap($userId, $startsAt, $blockedUntil)) {
            throw new InvalidArgumentException(trans('booking::app.messages.slot-overlap'));
        }

        return DB::transaction(function () use ($payload, $service, $userId, $startsAt, $endsAt): Booking {
            $booking = $this->bookingRepository->create([
                'booking_service_id' => $service->id,
                'user_id' => $userId,
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
                'user_id' => $userId,
            ]);

            $this->bookingRepository->update(['activity_id' => $activity->id], $booking->id);
            $booking = $this->bookingRepository->find($booking->id);

            if (! empty($payload['lead_id'])) {
                $activity->leads()->attach($payload['lead_id']);
            }

            if (! empty($payload['person_id'])) {
                $activity->persons()->attach($payload['person_id']);
            }

            return $booking->fresh(['service']);
        });
    }

    public function isSlotAvailable(int $userId, Carbon $startsAt, Carbon $endsAt, int $bufferMinutes = 0): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if ($this->availabilityFailureReason($userId, $startsAt, $endsAt) !== null) {
            return false;
        }

        $blockedUntil = (clone $endsAt)->addMinutes(max(0, $bufferMinutes));

        return ! $this->hasOverlap($userId, $startsAt, $blockedUntil);
    }

    /**
     * Active availability windows for a staff member, or soft Mon–Fri defaults when none exist.
     *
     * @return Collection<int, object{weekday: int, starts_at: string, ends_at: string}>
     */
    public function windowsForUser(int $userId): Collection
    {
        $rows = BookingAvailability::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->get(['weekday', 'starts_at', 'ends_at']);

        if ($rows->isNotEmpty()) {
            return $rows->map(fn (BookingAvailability $row) => (object) [
                'weekday' => (int) $row->weekday,
                'starts_at' => $this->normalizeTime($row->starts_at),
                'ends_at' => $this->normalizeTime($row->ends_at),
            ]);
        }

        return collect(self::DEFAULT_WEEKDAYS)->map(fn (int $weekday) => (object) [
            'weekday' => $weekday,
            'starts_at' => self::DEFAULT_START,
            'ends_at' => self::DEFAULT_END,
        ]);
    }

    /**
     * Persist Mon–Fri 09:00–17:00 for a staff member (skips weekdays that already exist).
     */
    public function seedDefaultWeek(int $userId): int
    {
        $created = 0;

        foreach (self::DEFAULT_WEEKDAYS as $weekday) {
            $exists = BookingAvailability::query()
                ->where('user_id', $userId)
                ->where('weekday', $weekday)
                ->exists();

            if ($exists) {
                continue;
            }

            BookingAvailability::query()->create([
                'user_id' => $userId,
                'weekday' => $weekday,
                'starts_at' => self::DEFAULT_START,
                'ends_at' => self::DEFAULT_END,
                'is_active' => true,
            ]);

            $created++;
        }

        return $created;
    }

    protected function availabilityFailureReason(int $userId, Carbon $startsAt, Carbon $endsAt): ?string
    {
        $windows = $this->windowsForUser($userId)
            ->where('weekday', $startsAt->dayOfWeek)
            ->values();

        if ($windows->isEmpty()) {
            return trans('booking::app.messages.slot-outside-availability');
        }

        $fits = $windows->contains(function (object $row) use ($startsAt, $endsAt): bool {
            $dayStart = Carbon::parse($startsAt->toDateString().' '.$row->starts_at);
            $dayEnd = Carbon::parse($startsAt->toDateString().' '.$row->ends_at);

            return $startsAt->greaterThanOrEqualTo($dayStart) && $endsAt->lessThanOrEqualTo($dayEnd);
        });

        return $fits ? null : trans('booking::app.messages.slot-outside-availability');
    }

    protected function hasOverlap(int $userId, Carbon $startsAt, Carbon $blockedUntil): bool
    {
        return Booking::query()
            ->where('user_id', $userId)
            ->where('status', '!=', Cancelled::getMorphClass())
            ->where('starts_at', '<', $blockedUntil)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }

    protected function normalizeTime(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('H:i:s');
        }

        $raw = (string) $value;

        if (preg_match('/^\d{2}:\d{2}$/', $raw)) {
            return $raw.':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}/', $raw, $matches)) {
            return $matches[0];
        }

        return Carbon::parse($raw)->format('H:i:s');
    }
}
