<?php

namespace Database\Seeders;

use Illuminate\Database\Connection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Idempotent demo rows for P0 CRM packages (assignment, booking, consent,
 * quote approvals/fulfillment, lead scoring, notifications) so Sales reports
 * and package UIs have realistic data after `tenants:seed {slug} --demo`.
 */
class TenantP0DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::setDefaultConnection('tenant');

        $connection = DB::connection('tenant');
        $userId = (int) ($connection->table('users')->orderBy('id')->value('id') ?? 0);

        if (! $userId) {
            throw new RuntimeException('This tenant has no CRM users. Provision a tenant administrator before adding P0 demo records.');
        }

        $personIds = $connection->table('persons')
            ->where('unique_id', 'like', 'tenant-demo-seeder|contact-%')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $leadRows = $connection->table('leads')
            ->where('title', 'like', 'DEMO - %')
            ->orderBy('id')
            ->get(['id', 'title', 'person_id', 'lead_value']);

        if ($personIds === [] || $leadRows->isEmpty()) {
            throw new RuntimeException('Core demo CRM records are missing. Run TenantDemoSeeder before TenantP0DemoSeeder.');
        }

        $connection->transaction(function () use ($connection, $userId, $personIds, $leadRows): void {
            $this->seedLeadOutcomesAndScores($connection, $leadRows);
            $this->seedAssignment($connection, $userId);
            $this->seedConsent($connection, $userId, $personIds);
            $this->seedBooking($connection, $userId, $personIds, $leadRows);
            $this->seedQuotesAndApprovals($connection, $userId, $personIds, $leadRows);
            $this->seedLeadScoringRules($connection);
            $this->seedActivitiesForAlerts($connection, $userId, $personIds, $leadRows);
            $this->seedNotifications($connection, $userId, $leadRows);
        });
    }

    private function seedLeadOutcomesAndScores(Connection $connection, $leadRows): void
    {
        if (! Schema::connection('tenant')->hasTable('leads')) {
            return;
        }

        $hasScore = Schema::connection('tenant')->hasColumn('leads', 'score');
        $hasClosedAt = Schema::connection('tenant')->hasColumn('leads', 'closed_at');

        foreach ($leadRows->values() as $index => $lead) {
            $payload = ['updated_at' => now()];

            if ($hasScore) {
                $payload['score'] = match ($index) {
                    0 => 45,
                    1 => 30,
                    2 => 15,
                    default => 10 + ($index * 5),
                };
            }

            // Mix open / won / lost so Sales reports metrics are non-zero.
            if ($index === 1 && $hasClosedAt) {
                $payload['status'] = 0;
                $payload['closed_at'] = now()->subDays(5);
            } elseif ($index === 2 && $hasClosedAt) {
                $payload['status'] = 0;
                $payload['closed_at'] = null;
            }

            $connection->table('leads')->where('id', $lead->id)->update($payload);
        }
    }

    private function seedAssignment(Connection $connection, int $userId): void
    {
        if (! Schema::connection('tenant')->hasTable('assignment_rules')) {
            return;
        }

        $name = 'DEMO - Round-robin leads';
        $ruleId = $connection->table('assignment_rules')->where('name', $name)->value('id');

        if (! $ruleId) {
            $ruleId = $connection->table('assignment_rules')->insertGetId([
                'name' => $name,
                'entity_type' => 'leads',
                'strategy' => 'round_robin',
                'is_active' => true,
                'only_when_empty' => true,
                'priority' => 10,
                'conditions' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::connection('tenant')->hasTable('assignment_rule_users')) {
            $exists = $connection->table('assignment_rule_users')
                ->where('assignment_rule_id', $ruleId)
                ->where('user_id', $userId)
                ->exists();

            if (! $exists) {
                $connection->table('assignment_rule_users')->insert([
                    'assignment_rule_id' => $ruleId,
                    'user_id' => $userId,
                    'sort_order' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::connection('tenant')->hasTable('assignment_cursors')) {
            $exists = $connection->table('assignment_cursors')
                ->where('assignment_rule_id', $ruleId)
                ->exists();

            if (! $exists) {
                $connection->table('assignment_cursors')->insert([
                    'assignment_rule_id' => $ruleId,
                    'last_user_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * @param  array<int, int>  $personIds
     */
    private function seedConsent(Connection $connection, int $userId, array $personIds): void
    {
        if (! Schema::connection('tenant')->hasTable('person_consents')) {
            return;
        }

        $rows = [
            [$personIds[0], 'email', true, 'demo-seeder'],
            [$personIds[0], 'marketing', true, 'demo-seeder'],
            [$personIds[1], 'email', true, 'demo-seeder'],
            [$personIds[1], 'marketing', false, 'demo-seeder'],
            [$personIds[2] ?? $personIds[0], 'sms', true, 'demo-seeder'],
        ];

        foreach ($rows as [$personId, $channel, $optedIn, $source]) {
            $exists = $connection->table('person_consents')
                ->where('person_id', $personId)
                ->where('channel', $channel)
                ->exists();

            if ($exists) {
                continue;
            }

            $connection->table('person_consents')->insert([
                'person_id' => $personId,
                'channel' => $channel,
                'is_opted_in' => $optedIn,
                'source' => $source,
                'changed_by' => $userId,
                'captured_at' => now()->subDays(2),
                'notes' => 'Sample consent from tenant P0 demo seeder.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<int, int>  $personIds
     */
    private function seedBooking(Connection $connection, int $userId, array $personIds, $leadRows): void
    {
        if (! Schema::connection('tenant')->hasTable('booking_services')) {
            return;
        }

        $serviceName = 'DEMO - Discovery call';
        $serviceId = $connection->table('booking_services')->where('name', $serviceName)->value('id');

        if (! $serviceId) {
            $serviceId = $connection->table('booking_services')->insertGetId([
                'name' => $serviceName,
                'duration_minutes' => 30,
                'buffer_minutes' => 10,
                'is_active' => true,
                'description' => 'Sample public-booking service for process testing.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::connection('tenant')->hasTable('booking_availabilities')) {
            foreach ([1, 2, 3, 4, 5] as $weekday) {
                $exists = $connection->table('booking_availabilities')
                    ->where('user_id', $userId)
                    ->where('weekday', $weekday)
                    ->where('starts_at', '09:00:00')
                    ->exists();

                if ($exists) {
                    continue;
                }

                $connection->table('booking_availabilities')->insert([
                    'user_id' => $userId,
                    'weekday' => $weekday,
                    'starts_at' => '09:00:00',
                    'ends_at' => '17:00:00',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (! Schema::connection('tenant')->hasTable('bookings')) {
            return;
        }

        $hasReminder = Schema::connection('tenant')->hasColumn('bookings', 'reminder_sent_at');
        $leadId = (int) $leadRows->first()->id;
        $personId = (int) ($personIds[0] ?? 0);

        $bookings = [
            [
                'notes' => 'DEMO - Upcoming confirmed discovery',
                'starts_at' => now()->addDays(2)->setTime(10, 0),
                'ends_at' => now()->addDays(2)->setTime(10, 30),
                'status' => 'confirmed',
                'guest_name' => 'Alex Morgan',
                'guest_email' => 'alex.morgan@example.test',
                'reminder_sent_at' => null,
            ],
            [
                'notes' => 'DEMO - Pending public web booking',
                'starts_at' => now()->addDays(3)->setTime(14, 0),
                'ends_at' => now()->addDays(3)->setTime(14, 30),
                'status' => 'pending',
                'guest_name' => 'Public Guest',
                'guest_email' => 'guest@example.test',
                'reminder_sent_at' => null,
            ],
            [
                'notes' => 'DEMO - Completed discovery',
                'starts_at' => now()->subDays(3)->setTime(11, 0),
                'ends_at' => now()->subDays(3)->setTime(11, 30),
                'status' => 'completed',
                'guest_name' => 'Alex Morgan',
                'guest_email' => 'alex.morgan@example.test',
                'reminder_sent_at' => now()->subDays(3)->subHour(),
            ],
        ];

        foreach ($bookings as $booking) {
            $exists = $connection->table('bookings')->where('notes', $booking['notes'])->exists();

            if ($exists) {
                continue;
            }

            $row = [
                'booking_service_id' => $serviceId,
                'user_id' => $userId,
                'person_id' => $personId ?: null,
                'lead_id' => $leadId,
                'activity_id' => null,
                'guest_name' => $booking['guest_name'],
                'guest_email' => $booking['guest_email'],
                'guest_phone' => '+1-555-0101',
                'starts_at' => $booking['starts_at'],
                'ends_at' => $booking['ends_at'],
                'status' => $booking['status'],
                'notes' => $booking['notes'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($hasReminder) {
                $row['reminder_sent_at'] = $booking['reminder_sent_at'];
            }

            $connection->table('bookings')->insert($row);
        }
    }

    /**
     * @param  array<int, int>  $personIds
     */
    private function seedQuotesAndApprovals(Connection $connection, int $userId, array $personIds, $leadRows): void
    {
        if (! Schema::connection('tenant')->hasTable('quotes')) {
            return;
        }

        $quotes = [
            [
                'subject' => 'DEMO - Northstar CRM proposal',
                'person' => 0,
                'lead_index' => 0,
                'sub_total' => 18000,
                'grand_total' => 18000,
                'approval' => [
                    'status' => 'pending',
                    'delivery_status' => 'none',
                    'order_reference' => null,
                    'converted_at' => null,
                    'notes' => 'Awaiting manager review.',
                ],
            ],
            [
                'subject' => 'DEMO - Cedar automation quote',
                'person' => 2,
                'lead_index' => 2,
                'sub_total' => 12500,
                'grand_total' => 12500,
                'approval' => [
                    'status' => 'approved',
                    'delivery_status' => 'sent',
                    'order_reference' => null,
                    'converted_at' => null,
                    'notes' => 'Approved and delivered to customer.',
                ],
            ],
            [
                'subject' => 'DEMO - Bluebird expansion quote',
                'person' => 5,
                'lead_index' => 5,
                'sub_total' => 8400,
                'grand_total' => 8400,
                'approval' => [
                    'status' => 'approved',
                    'delivery_status' => 'converted',
                    'order_reference' => 'ORD-DEMO-1001',
                    'converted_at' => now()->subDay(),
                    'notes' => 'Converted to order for fulfillment testing.',
                ],
            ],
        ];

        $hasApprovals = Schema::connection('tenant')->hasTable('quote_approvals');
        $hasDelivery = $hasApprovals && Schema::connection('tenant')->hasColumn('quote_approvals', 'delivery_status');
        $hasLeadQuotes = Schema::connection('tenant')->hasTable('lead_quotes');

        foreach ($quotes as $quote) {
            $personId = $personIds[$quote['person']] ?? $personIds[0];
            $quoteId = $connection->table('quotes')->where('subject', $quote['subject'])->value('id');

            if (! $quoteId) {
                $quoteId = $connection->table('quotes')->insertGetId([
                    'subject' => $quote['subject'],
                    'description' => 'Sample quote created by the tenant P0 demo seeder.',
                    'discount_percent' => 0,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'adjustment_amount' => 0,
                    'sub_total' => $quote['sub_total'],
                    'grand_total' => $quote['grand_total'],
                    'expired_at' => now()->addDays(14),
                    'person_id' => $personId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($hasLeadQuotes) {
                $lead = $leadRows->values()->get($quote['lead_index']) ?? $leadRows->first();
                $pivotExists = $connection->table('lead_quotes')
                    ->where('lead_id', $lead->id)
                    ->where('quote_id', $quoteId)
                    ->exists();

                if (! $pivotExists) {
                    $connection->table('lead_quotes')->insert([
                        'lead_id' => $lead->id,
                        'quote_id' => $quoteId,
                    ]);
                }
            }

            if (! $hasApprovals) {
                continue;
            }

            $approval = $quote['approval'];
            $existing = $connection->table('quote_approvals')->where('quote_id', $quoteId)->first();

            if ($existing) {
                continue;
            }

            $row = [
                'quote_id' => $quoteId,
                'status' => $approval['status'],
                'requested_by' => $userId,
                'reviewed_by' => $approval['status'] === 'approved' ? $userId : null,
                'requested_at' => now()->subDays(2),
                'reviewed_at' => $approval['status'] === 'approved' ? now()->subDay() : null,
                'notes' => $approval['notes'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($hasDelivery) {
                $row['delivery_status'] = $approval['delivery_status'];
                $row['order_reference'] = $approval['order_reference'];
                $row['converted_at'] = $approval['converted_at'];
            }

            $connection->table('quote_approvals')->insert($row);
        }
    }

    private function seedLeadScoringRules(Connection $connection): void
    {
        if (! Schema::connection('tenant')->hasTable('lead_scoring_rules')) {
            return;
        }

        $rules = [
            [
                'name' => 'DEMO - High deal value',
                'field' => 'lead_value',
                'operator' => 'gte',
                'value' => '10000',
                'points' => 20,
                'priority' => 20,
            ],
            [
                'name' => 'DEMO - Demo titled lead',
                'field' => 'title',
                'operator' => 'contains',
                'value' => 'DEMO',
                'points' => 10,
                'priority' => 10,
            ],
            [
                'name' => 'DEMO - Open opportunity',
                'field' => 'status',
                'operator' => 'eq',
                'value' => '1',
                'points' => 5,
                'priority' => 5,
            ],
        ];

        foreach ($rules as $rule) {
            $exists = $connection->table('lead_scoring_rules')->where('name', $rule['name'])->exists();

            if ($exists) {
                continue;
            }

            $connection->table('lead_scoring_rules')->insert([
                ...$rule,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<int, int>  $personIds
     */
    private function seedActivitiesForAlerts(Connection $connection, int $userId, array $personIds, $leadRows): void
    {
        if (! Schema::connection('tenant')->hasTable('activities')) {
            return;
        }

        $activities = [
            [
                'title' => 'DEMO - Due soon follow-up',
                'schedule_from' => now()->addMinutes(30),
                'schedule_to' => now()->addMinutes(60),
                'is_done' => false,
                'comment' => 'Triggers notifications:dispatch-due follow-up window.',
            ],
            [
                'title' => 'DEMO - Overdue callback',
                'schedule_from' => now()->subDay(),
                'schedule_to' => now()->subDay()->addHour(),
                'is_done' => false,
                'comment' => 'Triggers overdue activity alerts.',
            ],
        ];

        foreach ($activities as $activity) {
            $existing = $connection->table('activities')
                ->where('title', $activity['title'])
                ->where('user_id', $userId)
                ->first();

            $activityId = $existing?->id ?? $connection->table('activities')->insertGetId([
                'title' => $activity['title'],
                'type' => 'call',
                'comment' => $activity['comment'],
                'schedule_from' => $activity['schedule_from'],
                'schedule_to' => $activity['schedule_to'],
                'is_done' => $activity['is_done'],
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $leadId = (int) $leadRows->first()->id;
            $personId = (int) ($personIds[0] ?? 0);

            if (Schema::connection('tenant')->hasTable('lead_activities')) {
                $this->attachIfMissing($connection, 'lead_activities', 'lead_id', $leadId, (int) $activityId);
            }

            if ($personId && Schema::connection('tenant')->hasTable('person_activities')) {
                $this->attachIfMissing($connection, 'person_activities', 'person_id', $personId, (int) $activityId);
            }
        }

        // Push a close-date deadline into the near window for due alerts.
        if (Schema::connection('tenant')->hasColumn('leads', 'expected_close_date')) {
            $connection->table('leads')
                ->where('id', $leadRows->first()->id)
                ->update([
                    'expected_close_date' => now()->addDay()->toDateString(),
                    'updated_at' => now(),
                ]);
        }
    }

    private function seedNotifications(Connection $connection, int $userId, $leadRows): void
    {
        if (! Schema::connection('tenant')->hasTable('user_notifications')) {
            return;
        }

        $leadId = (int) $leadRows->first()->id;
        $adminPath = trim((string) config('app.admin_path', 'admin'), '/');

        $rows = [
            [
                'type' => 'assignment',
                'title' => 'DEMO - Lead assigned: Northstar CRM rollout',
                'body' => 'You were assigned a demo lead for inbox testing.',
                'link' => '/'.$adminPath.'/leads/view/'.$leadId,
                'entity_type' => 'lead',
                'entity_id' => $leadId,
                'read_at' => null,
            ],
            [
                'type' => 'follow_up',
                'title' => 'DEMO - Upcoming follow-up',
                'body' => 'Sample unread follow-up alert.',
                'link' => '/'.$adminPath.'/activities',
                'entity_type' => 'activity',
                'entity_id' => 1,
                'read_at' => null,
            ],
            [
                'type' => 'lead_deadline',
                'title' => 'DEMO - Lead close date approaching',
                'body' => 'Sample read deadline alert.',
                'link' => '/'.$adminPath.'/leads/view/'.$leadId,
                'entity_type' => 'lead',
                'entity_id' => $leadId,
                'read_at' => now()->subHour(),
            ],
        ];

        foreach ($rows as $row) {
            $exists = $connection->table('user_notifications')
                ->where('user_id', $userId)
                ->where('type', $row['type'])
                ->where('title', $row['title'])
                ->exists();

            if ($exists) {
                continue;
            }

            $connection->table('user_notifications')->insert([
                'user_id' => $userId,
                'type' => $row['type'],
                'title' => $row['title'],
                'body' => $row['body'],
                'link' => $row['link'],
                'entity_type' => $row['entity_type'],
                'entity_id' => $row['entity_id'],
                'meta' => json_encode(['demo' => true]),
                'read_at' => $row['read_at'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function attachIfMissing(Connection $connection, string $table, string $relatedKey, int $relatedId, int $activityId): void
    {
        $exists = $connection->table($table)
            ->where($relatedKey, $relatedId)
            ->where('activity_id', $activityId)
            ->exists();

        if (! $exists) {
            $connection->table($table)->insert([
                $relatedKey => $relatedId,
                'activity_id' => $activityId,
            ]);
        }
    }
}
