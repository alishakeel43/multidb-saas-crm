<?php

namespace Webkul\Activity\Services;

use Illuminate\Support\Facades\Http;
use Webkul\Activity\Contracts\SmsSender;

class TwilioSmsSender implements SmsSender
{
    public function send(string $to, string $message, string $idempotencyKey): void
    {
        $config = config('activity-reminders.sms');
        if (! preg_match('/^AC[a-fA-F0-9]{32}$/', $config['sid'] ?? '') || empty($config['token']) || empty($config['from'])) {
            throw new \RuntimeException('SMS delivery is not configured.');
        }
        try {
            $response = Http::asForm()->withBasicAuth($config['sid'], $config['token'])
                ->connectTimeout(5)->timeout(15)->withoutRedirecting()
                ->post('https://api.twilio.com/2010-04-01/Accounts/'.$config['sid'].'/Messages.json', [
                    'To' => $to, 'From' => $config['from'], 'Body' => $message,
                ]);
            if (! $response->successful()) {
                throw new \RuntimeException;
            }
        } catch (\Throwable) {
            // Never retain the transport exception: it can contain credentials or phone numbers.
            throw new \RuntimeException('SMS delivery failed or its outcome is unknown.');
        }
    }
}
