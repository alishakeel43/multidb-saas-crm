<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Facades\CauserResolver;
use Throwable;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $description, array $properties = [], ?object $subject = null, ?string $event = null): void
    {
        try {
            if (! Schema::connection(config('activitylog.database_connection', 'landlord'))->hasTable('activity_log')) {
                return;
            }

            $properties = $this->sanitize($properties);

            if ($causer = Auth::guard('platform')->user()) {
                CauserResolver::setCauser($causer);
            }

            $activity = activity('saas')
                ->event($event ?? 'platform')
                ->withProperties($properties);

            if ($subject) {
                $activity->performedOn($subject);
            }

            $activity->log($description);
        } catch (Throwable $exception) {
            Log::warning('saas.audit_failed', [
                'description' => $description,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function sanitize(array $properties): array
    {
        $blocked = ['password', 'admin_password', 'secret', 'token', 'two_factor_secret', 'recovery_codes'];

        foreach ($properties as $key => $value) {
            if (in_array(strtolower((string) $key), $blocked, true)) {
                $properties[$key] = '[redacted]';
            }
        }

        return $properties;
    }
}
