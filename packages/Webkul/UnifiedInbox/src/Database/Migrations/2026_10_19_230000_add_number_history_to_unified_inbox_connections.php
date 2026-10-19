<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only (tenant DB): keep a dated history of every number/account a tenant connected.
 * Rows are never deleted; a disconnected row is the archive record of the old number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unified_inbox_connections', function (Blueprint $table) {
            if (! Schema::hasColumn('unified_inbox_connections', 'display_phone_number')) {
                $table->string('display_phone_number', 40)->nullable();
            }
            if (! Schema::hasColumn('unified_inbox_connections', 'connected_at')) {
                $table->timestamp('connected_at')->nullable();
            }
            if (! Schema::hasColumn('unified_inbox_connections', 'disconnected_at')) {
                $table->timestamp('disconnected_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        foreach (['display_phone_number', 'connected_at', 'disconnected_at'] as $column) {
            if (Schema::hasColumn('unified_inbox_connections', $column)) {
                Schema::table('unified_inbox_connections', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
