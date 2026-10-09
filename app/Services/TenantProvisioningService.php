<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Support\TenantIdentifier;
use Database\Seeders\TenantCrmSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class TenantProvisioningService
{
    public function __construct(
        private readonly TenantDatabaseManager $databaseManager,
    ) {}

    public function provision(
        string $name,
        string $slug,
        string $domain,
        string $adminName,
        string $adminEmail,
        string $adminPassword,
    ): Tenant {
        $slug = TenantIdentifier::validateSlug($slug);
        $domain = TenantIdentifier::validateDomain($domain);
        $this->validateCredentials($name, $adminName, $adminEmail, $adminPassword);

        $database = TenantIdentifier::databaseName($slug);
        $storagePath = storage_path('tenants/'.$slug);

        if (Tenant::query()->where('slug', $slug)->orWhere('database', $database)->exists()) {
            throw new RuntimeException("A tenant already uses slug or database [{$slug} / {$database}].");
        }

        if (Domain::query()->where('domain', $domain)->exists()) {
            throw new RuntimeException("Tenant domain [{$domain}] is already in use.");
        }

        if ($this->databaseManager->databaseExists($slug)) {
            throw new RuntimeException("Tenant database [{$database}] already exists; refusing to reuse it.");
        }

        if (File::exists($storagePath)) {
            throw new RuntimeException("Tenant storage path [{$storagePath}] already exists; refusing to reuse it.");
        }

        $plan = Plan::query()
            ->where('is_active', true)
            ->where('slug', 'starter')
            ->first()
            ?? Plan::query()->where('is_active', true)->orderBy('id')->first();

        if (! $plan) {
            throw new RuntimeException('No active plan is available for tenant provisioning.');
        }

        $tenant = null;
        $domainRecord = null;
        $databaseCreated = false;
        $storageCreated = false;
        $previousTenant = Tenant::current();

        try {
            $tenant = Tenant::query()->create([
                'name' => trim($name),
                'slug' => $slug,
                'database' => $database,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'trial_ends_at' => now()->addDays(config('tenant-provisioning.trial_days', 14)),
            ]);

            $domainRecord = $tenant->domains()->create([
                'domain' => $domain,
                'is_primary' => true,
                'is_active' => true,
            ]);

            $this->databaseManager->createDatabase($slug);
            $databaseCreated = true;

            $tenant->makeCurrent();

            $this->runArtisan('migrate', [
                '--database' => 'tenant',
                '--force' => true,
                '--no-interaction' => true,
            ], 'tenant migrations');

            $this->runArtisan('db:seed', [
                '--database' => 'tenant',
                '--class' => TenantCrmSeeder::class,
                '--force' => true,
                '--no-interaction' => true,
            ], 'safe CRM baseline seeding');

            $this->createTenantAdministrator($adminName, $adminEmail, $adminPassword);

            foreach ($plan->features()->get() as $feature) {
                TenantFeature::query()->create([
                    'tenant_id' => $tenant->id,
                    'feature_key' => $feature->feature_key,
                    'is_enabled' => $feature->is_enabled,
                ]);
            }

            $storageCreated = true;
            File::ensureDirectoryExists($storagePath);

            DB::connection('landlord')->table('subscriptions')->insert([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'trial',
                'starts_at' => now(),
                'trial_ends_at' => $tenant->trial_ends_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tenant->update(['status' => 'active']);
        } catch (Throwable $exception) {
            $cleanupErrors = $this->rollback(
                $tenant,
                $domainRecord,
                $slug,
                $databaseCreated,
                $storagePath,
                $storageCreated,
            );

            try {
                $this->restoreTenant($previousTenant);
            } catch (Throwable $restoreException) {
                $cleanupErrors[] = 'tenant context restore: '.$restoreException->getMessage();
            }

            if ($cleanupErrors !== []) {
                throw new RuntimeException(
                    'Tenant provisioning failed: '.$exception->getMessage().'; rollback errors: '.implode('; ', $cleanupErrors),
                    previous: $exception,
                );
            }

            throw $exception;
        }

        $this->restoreTenant($previousTenant);

        return $tenant->refresh();
    }

    private function createTenantAdministrator(string $name, string $email, string $password): void
    {
        $connection = DB::connection('tenant');
        $role = $connection->table('roles')
            ->where('name', 'Administrator')
            ->where('permission_type', 'all')
            ->first();

        if (! $role) {
            $now = now();
            $roleId = $connection->table('roles')->insertGetId([
                'name' => 'Administrator',
                'description' => 'Tenant administrator',
                'permission_type' => 'all',
                'permissions' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $roleId = $role->id;
        }

        $connection->table('users')->insert([
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'password' => Hash::make($password),
            'status' => true,
            'role_id' => $roleId,
            'view_permission' => 'global',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function runArtisan(string $command, array $arguments, string $description): void
    {
        $exitCode = Artisan::call($command, $arguments);

        if ($exitCode !== 0) {
            throw new RuntimeException(ucfirst($description).' failed: '.trim(Artisan::output()));
        }
    }

    private function rollback(
        ?Tenant $tenant,
        ?Domain $domain,
        string $slug,
        bool $databaseCreated,
        string $storagePath,
        bool $storageCreated,
    ): array {
        $errors = [];

        if ($databaseCreated) {
            try {
                $this->databaseManager->dropDatabase($slug);
            } catch (Throwable $exception) {
                $errors[] = 'database cleanup: '.$exception->getMessage();
            }
        }

        if ($domain) {
            try {
                Domain::query()->whereKey($domain->getKey())->delete();
            } catch (Throwable $exception) {
                $errors[] = 'domain row cleanup: '.$exception->getMessage();
            }
        }

        if ($tenant) {
            try {
                Tenant::query()->whereKey($tenant->getKey())->delete();
            } catch (Throwable $exception) {
                $errors[] = 'tenant row cleanup: '.$exception->getMessage();
            }
        }

        if ($storageCreated && File::isDirectory($storagePath)) {
            try {
                if (! File::deleteDirectory($storagePath)) {
                    $errors[] = 'storage cleanup: directory could not be removed.';
                }
            } catch (Throwable $exception) {
                $errors[] = 'storage cleanup: '.$exception->getMessage();
            }
        }

        return $errors;
    }

    private function restoreTenant(?Tenant $tenant): void
    {
        if ($tenant) {
            $tenant->makeCurrent();
        } else {
            Tenant::forgetCurrent();
        }
    }

    private function validateCredentials(
        string $name,
        string $adminName,
        string $adminEmail,
        string $adminPassword,
    ): void {
        if (trim($name) === '' || Str::length($name) > 255) {
            throw new InvalidArgumentException('Tenant name is required and must not exceed 255 characters.');
        }

        if (trim($adminName) === '' || Str::length($adminName) > 255) {
            throw new InvalidArgumentException('Administrator name is required and must not exceed 255 characters.');
        }

        if (
            strlen(trim($adminEmail)) > 255
            || ! filter_var(trim($adminEmail), FILTER_VALIDATE_EMAIL)
        ) {
            throw new InvalidArgumentException('A valid administrator email address is required.');
        }

        if (strlen($adminPassword) < 12 || strlen($adminPassword) > 72) {
            throw new InvalidArgumentException('Administrator password must be between 12 and 72 bytes.');
        }
    }
}
