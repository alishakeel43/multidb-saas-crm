<?php

namespace App\Providers;

use App\Health\Checks\StripeConfiguredCheck;
use App\Models\PlanFeature;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Observers\RejectSupportUserOwnership;
use App\Services\FeatureEntitlementService;
use App\Services\TenantOverviewService;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Webkul\Activity\Models\Activity;
use Webkul\Activity\Models\Participant;
use Webkul\Contact\Models\Organization;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Quote\Models\Quote;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantOverviewService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/tenant'));

        Health::checks([
            DatabaseCheck::new()->connectionName('landlord')->name('Landlord database'),
            UsedDiskSpaceCheck::new(),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            StripeConfiguredCheck::new(),
        ]);

        foreach ([Activity::class, Participant::class, Organization::class, Person::class, Lead::class, Quote::class] as $model) {
            $model::observe(RejectSupportUserOwnership::class);
        }

        TenantFeature::saved(function (TenantFeature $feature): void {
            $entitlements = app(FeatureEntitlementService::class);
            $entitlements->forgetTenantCache($feature->tenant_id);
        });

        TenantFeature::updating(function (TenantFeature $feature): void {
            $entitlements = app(FeatureEntitlementService::class);
            $entitlements->forgetTenantCache($feature->getOriginal('tenant_id'));
            $entitlements->forgetTenantCache($feature->tenant_id);
        });

        TenantFeature::deleted(function (TenantFeature $feature): void {
            app(FeatureEntitlementService::class)->forgetTenantCache($feature->tenant_id);
        });

        PlanFeature::saved(function (PlanFeature $feature): void {
            app(FeatureEntitlementService::class)->forgetPlanCache($feature->plan_id);
        });

        PlanFeature::updating(function (PlanFeature $feature): void {
            $entitlements = app(FeatureEntitlementService::class);
            $entitlements->forgetPlanCache($feature->getOriginal('plan_id'));
            $entitlements->forgetPlanCache($feature->plan_id);
        });

        PlanFeature::deleted(function (PlanFeature $feature): void {
            app(FeatureEntitlementService::class)->forgetPlanCache($feature->plan_id);
        });

        Tenant::updated(function (Tenant $tenant): void {
            if ($tenant->wasChanged('plan_id')) {
                app(FeatureEntitlementService::class)->forgetTenantCache($tenant->id);
            }
        });
    }
}
