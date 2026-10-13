<?php

namespace App\Multitenancy\Tasks;

use Illuminate\Support\Facades\File;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class SwitchTenantStorageTask implements SwitchTenantTask
{
    protected ?string $originalLocalRoot;

    protected ?string $originalPublicRoot;

    protected ?string $originalPublicUrl;

    public function __construct()
    {
        $this->originalLocalRoot = config('filesystems.disks.local.root', storage_path('app'));
        $this->originalPublicRoot = config('filesystems.disks.public.root', storage_path('app/public'));
        $this->originalPublicUrl = config('filesystems.disks.public.url');
    }

    public function makeCurrent(IsTenant $tenant): void
    {
        $slug = (string) ($tenant->slug ?? $tenant->getAttribute('slug') ?? '');
        $tenantRoot = $slug !== '' ? storage_path('tenants/'.$slug) : storage_path('tenants');

        $localRoot = $tenantRoot.'/app';
        $publicRoot = $tenantRoot.'/app/public';

        File::ensureDirectoryExists($localRoot);
        File::ensureDirectoryExists($publicRoot);

        $config = [
            'filesystems.disks.local.root' => $localRoot,
            'filesystems.disks.public.root' => $publicRoot,
        ];

        // The shared public symlink points at storage/app/public, not this tenant
        // directory. A root-relative /storage URL stays on the tenant host so the
        // fallback route can serve the file that was just stored.
        if ($this->requestHasHost()) {
            $config['filesystems.disks.public.url'] = '/storage';
        }

        config($config);

        app('filesystem')->forgetDisk(['local', 'public']);
    }

    public function forgetCurrent(): void
    {
        config([
            'filesystems.disks.local.root' => $this->originalLocalRoot ?? storage_path('app'),
            'filesystems.disks.public.root' => $this->originalPublicRoot ?? storage_path('app/public'),
            'filesystems.disks.public.url' => $this->originalPublicUrl,
        ]);

        app('filesystem')->forgetDisk(['local', 'public']);
    }

    /**
     * Artisan and queued jobs have no browser host. Only an actual HTTP request
     * should rewrite public file URLs onto the current tenant host.
     */
    protected function requestHasHost(): bool
    {
        if (! app()->bound('request')) {
            return false;
        }

        $request = request();

        return $request->headers->has('HOST') || $request->server->has('HTTP_HOST');
    }
}
