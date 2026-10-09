<?php

namespace Webkul\Activity\Contracts;

interface SmsSender
{
    public function send(string $to, string $message, string $idempotencyKey): void;
}
