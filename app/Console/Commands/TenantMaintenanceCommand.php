<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;
use App\Support\TenantIdentifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

abstract class TenantMaintenanceCommand extends Command
{
    public function handle(TenantDatabaseManager $databaseManager): int
    {
        $identifier = $this->argument('tenant');
        $all = $this->option('all');

        if (($identifier && $all) || (! $identifier && ! $all)) {
            $this->error('Specify one tenant slug/ID or --all.');

            return self::FAILURE;
        }

        $tenants = $all
            ? Tenant::query()->orderBy('id')->get()
            : Tenant::query()
                ->when(
                    ctype_digit((string) $identifier),
                    fn ($query) => $query->whereKey((int) $identifier),
                    fn ($query) => $query->where('slug', $identifier),
                )
                ->get();

        if ($tenants->isEmpty()) {
            $this->error($all ? 'No tenants are registered.' : "Tenant [{$identifier}] was not found.");

            return self::FAILURE;
        }

        try {
            $databaseManager->assertMampConnection();
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $result = self::SUCCESS;

        foreach ($tenants as $tenant) {
            $this->info("Running {$this->tenantCommand()} for [{$tenant->slug}] ({$tenant->database})");

            try {
                if (
                    $tenant->database !== TenantIdentifier::databaseName($tenant->slug)
                    || $databaseManager->isProtectedSlug($tenant->slug)
                ) {
                    throw new RuntimeException("Refusing to operate on protected or invalid database [{$tenant->database}].");
                }

                $tenant->makeCurrent();

                $exitCode = Artisan::call($this->tenantCommand(), [
                    '--database' => 'tenant',
                    '--force' => true,
                    '--no-interaction' => true,
                    ...$this->tenantCommandArguments(),
                ]);

                $output = trim(Artisan::output());

                if ($output !== '') {
                    $this->output->writeln($output);
                }

                if ($exitCode !== 0) {
                    $this->error("Command failed for tenant [{$tenant->slug}].");
                    $result = self::FAILURE;
                }
            } catch (\Throwable $exception) {
                $this->error("Command failed for tenant [{$tenant->slug}]: {$exception->getMessage()}");
                $result = self::FAILURE;
            } finally {
                Tenant::forgetCurrent();
            }
        }

        return $result;
    }

    abstract protected function tenantCommand(): string;

    protected function tenantCommandArguments(): array
    {
        return [];
    }
}
