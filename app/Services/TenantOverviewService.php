<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TenantOverviewService
{
    /**
     * @var array<int, array<string, int|string|null>>
     */
    private array $tenantDetails = [];

    /**
     * @return array{
     *     database_status: string,
     *     database_message: ?string,
     *     owner_name: ?string,
     *     owner_email: ?string,
     *     total_users: ?int,
     *     active_users: ?int,
     *     leads: ?int,
     *     contacts: ?int,
     *     organizations: ?int
     * }
     */
    public function detailsFor(Tenant $tenant): array
    {
        $tenantId = (int) $tenant->getKey();

        if (isset($this->tenantDetails[$tenantId])) {
            return $this->tenantDetails[$tenantId];
        }

        try {
            $details = $tenant->execute(function (): array {
                $connection = DB::connection('tenant');

                $administrator = $connection->table('users')
                    ->join('roles', 'roles.id', '=', 'users.role_id')
                    ->where('roles.permission_type', 'all')
                    ->orderBy('users.id')
                    ->select(['users.name', 'users.email'])
                    ->first();

                return [
                    'database_status' => 'Connected',
                    'database_message' => null,
                    'owner_name' => $administrator?->name,
                    'owner_email' => $administrator?->email,
                    'total_users' => (int) $connection->table('users')->count(),
                    'active_users' => (int) $connection->table('users')->where('status', true)->count(),
                    'leads' => $this->countTable('leads'),
                    'contacts' => $this->countTable('persons'),
                    'organizations' => $this->countTable('organizations'),
                ];
            });
        } catch (Throwable $exception) {
            Log::warning('Could not load tenant overview from its CRM database.', [
                'tenant_id' => $tenantId,
                'exception' => $exception,
            ]);

            $details = [
                'database_status' => 'Unavailable',
                'database_message' => 'Tenant database could not be reached. Check the tenant database configuration and connection.',
                'owner_name' => null,
                'owner_email' => null,
                'total_users' => null,
                'active_users' => null,
                'leads' => null,
                'contacts' => null,
                'organizations' => null,
            ];
        }

        return $this->tenantDetails[$tenantId] = $details;
    }

    private function countTable(string $table): ?int
    {
        if (! Schema::connection('tenant')->hasTable($table)) {
            return null;
        }

        return (int) DB::connection('tenant')->table($table)->count();
    }
}
