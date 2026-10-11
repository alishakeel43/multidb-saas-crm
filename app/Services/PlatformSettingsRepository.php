<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

class PlatformSettingsRepository
{
    private const CACHE_KEY = 'saas.platform_settings';

    private const CACHE_TTL_MINUTES = 30;

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return data_get($settings, $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTES), function (): array {
            $grouped = [];

            foreach (PlatformSetting::query()->get() as $setting) {
                $grouped[$setting->group][$setting->key] = $setting->value;
            }

            return $grouped;
        });
    }

    /**
     * @param  array<string, mixed>  $values  group.key => value
     */
    public function putMany(array $values): void
    {
        foreach ($values as $compositeKey => $value) {
            [$group, $key] = array_pad(explode('.', $compositeKey, 2), 2, null);

            if (! $group || ! $key) {
                continue;
            }

            PlatformSetting::query()->updateOrCreate(
                ['key' => $key, 'group' => $group],
                ['value' => $value, 'is_public' => false],
            );
        }

        $this->forget();
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function trialDays(): int
    {
        return (int) ($this->get('billing.trial_days')
            ?? config('saas.trial_days')
            ?? config('tenant-provisioning.trial_days', 14));
    }

    public function taxPercent(): float
    {
        return (float) ($this->get('billing.tax_percent') ?? config('saas.tax_percent', 0));
    }

    public function currency(): string
    {
        return (string) ($this->get('billing.currency') ?? config('saas.default_currency', 'USD'));
    }

    public function enforceSubscription(): bool
    {
        return (bool) ($this->get('security.enforce_subscription')
            ?? config('saas.enforce_subscription')
            ?? config('tenant-provisioning.enforce_subscription', false));
    }
}
