<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;

class DefaultPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::query()->firstOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Default plan with all stock Krayin modules enabled.',
                'monthly_price' => 0,
                'currency' => 'USD',
                'is_active' => true,
                'sort_order' => 0,
                'storage_quota_mb' => 1024,
            ],
        );

        foreach (config('features', []) as $featureKey => $feature) {
            PlanFeature::query()->firstOrCreate(
                [
                    'plan_id' => $plan->id,
                    'feature_key' => $featureKey,
                ],
                [
                    'is_enabled' => $feature['default_enabled'] ?? true,
                ],
            );
        }
    }
}
