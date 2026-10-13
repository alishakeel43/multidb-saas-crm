<?php

namespace Webkul\MarketingAutomation\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\MarketingAutomation\Services\CampaignExtension;

class ExtendCampaignPayload
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('admin.settings.marketing.campaigns.edit') || ! $response instanceof JsonResponse) {
            return $response;
        }

        $payload = $response->getData(true);

        if (! is_array($payload)) {
            return $response;
        }

        $response->setData(app(CampaignExtension::class)->mergeIntoPayload($payload));

        return $response;
    }
}
