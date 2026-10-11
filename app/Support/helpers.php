<?php

use App\Services\AuditLogger;

if (! function_exists('audit')) {
    /**
     * @param  array<string, mixed>  $properties
     */
    function audit(string $description, array $properties = [], ?object $subject = null, ?string $event = null): void
    {
        app(AuditLogger::class)->log($description, $properties, $subject, $event);
    }
}
