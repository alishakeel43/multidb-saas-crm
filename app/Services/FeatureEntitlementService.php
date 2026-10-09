<?php

namespace App\Services;

use App\Models\PlanFeature;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Support\Facades\Cache;
use LogicException;

class FeatureEntitlementService
{
    private const CACHE_TTL_MINUTES = 10;

    /**
     * Resolve a feature from tenant override, plan default, then configured default.
     */
    public function isEnabled(string $featureKey, ?Tenant $tenant = null): bool
    {
        $tenant ??= Tenant::current();

        if (! $tenant) {
            return false;
        }

        $features = Cache::remember(
            $this->tenantCacheKey($tenant->getKey()),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => $this->loadTenantFeatures($tenant),
        );

        return $this->resolveFeature($featureKey, $features);
    }

    /**
     * @param  array{overrides: array<string, bool>, plan: array<string, bool>}  $features
     * @param  array<string, bool>  $resolving
     */
    private function resolveFeature(string $featureKey, array $features, array $resolving = []): bool
    {
        $definition = config("features.{$featureKey}");

        if (! is_array($definition)) {
            return false;
        }

        if (isset($resolving[$featureKey])) {
            throw new LogicException("Circular feature dependency detected for [{$featureKey}].");
        }

        $resolving[$featureKey] = true;

        if (array_key_exists($featureKey, $features['overrides'])) {
            $enabled = $features['overrides'][$featureKey];
        } elseif (array_key_exists($featureKey, $features['plan'])) {
            $enabled = $features['plan'][$featureKey];
        } else {
            $enabled = (bool) ($definition['default_enabled'] ?? false);
        }

        if (! $enabled) {
            return false;
        }

        foreach ($definition['depends_on'] ?? [] as $dependency) {
            if (! $this->resolveFeature($dependency, $features, $resolving)) {
                return false;
            }
        }

        return true;
    }

    public function featureForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $matchedFeature = null;
        $longestPrefixLength = -1;

        foreach (config('features', []) as $featureKey => $definition) {
            if (in_array($routeName, $definition['route_names'] ?? [], true)) {
                $matchedFeature = $featureKey;
                $longestPrefixLength = PHP_INT_MAX;

                continue;
            }

            foreach ($definition['route_prefixes'] ?? [] as $prefix) {
                $exactName = rtrim($prefix, '.');
                $matches = $routeName === $exactName || str_starts_with($routeName, $prefix);

                if ($longestPrefixLength < PHP_INT_MAX && $matches && strlen($prefix) > $longestPrefixLength) {
                    $matchedFeature = $featureKey;
                    $longestPrefixLength = strlen($prefix);
                }
            }
        }

        return $matchedFeature;
    }

    /**
     * Remove disabled feature links from Krayin's flat menu configuration.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function filterMenuItems(array $items, ?Tenant $tenant = null): array
    {
        $tenant ??= Tenant::current();

        if (! $tenant) {
            return $items;
        }

        $menuFeatures = [];
        $menuAnyFeatures = [];
        $menuAnyRoutes = [];

        foreach (config('features', []) as $featureKey => $definition) {
            foreach ($definition['menu_keys'] ?? [] as $menuKey) {
                $menuFeatures[$menuKey][] = $featureKey;
            }

            foreach ($definition['menu_any_keys'] ?? [] as $menuKey) {
                $menuAnyFeatures[$menuKey][] = $featureKey;
            }

            foreach ($definition['menu_any_routes'] ?? [] as $menuKey => $route) {
                $menuAnyRoutes[$menuKey][$featureKey] = $route;
            }
        }

        $filteredItems = array_filter($items, function (array $item) use ($menuFeatures, $menuAnyFeatures, $tenant): bool {
            $menuKey = $item['key'] ?? '';

            foreach ($menuAnyFeatures[$menuKey] ?? [] as $featureKey) {
                if ($this->isEnabled($featureKey, $tenant)) {
                    return true;
                }
            }

            foreach ($menuFeatures[$menuKey] ?? [] as $featureKey) {
                if (! $this->isEnabled($featureKey, $tenant)) {
                    return false;
                }
            }

            if (isset($menuAnyFeatures[$menuKey])) {
                return false;
            }

            return true;
        });

        foreach ($filteredItems as &$item) {
            $menuKey = $item['key'] ?? '';

            foreach ($menuAnyRoutes[$menuKey] ?? [] as $featureKey => $route) {
                if ($this->isEnabled($featureKey, $tenant)) {
                    $item['route'] = $route;

                    break;
                }
            }
        }

        unset($item);

        return array_values($filteredItems);
    }

    public function forgetTenantCache(int|string|null $tenantId): void
    {
        if ($tenantId !== null) {
            Cache::forget($this->tenantCacheKey($tenantId));
        }
    }

    public function forgetPlanCache(int|string|null $planId): void
    {
        if ($planId === null) {
            return;
        }

        Tenant::query()
            ->where('plan_id', $planId)
            ->pluck('id')
            ->each(fn ($tenantId) => $this->forgetTenantCache($tenantId));
    }

    private function tenantCacheKey(int|string $tenantId): string
    {
        return "feature-entitlements:tenant:{$tenantId}";
    }

    /**
     * @return array{overrides: array<string, bool>, plan: array<string, bool>}
     */
    private function loadTenantFeatures(Tenant $tenant): array
    {
        $overrides = TenantFeature::query()
            ->where('tenant_id', $tenant->getKey())
            ->get(['feature_key', 'is_enabled'])
            ->mapWithKeys(fn (TenantFeature $feature): array => [
                $feature->feature_key => (bool) $feature->is_enabled,
            ])
            ->all();

        $plan = $tenant->plan_id === null
            ? []
            : PlanFeature::query()
                ->where('plan_id', $tenant->plan_id)
                ->get(['feature_key', 'is_enabled'])
                ->mapWithKeys(fn (PlanFeature $feature): array => [
                    $feature->feature_key => (bool) $feature->is_enabled,
                ])
                ->all();

        return [
            'overrides' => $overrides,
            'plan' => $plan,
        ];
    }
}
