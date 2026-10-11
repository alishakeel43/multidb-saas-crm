<?php

namespace Webkul\Booking\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Booking\Models\Booking;

class BookingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $serviceName,
        public string $startsLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: {$this->serviceName} at {$this->startsLabel}",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'booking::emails.reminder',
        );
    }
}
