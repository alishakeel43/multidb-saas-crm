<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Support\Facades\DB;

class FeatureMatrixService
{
    public function __construct(
        private readonly FeatureEntitlementService $entitlements,
    ) {}

    /**
     * @return array<string, string> category key => label
     */
    public function categoryLabels(): array
    {
        return config('feature_categories', []);
    }

    /**
     * Features grouped by category for matrix UIs.
     *
     * @return array<string, array{label: string, features: array<string, array<string, mixed>>}>
     */
    public function groupedFeatures(): array
    {
        $categories = $this->categoryLabels();
        $grouped = [];

        foreach ($categories as $categoryKey => $categoryLabel) {
            $grouped[$categoryKey] = [
                'label' => $categoryLabel,
                'features' => [],
            ];
        }

        foreach (config('features', []) as $featureKey => $definition) {
            if (($definition['matrix'] ?? true) === false) {
                continue;
            }

            $categoryKey = $definition['category'] ?? 'integrations';

            if (! isset($grouped[$categoryKey])) {
                $grouped[$categoryKey] = [
                    'label' => $categories[$categoryKey] ?? ucfirst(str_replace('_', ' ', $categoryKey)),
                    'features' => [],
                ];
            }

            $grouped[$categoryKey]['features'][$featureKey] = $definition;
        }

        return array_filter(
            $grouped,
            fn (array $group): bool => $group['features'] !== [],
        );
    }

    /**
     * @return array<string, bool>
     */
    public function enabledMapForPlan(Plan $plan): array
    {
        $stored = PlanFeature::query()
            ->where('plan_id', $plan->id)
            ->pluck('is_enabled', 'feature_key')
            ->map(fn ($enabled): bool => (bool) $enabled)
            ->all();

        $map = [];

        foreach (config('features', []) as $featureKey => $definition) {
            $map[$featureKey] = array_key_exists($featureKey, $stored)
                ? $stored[$featureKey]
                : (bool) ($definition['default_enabled'] ?? false);
        }

        return $map;
    }

    /**
     * Effective enabled map for a tenant (override → plan → default).
     *
     * @return array<string, bool>
     */
    public function enabledMapForTenant(Tenant $tenant): array
    {
        $map = [];

        foreach (array_keys(config('features', [])) as $featureKey) {
            $map[$featureKey] = $this->entitlements->isEnabled($featureKey, $tenant);
        }

        return $map;
    }

    /**
     * Validate that every enabled module still has its required dependencies enabled.
     *
     * @param  array<string, bool>  $enabledByKey
     * @return list<string>
     */
    public function dependencyErrors(array $enabledByKey): array
    {
        $labels = collect(config('features', []))
            ->mapWithKeys(fn (array $definition, string $key): array => [
                $key => $definition['label'] ?? $key,
            ])
            ->all();

        $errors = [];

        foreach (config('features', []) as $featureKey => $definition) {
            if (! ($enabledByKey[$featureKey] ?? false)) {
                continue;
            }

            foreach ($definition['depends_on'] ?? [] as $dependencyKey) {
                if ($enabledByKey[$dependencyKey] ?? false) {
                    continue;
                }

                $featureLabel = $labels[$featureKey] ?? $featureKey;
                $dependencyLabel = $labels[$dependencyKey] ?? $dependencyKey;

                $errors[] = "{$featureLabel} requires {$dependencyLabel} to stay enabled.";
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  array<string, bool>  $enabledByKey
     *
     * @throws \InvalidArgumentException
     */
    public function syncPlan(Plan $plan, array $enabledByKey): void
    {
        $enabledByKey = $this->normalizeLegacyAliases($enabledByKey);

        $this->assertDependenciesSatisfied($enabledByKey);

        DB::connection('landlord')->transaction(function () use ($plan, $enabledByKey): void {
            foreach (array_keys(config('features', [])) as $featureKey) {
                PlanFeature::query()->updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'feature_key' => $featureKey,
                    ],
                    [
                        'is_enabled' => (bool) ($enabledByKey[$featureKey] ?? false),
                    ],
                );
            }
        });

        $this->entitlements->forgetPlanCache($plan->id);
    }

    /**
     * Persist explicit tenant overrides for every known feature key.
     *
     * @param  array<string, bool>  $enabledByKey
     *
     * @throws \InvalidArgumentException
     */
    public function syncTenant(Tenant $tenant, array $enabledByKey): void
    {
        $enabledByKey = $this->normalizeLegacyAliases($enabledByKey);

        $this->assertDependenciesSatisfied($enabledByKey);

        DB::connection('landlord')->transaction(function () use ($tenant, $enabledByKey): void {
            foreach (array_keys(config('features', [])) as $featureKey) {
                TenantFeature::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'feature_key' => $featureKey,
                    ],
                    [
                        'is_enabled' => (bool) ($enabledByKey[$featureKey] ?? false),
                    ],
                );
            }
        });

        $this->entitlements->forgetTenantCache($tenant->id);
    }

    /**
     * Keep legacy alias feature keys (e.g. organizations → contacts) in lockstep for stored rows.
     *
     * @param  array<string, bool>  $enabledByKey
     * @return array<string, bool>
     */
    private function normalizeLegacyAliases(array $enabledByKey): array
    {
        foreach (config('features', []) as $featureKey => $definition) {
            $aliasOf = $definition['legacy_alias_of'] ?? null;

            if (! is_string($aliasOf) || $aliasOf === '') {
                continue;
            }

            $enabledByKey[$featureKey] = (bool) ($enabledByKey[$aliasOf] ?? false);
        }

        return $enabledByKey;
    }

    /**
     * @param  array<string, bool>  $enabledByKey
     *
     * @throws \InvalidArgumentException
     */
    private function assertDependenciesSatisfied(array $enabledByKey): void
    {
        $errors = $this->dependencyErrors($enabledByKey);

        if ($errors === []) {
            return;
        }

        throw new \InvalidArgumentException(implode(' ', $errors));
    }

    /**
     * @param  array<string, bool>  $enabledByKey
     * @return array<string, bool>
     */
    public function applyCategoryState(array $enabledByKey, string $categoryKey, bool $enabled): array
    {
        $grouped = $this->groupedFeatures();

        foreach (array_keys($grouped[$categoryKey]['features'] ?? []) as $featureKey) {
            $enabledByKey[$featureKey] = $enabled;
        }

        return $enabledByKey;
    }

    /**
     * @param  array<string, bool>  $enabledByKey
     */
    public function categoryIsFullyEnabled(array $enabledByKey, string $categoryKey): bool
    {
        $features = $this->groupedFeatures()[$categoryKey]['features'] ?? [];

        if ($features === []) {
            return false;
        }

        foreach (array_keys($features) as $featureKey) {
            if (! ($enabledByKey[$featureKey] ?? false)) {
                return false;
            }
        }

        return true;
    }
}
