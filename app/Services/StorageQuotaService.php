<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class StorageQuotaService
{
    public function quotaBytes(Tenant $tenant): int
    {
        $tenant->loadMissing('plan');

        $mb = $tenant->storage_quota_mb
            ?? $tenant->plan?->storage_quota_mb
            ?? config('saas.default_storage_quota_mb', 1024);

        return max(0, (int) $mb) * 1024 * 1024;
    }

    public function usageBytes(Tenant $tenant): int
    {
        $path = storage_path('tenants/'.$tenant->slug);

        if (! File::isDirectory($path)) {
            return 0;
        }

        $bytes = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $bytes += $file->getSize();
            }
        }

        return $bytes;
    }

    /**
     * @return array{used_bytes: int, quota_bytes: int, used_mb: float, quota_mb: float, percent: float}
     */
    public function summary(Tenant $tenant): array
    {
        $used = $this->usageBytes($tenant);
        $quota = $this->quotaBytes($tenant);

        return [
            'used_bytes' => $used,
            'quota_bytes' => $quota,
            'used_mb' => round($used / 1024 / 1024, 2),
            'quota_mb' => round($quota / 1024 / 1024, 2),
            'percent' => $quota > 0 ? round(($used / $quota) * 100, 1) : 0.0,
        ];
    }

    public function assertCanStore(Tenant $tenant, int $incomingBytes = 0): void
    {
        $quota = $this->quotaBytes($tenant);

        if ($quota === 0) {
            return;
        }

        if (($this->usageBytes($tenant) + max(0, $incomingBytes)) > $quota) {
            throw new RuntimeException('Tenant storage quota exceeded.');
        }
    }

    public function isOverQuota(Tenant $tenant): bool
    {
        $quota = $this->quotaBytes($tenant);

        return $quota > 0 && $this->usageBytes($tenant) > $quota;
    }
}
