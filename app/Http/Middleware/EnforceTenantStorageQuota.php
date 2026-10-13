<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\StorageQuotaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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

        if (! $tenant || $request->allFiles() === []) {
            return $next($request);
        }

        $incoming = $this->incomingUploadBytes($request->allFiles());

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

    /**
     * Configuration uploads such as the admin logo are nested
     * (`general[general][admin_logo][logo_image]`), so the file tree must be
     * walked until each UploadedFile is reached.
     *
     * @param  array<string, mixed>  $files
     */
    private function incomingUploadBytes(array $files): int
    {
        $bytes = 0;

        $walk = function (mixed $node) use (&$walk, &$bytes): void {
            if ($node instanceof UploadedFile) {
                $bytes += (int) ($node->getSize() ?: 0);

                return;
            }

            if (! is_array($node)) {
                return;
            }

            foreach ($node as $child) {
                $walk($child);
            }
        };

        $walk($files);

        return $bytes;
    }
}
