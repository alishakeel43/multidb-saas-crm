<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill performance indexes for client P0 CRM tables on existing tenant DBs.
 * Idempotent: safe if create migrations already included these indexes (new tenants).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->indexIfMissing('assignment_rules', 'assignment_rules_entity_active_priority_idx', function (Blueprint $table): void {
            $table->index(
                ['entity_type', 'is_active', 'priority'],
                'assignment_rules_entity_active_priority_idx'
            );
        });

        $this->indexIfMissing('assignment_rule_users', 'assignment_rule_users_rule_active_sort_idx', function (Blueprint $table): void {
            $table->index(
                ['assignment_rule_id', 'is_active', 'sort_order'],
                'assignment_rule_users_rule_active_sort_idx'
            );
        });

        $this->indexIfMissing('assignment_rule_users', 'assignment_rule_users_user_id_idx', function (Blueprint $table): void {
            $table->index('user_id', 'assignment_rule_users_user_id_idx');
        });

        $this->indexIfMissing('booking_services', 'booking_services_active_name_idx', function (Blueprint $table): void {
            $table->index(['is_active', 'name'], 'booking_services_active_name_idx');
        });

        $this->dropIndexIfExists('booking_availabilities', 'booking_availabilities_user_id_weekday_index');
        $this->indexIfMissing('booking_availabilities', 'booking_availabilities_user_weekday_active_idx', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'weekday', 'is_active'],
                'booking_availabilities_user_weekday_active_idx'
            );
        });

        $this->dropIndexIfExists('bookings', 'bookings_starts_at_ends_at_index');
        $this->dropIndexIfExists('bookings', 'bookings_status_index');

        $this->indexIfMissing('bookings', 'bookings_user_starts_ends_idx', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'starts_at', 'ends_at'],
                'bookings_user_starts_ends_idx'
            );
        });

        $this->indexIfMissing('bookings', 'bookings_status_starts_idx', function (Blueprint $table): void {
            $table->index(['status', 'starts_at'], 'bookings_status_starts_idx');
        });

        $this->indexIfMissing('bookings', 'bookings_person_id_idx', function (Blueprint $table): void {
            $table->index('person_id', 'bookings_person_id_idx');
        });

        $this->indexIfMissing('bookings', 'bookings_lead_id_idx', function (Blueprint $table): void {
            $table->index('lead_id', 'bookings_lead_id_idx');
        });

        $this->uniqueIfMissing('bookings', 'bookings_activity_id_unique', function (Blueprint $table): void {
            $table->unique('activity_id', 'bookings_activity_id_unique');
        });

        $this->indexIfMissing('person_consents', 'person_consents_channel_opt_in_idx', function (Blueprint $table): void {
            $table->index(['channel', 'is_opted_in'], 'person_consents_channel_opt_in_idx');
        });

        $this->indexIfMissing('person_consents', 'person_consents_updated_at_idx', function (Blueprint $table): void {
            $table->index('updated_at', 'person_consents_updated_at_idx');
        });

        // Unique (person_id, channel) may already exist from create migration under a generated name.
        $this->uniqueIfMissing('person_consents', 'person_consents_person_channel_unique', function (Blueprint $table): void {
            if (! $this->hasIndexCovering('person_consents', ['person_id', 'channel'], unique: true)) {
                $table->unique(['person_id', 'channel'], 'person_consents_person_channel_unique');
            }
        });

        $this->indexIfMissing('quote_approvals', 'quote_approvals_status_updated_idx', function (Blueprint $table): void {
            $table->index(['status', 'updated_at'], 'quote_approvals_status_updated_idx');
        });

        $this->indexIfMissing('quote_approvals', 'quote_approvals_requested_by_idx', function (Blueprint $table): void {
            $table->index('requested_by', 'quote_approvals_requested_by_idx');
        });

        $this->indexIfMissing('quote_approvals', 'quote_approvals_reviewed_by_idx', function (Blueprint $table): void {
            $table->index('reviewed_by', 'quote_approvals_reviewed_by_idx');
        });

        $this->uniqueIfMissing('quote_approvals', 'quote_approvals_quote_id_unique', function (Blueprint $table): void {
            if (! $this->hasIndexCovering('quote_approvals', ['quote_id'], unique: true)) {
                $table->unique('quote_id', 'quote_approvals_quote_id_unique');
            }
        });

        // Drop narrower status-only index if the composite above exists (create migration had both).
        if ($this->hasNamedIndex('quote_approvals', 'quote_approvals_status_updated_idx')) {
            $this->dropIndexIfExists('quote_approvals', 'quote_approvals_status_index');
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists('assignment_rules', 'assignment_rules_entity_active_priority_idx');
        $this->dropIndexIfExists('assignment_rule_users', 'assignment_rule_users_rule_active_sort_idx');
        $this->dropIndexIfExists('assignment_rule_users', 'assignment_rule_users_user_id_idx');
        $this->dropIndexIfExists('booking_services', 'booking_services_active_name_idx');
        $this->dropIndexIfExists('booking_availabilities', 'booking_availabilities_user_weekday_active_idx');
        $this->dropIndexIfExists('bookings', 'bookings_user_starts_ends_idx');
        $this->dropIndexIfExists('bookings', 'bookings_status_starts_idx');
        $this->dropIndexIfExists('bookings', 'bookings_person_id_idx');
        $this->dropIndexIfExists('bookings', 'bookings_lead_id_idx');
        $this->dropUniqueIfExists('bookings', 'bookings_activity_id_unique');
        $this->dropIndexIfExists('person_consents', 'person_consents_channel_opt_in_idx');
        $this->dropIndexIfExists('person_consents', 'person_consents_updated_at_idx');
        $this->dropIndexIfExists('quote_approvals', 'quote_approvals_status_updated_idx');
        $this->dropIndexIfExists('quote_approvals', 'quote_approvals_requested_by_idx');
        $this->dropIndexIfExists('quote_approvals', 'quote_approvals_reviewed_by_idx');
    }

    private function indexIfMissing(string $table, string $index, callable $callback): void
    {
        if (! Schema::hasTable($table) || $this->hasNamedIndex($table, $index)) {
            return;
        }

        Schema::table($table, $callback);
    }

    private function uniqueIfMissing(string $table, string $index, callable $callback): void
    {
        if (! Schema::hasTable($table) || $this->hasNamedIndex($table, $index)) {
            return;
        }

        Schema::table($table, $callback);
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

    private function dropUniqueIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->hasNamedIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropUnique($index);
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

    /**
     * @param  list<string>  $columns
     */
    private function hasIndexCovering(string $table, array $columns, bool $unique): bool
    {
        $want = array_values($columns);

        foreach (Schema::getIndexes($table) as $meta) {
            $have = array_values($meta['columns'] ?? []);

            if ($have === $want && (bool) ($meta['unique'] ?? false) === $unique) {
                return true;
            }
        }

        return false;
    }
};
