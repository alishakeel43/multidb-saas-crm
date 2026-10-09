<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSupportUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class TenantSupportAccountService
{
    private const READ_ONLY_PERMISSIONS = [
        'dashboard',
        'leads',
        'leads.view',
        'quotes',
        'activities',
        'contacts',
        'contacts.persons',
        'contacts.persons.view',
        'contacts.organizations',
        'contacts.organizations.view',
    ];

    public function create(Tenant $tenant, array $data): TenantSupportUser
    {
        $this->validateIdentity($data);

        if (strlen($data['password'] ?? '') < 12 || strlen($data['password']) > 72) {
            throw ValidationException::withMessages([
                'data.password' => 'Support password must be between 12 and 72 bytes.',
            ]);
        }

        $email = Str::lower(trim($data['email']));

        $tenantUserId = null;

        try {
            return DB::connection('landlord')->transaction(function () use ($tenant, $data, $email, &$tenantUserId): TenantSupportUser {
                $tenantUserId = $tenant->execute(function () use ($data, $email): int {
                    $this->assertTenantSchema();

                    return DB::connection('tenant')->transaction(function () use ($data, $email): int {
                        $connection = DB::connection('tenant');

                        if ($connection->table('users')->where('email', $email)->exists()) {
                            throw ValidationException::withMessages([
                                'data.email' => 'This email address is already used in the selected tenant.',
                            ]);
                        }

                        $roleId = $this->ensureSupportRole();
                        $now = now();

                        return (int) $connection->table('users')->insertGetId([
                            'name' => trim($data['name']),
                            'email' => $email,
                            'password' => Hash::make($data['password']),
                            'status' => (bool) ($data['is_active'] ?? true),
                            'role_id' => $roleId,
                            'view_permission' => 'global',
                            'is_support_account' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    });
                });

                $supportUser = TenantSupportUser::query()->create([
                    'tenant_id' => $tenant->id,
                    'tenant_user_id' => $tenantUserId,
                    'name' => trim($data['name']),
                    'email' => $email,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);
                $supportUser->setRelation('tenant', $tenant);

                return $supportUser;
            });
        } catch (Throwable $exception) {
            if ($tenantUserId !== null) {
                $tenant->execute(function () use ($tenantUserId): void {
                    if (Schema::connection('tenant')->hasColumn('users', 'is_support_account')) {
                        DB::connection('tenant')->table('users')
                            ->where('id', $tenantUserId)
                            ->where('is_support_account', true)
                            ->delete();
                    }
                });
            }

            throw $exception;
        }
    }

    public function update(TenantSupportUser $supportUser, array $data): TenantSupportUser
    {
        $this->validateIdentity($data);

        if (filled($data['password'] ?? null) && (strlen($data['password']) < 12 || strlen($data['password']) > 72)) {
            throw ValidationException::withMessages([
                'data.password' => 'Support password must be between 12 and 72 bytes.',
            ]);
        }

        $tenant = $supportUser->tenant;
        $email = Str::lower(trim($data['email']));

        DB::connection('landlord')->transaction(function () use ($tenant, $supportUser, $data, $email): void {
            $tenant->execute(function () use ($supportUser, $data, $email): void {
                $this->assertTenantSchema();

                DB::connection('tenant')->transaction(function () use ($supportUser, $data, $email): void {
                    $connection = DB::connection('tenant');
                    $userQuery = $connection->table('users')
                        ->where('id', $supportUser->tenant_user_id)
                        ->where('is_support_account', true);

                    if (! $userQuery->exists()) {
                        throw new RuntimeException('The support account no longer exists in its tenant database.');
                    }

                    if (
                        $connection->table('users')->where('email', $email)
                            ->where('id', '!=', $supportUser->tenant_user_id)->exists()
                    ) {
                        throw ValidationException::withMessages([
                            'data.email' => 'This email address is already used in the selected tenant.',
                        ]);
                    }

                    $values = [
                        'name' => trim($data['name']),
                        'email' => $email,
                        'status' => (bool) $data['is_active'],
                        'role_id' => $this->ensureSupportRole(),
                        'updated_at' => now(),
                    ];

                    if (filled($data['password'] ?? null)) {
                        $values['password'] = Hash::make($data['password']);
                    }

                    $userQuery->update($values);
                });
            });

            $supportUser->update([
                'name' => trim($data['name']),
                'email' => $email,
                'is_active' => (bool) $data['is_active'],
            ]);
        });

        return $supportUser->refresh();
    }

    public function delete(TenantSupportUser $supportUser): void
    {
        $tenant = $supportUser->tenant;

        DB::connection('landlord')->transaction(function () use ($tenant, $supportUser): void {
            $tenant->execute(function () use ($supportUser): void {
                $this->assertTenantSchema();

                DB::connection('tenant')->transaction(function () use ($supportUser): void {
                    $userQuery = DB::connection('tenant')->table('users')
                        ->where('id', $supportUser->tenant_user_id)
                        ->where('is_support_account', true);

                    if (! $userQuery->exists()) {
                        throw new RuntimeException('The support account no longer exists in its tenant database.');
                    }

                    $userQuery->delete();
                });
            });

            $supportUser->delete();
        });
    }

    public function isSupportUserId(int $userId): bool
    {
        if (! Schema::connection('tenant')->hasColumn('users', 'is_support_account')) {
            return false;
        }

        return DB::connection('tenant')
            ->table('users')
            ->where('id', $userId)
            ->where('is_support_account', true)
            ->exists();
    }

    private function validateIdentity(array $data): void
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        if ($name === '' || Str::length($name) > 255) {
            throw ValidationException::withMessages([
                'data.name' => 'Support name is required and must not exceed 255 characters.',
            ]);
        }

        if (strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'data.email' => 'Enter a valid support email address.',
            ]);
        }
    }

    private function assertTenantSchema(): void
    {
        if (
            ! Schema::connection('tenant')->hasColumn('users', 'is_support_account')
            || ! Schema::connection('tenant')->hasColumn('roles', 'is_support_role')
        ) {
            throw new RuntimeException('Tenant support-account schema is missing; run tenant migrations before managing support users.');
        }
    }

    private function ensureSupportRole(): int
    {
        $connection = DB::connection('tenant');
        $role = $connection->table('roles')->where('is_support_role', true)->first();
        $values = [
            'name' => 'Support (System)',
            'description' => 'Read-only diagnostic support role',
            'permission_type' => 'custom',
            'permissions' => json_encode(self::READ_ONLY_PERMISSIONS, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ];

        if ($role) {
            $connection->table('roles')->where('id', $role->id)->update($values);

            return (int) $role->id;
        }

        return (int) $connection->table('roles')->insertGetId([
            ...$values,
            'is_support_role' => true,
            'created_at' => now(),
        ]);
    }
}
