<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\StorageQuotaService;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantStorageQuota
{
    public function __construct(
        private readonly StorageQuotaService $storageQuota,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::current();

        if (! $tenant || ! $request->hasFile()) {
            return $next($request);
        }

        $incoming = 0;

        foreach ($request->allFiles() as $file) {
            $files = is_array($file) ? $file : [$file];

            foreach ($files as $uploaded) {
                if ($uploaded) {
                    $incoming += (int) $uploaded->getSize();
                }
            }
        }

        try {
            $this->storageQuota->assertCanStore($tenant, $incoming);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            abort(422, $exception->getMessage());
        }

        return $next($request);
    }
}
