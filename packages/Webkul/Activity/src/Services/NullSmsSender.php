<?php

namespace Webkul\Activity\Services;

use Webkul\Activity\Contracts\SmsSender;

class NullSmsSender implements SmsSender
{
    public function send(string $to, string $message, string $idempotencyKey): void
    {
        throw new \RuntimeException('SMS delivery is not configured.');
    }
}
