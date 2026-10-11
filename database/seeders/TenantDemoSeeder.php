<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantDemoSeeder extends Seeder
{
    private const ORGANIZATIONS = [
        'Demo Company - Northstar Labs',
        'Demo Company - Cedar & Stone',
        'Demo Company - Bluebird Works',
    ];

    private const CONTACTS = [
        ['name' => 'Alex Morgan', 'email' => 'alex.morgan@example.test', 'job_title' => 'Operations Director', 'organization' => 0],
        ['name' => 'Samira Khan', 'email' => 'samira.khan@example.test', 'job_title' => 'Finance Manager', 'organization' => 0],
        ['name' => 'Jordan Lee', 'email' => 'jordan.lee@example.test', 'job_title' => 'Founder', 'organization' => 1],
        ['name' => 'Taylor Reed', 'email' => 'taylor.reed@example.test', 'job_title' => 'Sales Lead', 'organization' => 1],
        ['name' => 'Casey Patel', 'email' => 'casey.patel@example.test', 'job_title' => 'IT Manager', 'organization' => 2],
        ['name' => 'Riley Chen', 'email' => 'riley.chen@example.test', 'job_title' => 'Product Director', 'organization' => 2],
    ];

    private const LEADS = [
        ['title' => 'DEMO - Northstar CRM rollout', 'contact' => 0, 'value' => 18000, 'source' => 1],
        ['title' => 'DEMO - Northstar support renewal', 'contact' => 1, 'value' => 7500, 'source' => 4],
        ['title' => 'DEMO - Cedar sales automation', 'contact' => 2, 'value' => 12500, 'source' => 2],
        ['title' => 'DEMO - Cedar onboarding package', 'contact' => 3, 'value' => 5200, 'source' => 0],
        ['title' => 'DEMO - Bluebird integration', 'contact' => 4, 'value' => 9600, 'source' => 3],
        ['title' => 'DEMO - Bluebird product expansion', 'contact' => 5, 'value' => 8400, 'source' => 1],
    ];

    public function run(): void
    {
        DB::setDefaultConnection('tenant');

        $this->call(TenantCrmSeeder::class);

        $connection = DB::connection('tenant');
        $userId = $connection->table('users')->orderBy('id')->value('id');

        if (! $userId) {
            throw new RuntimeException('This tenant has no CRM users. Provision a tenant administrator before adding demo records.');
        }

        $pipelineId = $connection->table('lead_pipelines')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');
        $stageId = $pipelineId
            ? $connection->table('lead_pipeline_stages')
                ->where('lead_pipeline_id', $pipelineId)
                ->where('code', 'new')
                ->value('id')
            : null;
        $typeId = $connection->table('lead_types')->orderBy('id')->value('id');
        $sourceIds = $connection->table('lead_sources')->orderBy('id')->pluck('id')->all();

        if (! $pipelineId || ! $stageId || ! $typeId || count($sourceIds) < 5) {
            throw new RuntimeException('Tenant CRM lookup data is incomplete. Run tenant migrations and baseline seeding before adding demo records.');
        }

        $connection->transaction(function () use ($connection, $userId, $pipelineId, $stageId, $typeId, $sourceIds): void {
            $now = now();
            $organizationIds = [];

            foreach (self::ORGANIZATIONS as $name) {
                $organization = $connection->table('organizations')->where('name', $name)->first();

                $organizationIds[] = $organization?->id ?? $connection->table('organizations')->insertGetId([
                    'name' => $name,
                    'address' => json_encode(['country' => 'United States']),
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $personIds = [];

            foreach (self::CONTACTS as $index => $contact) {
                $uniqueId = 'tenant-demo-seeder|contact-'.($index + 1);
                $person = $connection->table('persons')->where('unique_id', $uniqueId)->first();

                $personIds[] = $person?->id ?? $connection->table('persons')->insertGetId([
                    'name' => $contact['name'],
                    'emails' => json_encode([['value' => $contact['email'], 'label' => 'work']]),
                    'contact_numbers' => json_encode([['value' => '+1-555-010'.($index + 1), 'label' => 'work']]),
                    'organization_id' => $organizationIds[$contact['organization']],
                    'job_title' => $contact['job_title'],
                    'user_id' => $userId,
                    'unique_id' => $uniqueId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $leadIds = [];

            foreach (self::LEADS as $lead) {
                $existingLead = $connection->table('leads')->where('title', $lead['title'])->first();
                $leadIds[] = $existingLead?->id ?? $connection->table('leads')->insertGetId([
                    'title' => $lead['title'],
                    'description' => 'Sample opportunity created by the tenant demo seeder.',
                    'lead_value' => $lead['value'],
                    'status' => true,
                    'user_id' => $userId,
                    'person_id' => $personIds[$lead['contact']],
                    'lead_source_id' => $sourceIds[$lead['source']],
                    'lead_type_id' => $typeId,
                    'lead_pipeline_id' => $pipelineId,
                    'lead_pipeline_stage_id' => $stageId,
                    'expected_close_date' => now()->addDays(30)->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $activityTitle = 'DEMO - Introductory customer calls';
            $activity = $connection->table('activities')
                ->where('title', $activityTitle)
                ->where('user_id', $userId)
                ->first();

            $activityId = $activity?->id ?? $connection->table('activities')->insertGetId([
                'title' => $activityTitle,
                'type' => 'call',
                'comment' => 'Sample follow-up activity created by the tenant demo seeder.',
                'schedule_from' => now()->addDay(),
                'schedule_to' => now()->addDay()->addHour(),
                'is_done' => false,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($leadIds as $leadId) {
                $this->attachIfMissing($connection, 'lead_activities', 'lead_id', $leadId, $activityId);
            }

            foreach ($personIds as $personId) {
                $this->attachIfMissing($connection, 'person_activities', 'person_id', $personId, $activityId);
                $this->attachIfMissing($connection, 'activity_participants', 'person_id', $personId, $activityId);
            }
        });
    }

    private function attachIfMissing($connection, string $table, string $relatedKey, int $relatedId, int $activityId): void
    {
        $pivot = $connection->table($table);

        if (! $pivot->where($relatedKey, $relatedId)->where('activity_id', $activityId)->exists()) {
            $pivot->insert([
                $relatedKey => $relatedId,
                'activity_id' => $activityId,
            ]);
        }
    }
}
