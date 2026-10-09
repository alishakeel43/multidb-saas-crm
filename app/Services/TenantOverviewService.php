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
     *     support_users: list<array{name: string, email: string}>,
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
                    ->when(
                        Schema::connection('tenant')->hasColumn('users', 'is_support_account'),
                        fn ($query) => $query->where('users.is_support_account', false),
                    )
                    ->orderBy('users.id')
                    ->select(['users.name', 'users.email'])
                    ->first();

                $hasSupportAccountMarker = Schema::connection('tenant')->hasColumn('users', 'is_support_account');
                $supportUsers = $hasSupportAccountMarker
                    ? $connection->table('users')
                        ->where('is_support_account', true)
                        ->select(['name', 'email'])
                        ->orderBy('name')
                        ->get()
                        ->map(fn ($user): array => ['name' => $user->name, 'email' => $user->email])
                        ->all()
                    : [];
                $users = $connection->table('users');

                if ($hasSupportAccountMarker) {
                    $users->where('is_support_account', false);
                }

                return [
                    'database_status' => 'Connected',
                    'database_message' => null,
                    'owner_name' => $administrator?->name,
                    'owner_email' => $administrator?->email,
                    'support_users' => $supportUsers,
                    'total_users' => (int) (clone $users)->count(),
                    'active_users' => (int) (clone $users)->where('status', true)->count(),
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
                'support_users' => [],
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
