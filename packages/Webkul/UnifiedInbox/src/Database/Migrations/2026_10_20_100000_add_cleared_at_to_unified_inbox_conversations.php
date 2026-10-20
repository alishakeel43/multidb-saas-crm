<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only (tenant DB): "Delete chat" hides a conversation and its earlier messages until the customer writes again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('unified_inbox_conversations', 'cleared_at')) {
            Schema::table('unified_inbox_conversations', function (Blueprint $table) {
                $table->timestamp('cleared_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('unified_inbox_conversations', 'cleared_at')) {
            Schema::table('unified_inbox_conversations', fn (Blueprint $table) => $table->dropColumn('cleared_at'));
        }
    }
};
