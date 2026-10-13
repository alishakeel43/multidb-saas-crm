<?php

namespace Webkul\MarketingAutomation\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SequenceMail extends Mailable
{
    public function __construct(
        public string $bodyText,
        public ?string $linkUrl,
        public ?string $linkLabel,
        public string $openUrl,
        public string $clickUrl,
        public string $unsubscribeUrl,
        private string $subjectLine,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            html: 'marketing_automation::emails.sequence',
        );
    }
}
