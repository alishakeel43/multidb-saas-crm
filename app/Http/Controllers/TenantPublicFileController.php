<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TenantPublicFileController extends Controller
{
    /**
     * Serve a file from the current tenant public disk.
     *
     * Apache only follows public/storage to storage/app/public. Tenant uploads
     * (admin logo, favicon, user images) live under storage/tenants/{slug}, so
     * a missing symlink target falls through to this route.
     */
    public function __invoke(string $path): StreamedResponse
    {
        abort_unless(Tenant::current(), 404);
        abort_if($this->isUnsafePath($path), 404);

        try {
            $exists = Storage::disk('public')->exists($path);
        } catch (Throwable) {
            abort(404);
        }

        abort_unless($exists, 404);

        return Storage::disk('public')->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function isUnsafePath(string $path): bool
    {
        return $path === ''
            || str_contains($path, '..')
            || str_contains($path, '\\')
            || str_contains($path, "\0");
    }
}
