<?php

namespace Webkul\MarketingAutomation\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\View\View;
use Webkul\MarketingAutomation\Services\EmailDispatcher;
use Webkul\MarketingAutomation\Services\TrackingService;

class TrackingController
{
    public function __construct(
        private TrackingService $tracking,
        private EmailDispatcher $mail,
    ) {}

    public function open(string $token): Response
    {
        return $this->tracking->open($token);
    }

    public function click(string $token): \Symfony\Component\HttpFoundation\Response
    {
        return $this->tracking->click($token);
    }

    public function unsubscribeForm(string $token): View
    {
        return view('marketing_automation::unsubscribe', ['token' => $token]);
    }

    public function unsubscribe(string $token): View
    {
        $this->mail->unsubscribe($token);

        return view('marketing_automation::unsubscribe', [
            'token' => $token,
            'done' => true,
        ]);
    }
}
