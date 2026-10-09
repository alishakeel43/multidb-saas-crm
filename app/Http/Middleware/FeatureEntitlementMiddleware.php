<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\FeatureEntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureEntitlementMiddleware
{
    public function __construct(
        private readonly FeatureEntitlementService $entitlements,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if (! $routeName || ! str_starts_with($routeName, 'admin.')) {
            return $next($request);
        }

        $tenant = Tenant::current();

        if ($tenant) {
            config([
                'menu.admin' => $this->entitlements->filterMenuItems(
                    config('menu.admin', []),
                    $tenant,
                ),
            ]);
        }

        $featureKey = $this->entitlements->featureForRoute($routeName);

        if ($featureKey !== null && (! $tenant || ! $this->entitlements->isEnabled($featureKey, $tenant))) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
