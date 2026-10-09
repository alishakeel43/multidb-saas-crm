<?php

namespace App\Http\Middleware;

use App\Services\TenantClock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyTenantClock
{
    public function __construct(protected TenantClock $clock) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->clock->apply();

        try {
            return $next($request);
        } finally {
            $this->clock->reset();
        }
    }
}
