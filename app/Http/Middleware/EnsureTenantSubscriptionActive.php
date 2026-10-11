<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\SubscriptionLifecycleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscriptionActive
{
    public function __construct(
        private readonly SubscriptionLifecycleService $subscriptions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return $next($request);
        }

        if ($this->subscriptions->tenantHasAccess($tenant)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tenant subscription is inactive. Please contact support.',
            ], 402);
        }

        abort(402, 'Tenant subscription is inactive. Please contact support.');
    }
}
