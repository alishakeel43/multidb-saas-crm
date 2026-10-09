<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;
use Webkul\Core\Models\CoreConfig;

/**
 * Applies the current tenant's Configuration → General timezone.
 *
 * Isolation is the tenant database connection; this service never uses tenant_id columns.
 */
class TenantClock
{
    public const CONFIG_KEY = 'general.general.timezone.timezone';

    protected ?string $baselineTimezone = null;

    protected bool $activated = false;

    protected bool $applying = false;

    public function apply(): void
    {
        if ($this->applying) {
            return;
        }

        $this->applying = true;

        try {
            $this->rememberBaseline();

            $timezone = $this->savedTimezone();

            if ($timezone === null) {
                if ($this->activated) {
                    $this->restoreBaseline();
                    $this->activated = false;
                }

                return;
            }

            $this->activate($timezone);
            $this->activated = true;
        } finally {
            $this->applying = false;
        }
    }

    public function reset(): void
    {
        if (! $this->activated) {
            return;
        }

        $this->rememberBaseline();
        $this->restoreBaseline();
        $this->activated = false;
    }

    public function timezone(): string
    {
        return $this->savedTimezone()
            ?? (string) (config('app.timezone') ?: 'UTC');
    }

    public function savedTimezone(): ?string
    {
        if (! Tenant::checkCurrent()) {
            return null;
        }

        if (! Schema::hasTable('core_config')) {
            return null;
        }

        $value = CoreConfig::query()
            ->where('code', self::CONFIG_KEY)
            ->value('value');

        if (! is_string($value) || ! $this->isValid($value)) {
            return null;
        }

        return $value;
    }

    protected function rememberBaseline(): void
    {
        if ($this->baselineTimezone !== null) {
            return;
        }

        $timezone = (string) (config('app.timezone') ?: 'UTC');
        $this->baselineTimezone = $this->isValid($timezone) ? $timezone : 'UTC';
    }

    protected function activate(string $timezone): void
    {
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
    }

    protected function restoreBaseline(): void
    {
        config(['app.timezone' => $this->baselineTimezone]);
        date_default_timezone_set($this->baselineTimezone);
    }

    protected function isValid(string $timezone): bool
    {
        static $identifiers;

        $identifiers ??= array_flip(timezone_identifiers_list());

        return isset($identifiers[$timezone]);
    }
}
