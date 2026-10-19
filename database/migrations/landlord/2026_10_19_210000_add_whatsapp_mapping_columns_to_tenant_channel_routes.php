<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only: richer phone_number_id -> tenant mapping for the central WhatsApp webhook.
 * Access tokens are NOT stored here; they stay encrypted in each tenant's unified_inbox_connections row.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('landlord');

        if (! $schema->hasColumn('tenant_channel_routes', 'display_phone_number')) {
            $schema->table('tenant_channel_routes', function (Blueprint $table) {
                $table->string('display_phone_number', 40)->nullable()->after('account_name');
            });
        }

        if (! $schema->hasColumn('tenant_channel_routes', 'deactivated_at')) {
            $schema->table('tenant_channel_routes', function (Blueprint $table) {
                $table->timestamp('deactivated_at')->nullable()->after('is_active');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('landlord');

        foreach (['display_phone_number', 'deactivated_at'] as $column) {
            if ($schema->hasColumn('tenant_channel_routes', $column)) {
                $schema->table('tenant_channel_routes', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
