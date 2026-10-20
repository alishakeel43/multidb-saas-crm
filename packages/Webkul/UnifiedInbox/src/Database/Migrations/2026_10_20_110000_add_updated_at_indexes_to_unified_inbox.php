<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only (tenant DB): the open inbox polls MAX(updated_at) every few seconds to detect new activity.
 */
return new class extends Migration
{
    protected array $indexes = [
        'unified_inbox_conversations' => 'ui_conversation_updated_idx',
        'unified_inbox_messages' => 'ui_message_updated_idx',
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $index) {
            if (! Schema::hasIndex($table, $index)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index('updated_at', $index));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $index) {
            if (Schema::hasIndex($table, $index)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($index));
            }
        }
    }
};
