<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

class LandlordSchema
{
    public static function migrate(): void
    {
        foreach ([
            '2026_10_09_000000_create_saas_landlord_tables.php',
            '2026_10_09_000001_create_tenant_support_users_table.php',
            '2026_10_11_000000_add_saas_billing_and_security_tables.php',
            '2026_10_11_000001_create_activity_log_table.php',
            '2026_10_11_000002_create_health_tables.php',
        ] as $file) {
            $migration = require database_path('migrations/landlord/'.$file);
            $migration->up();
        }
    }

    public static function configureMemoryConnections(): void
    {
        config([
            'database.default' => 'tenant',
            'database.connections.landlord' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.connections.tenant' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'activitylog.database_connection' => 'landlord',
            'saas.enforce_subscription' => false,
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
        ]);

        DB::purge('landlord');
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');
    }
}
