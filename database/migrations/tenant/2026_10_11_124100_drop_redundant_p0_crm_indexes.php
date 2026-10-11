<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove older auto-named indexes superseded by explicit P0 performance indexes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dropIndexIfExists('person_consents', 'person_consents_channel_is_opted_in_index');
    }

    public function down(): void
    {
        if (! Schema::hasTable('person_consents')) {
            return;
        }

        if ($this->hasNamedIndex('person_consents', 'person_consents_channel_is_opted_in_index')) {
            return;
        }

        Schema::table('person_consents', function (Blueprint $table): void {
            $table->index(['channel', 'is_opted_in'], 'person_consents_channel_is_opted_in_index');
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->hasNamedIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }

    private function hasNamedIndex(string $table, string $index): bool
    {
        foreach (Schema::getIndexes($table) as $meta) {
            if (($meta['name'] ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
};
