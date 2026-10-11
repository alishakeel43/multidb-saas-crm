<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

class TenantSeedService
{
    /**
     * Run CRM baseline (or full demo + P0) seed for one tenant.
     *
     * @throws RuntimeException
     */
    public function seed(Tenant $tenant, bool $demo = false): void
    {
        $arguments = [
            'tenant' => $tenant->slug,
            '--no-interaction' => true,
        ];

        if ($demo) {
            $arguments['--demo'] = true;
        }

        $exitCode = Artisan::call('tenants:seed', $arguments);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            throw new RuntimeException($output !== '' ? $output : "Seed failed for tenant [{$tenant->slug}].");
        }
    }

    /**
     * @return array{succeeded: int, failed: array<int, array{slug: string, error: string}>}
     */
    public function seedAll(bool $demo = false): array
    {
        $succeeded = 0;
        $failed = [];

        foreach (Tenant::query()->orderBy('id')->get() as $tenant) {
            try {
                $this->seed($tenant, $demo);
                $succeeded++;
            } catch (Throwable $exception) {
                $failed[] = [
                    'slug' => $tenant->slug,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return [
            'succeeded' => $succeeded,
            'failed' => $failed,
        ];
    }
}
