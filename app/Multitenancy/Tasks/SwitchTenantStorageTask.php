<?php

namespace App\Multitenancy\Tasks;

use Illuminate\Support\Facades\File;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class SwitchTenantStorageTask implements SwitchTenantTask
{
    protected ?string $originalLocalRoot;

    protected ?string $originalPublicRoot;

    public function __construct()
    {
        $this->originalLocalRoot = config('filesystems.disks.local.root', storage_path('app'));
        $this->originalPublicRoot = config('filesystems.disks.public.root', storage_path('app/public'));
    }

    public function makeCurrent(IsTenant $tenant): void
    {
        $slug = (string) ($tenant->slug ?? $tenant->getAttribute('slug') ?? '');
        $tenantRoot = $slug !== '' ? storage_path('tenants/'.$slug) : storage_path('tenants');

        $localRoot = $tenantRoot.'/app';
        $publicRoot = $tenantRoot.'/app/public';

        File::ensureDirectoryExists($localRoot);
        File::ensureDirectoryExists($publicRoot);

        config([
            'filesystems.disks.local.root' => $localRoot,
            'filesystems.disks.public.root' => $publicRoot,
        ]);

        app('filesystem')->forgetDisk(['local', 'public']);
    }

    public function forgetCurrent(): void
    {
        config([
            'filesystems.disks.local.root' => $this->originalLocalRoot ?? storage_path('app'),
            'filesystems.disks.public.root' => $this->originalPublicRoot ?? storage_path('app/public'),
        ]);

        app('filesystem')->forgetDisk(['local', 'public']);
    }
}
