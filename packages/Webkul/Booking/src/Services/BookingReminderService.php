<?php

namespace Webkul\Booking\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\Booking\Mail\BookingReminderMail;
use Webkul\Booking\Models\Booking;
use Webkul\Booking\States\Cancelled;

class BookingReminderService
{
    /**
     * Minutes before start when a reminder becomes due.
     */
    public const REMINDER_WINDOW_MINUTES = 60;

    /**
     * @return int Number of reminders sent
     */
    public function sendDueReminders(?Carbon $now = null): int
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'reminder_sent_at')) {
            return 0;
        }

        $now ??= now();
        $windowEnd = (clone $now)->addMinutes(self::REMINDER_WINDOW_MINUTES);

        $bookings = Booking::query()
            ->with(['service', 'user'])
            ->whereNull('reminder_sent_at')
            ->where('starts_at', '>', $now)
            ->where('starts_at', '<=', $windowEnd)
            ->get()
            ->filter(fn (Booking $booking) => ! $booking->status->equals(Cancelled::class));

        $sent = 0;

        foreach ($bookings as $booking) {
            if ($this->notify($booking)) {
                $booking->forceFill(['reminder_sent_at' => $now])->save();
                $sent++;
            }
        }

        return $sent;
    }

    private function notify(Booking $booking): bool
    {
        $recipients = collect([
            $booking->guest_email,
            $booking->user?->email,
        ])->filter()->unique()->values();

        if ($recipients->isEmpty()) {
            Log::info('booking.reminder_skipped_no_recipient', ['booking_id' => $booking->id]);

            return true;
        }

        $serviceName = $booking->service?->name ?? 'Appointment';
        $starts = $booking->starts_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '';

        try {
            foreach ($recipients as $email) {
                Mail::to($email)->send(new BookingReminderMail($booking, $serviceName, $starts));
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('booking.reminder_failed', [
                'booking_id' => $booking->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
