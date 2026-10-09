<?php

namespace App\Multitenancy\TenantFinder;

use App\Models\Domain;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        return Domain::query()
            ->with('tenant')
            ->where('domain', $request->getHost())
            ->where('is_active', true)
            ->whereHas('tenant', fn ($query) => $query->where('status', 'active'))
            ->first()
            ?->tenant;
    }
}
