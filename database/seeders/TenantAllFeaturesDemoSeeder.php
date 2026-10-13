<?php

namespace Database\Seeders;

use Illuminate\Database\Connection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\UnifiedInbox\Database\Seeders\UnifiedInboxDemoSeeder;

/**
 * Idempotent dummy data for every tenant feature, linked to the demo CRM
 * records. Safe to re-run. Does not delete existing rows.
 */
class TenantAllFeaturesDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::setDefaultConnection('tenant');

        $this->call(TenantDemoSeeder::class);

        $connection = DB::connection('tenant');
        $userId = (int) $connection->table('users')->orderBy('id')->value('id');
        $persons = $connection->table('persons')
            ->where('unique_id', 'like', 'tenant-demo-seeder|contact-%')
            ->orderBy('id')
            ->get(['id', 'name', 'organization_id', 'emails']);
        $leads = $connection->table('leads')
            ->where('title', 'like', 'DEMO - %')
            ->orderBy('id')
            ->get(['id', 'title', 'person_id']);

        $connection->transaction(function () use ($connection, $userId, $persons, $leads): void {
            $this->seedGroup($connection, $userId);
            $productIds = $this->seedProducts($connection);
            $warehouse = $this->seedWarehouse($connection, $productIds);
            $tagIds = $this->seedTags($connection, $userId, $persons, $leads, $productIds, $warehouse['id'] ?? null);
            $templateId = $this->ensureEmailTemplate($connection);
            $eventId = $this->seedMarketingEvent($connection);
            $this->seedMarketingCampaign($connection, $templateId, $eventId);
            $formId = $this->seedWebForm($connection);
            $this->seedWebhook($connection);
            $emailId = $this->seedMail($connection, $persons, $leads);
            $this->attachTag($connection, 'email_tags', 'email_id', $emailId, $tagIds[0] ?? null);
            $this->seedQuoteItems($connection, $productIds);
            $this->seedWorkflow($connection);
            $this->seedImport($connection);
            $this->seedSavedFilter($connection, $userId);
            $this->seedMarketingAutomation($connection, $persons, $leads, $formId);
            $this->seedCallLogs($connection, $userId, $persons, $leads);
            $this->seedGoogleContacts($connection, $userId, $persons);
            $this->seedKnowledge($connection, $userId);
        });

        $this->seedUnifiedChannels($userId, $persons, $leads);
    }

    private function seedGroup(Connection $connection, int $userId): void
    {
        if (! $this->has('groups')) {
            return;
        }

        $groupId = $this->firstOrCreate($connection, 'groups', 'name', 'DEMO - Sales team', [
            'name' => 'DEMO - Sales team',
            'description' => 'Sample group for the all-features demo seeder.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $this->has('user_groups')) {
            return;
        }

        $exists = $connection->table('user_groups')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->exists();

        if (! $exists) {
            $connection->table('user_groups')->insert([
                'group_id' => $groupId,
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * @return array<int, int>
     */
    private function seedProducts(Connection $connection): array
    {
        if (! $this->has('products')) {
            return [];
        }

        $products = [
            ['sku' => 'DEMO-CRM', 'name' => 'DEMO - CRM workspace', 'description' => 'Sample CRM subscription.', 'quantity' => 25, 'price' => 18000],
            ['sku' => 'DEMO-SUPPORT', 'name' => 'DEMO - Support plan', 'description' => 'Sample support renewal.', 'quantity' => 40, 'price' => 7500],
            ['sku' => 'DEMO-ONBOARD', 'name' => 'DEMO - Onboarding package', 'description' => 'Sample onboarding service.', 'quantity' => 12, 'price' => 5200],
        ];

        $ids = [];

        foreach ($products as $product) {
            $ids[] = $this->firstOrCreate($connection, 'products', 'sku', $product['sku'], [
                ...$product,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array{id: ?int, location_id: ?int}
     */
    private function seedWarehouse(Connection $connection, array $productIds): array
    {
        if (! $this->has('warehouses')) {
            return ['id' => null, 'location_id' => null];
        }

        $warehouseId = $this->firstOrCreate($connection, 'warehouses', 'name', 'DEMO - Main warehouse', [
            'name' => 'DEMO - Main warehouse',
            'description' => 'Sample warehouse for inventory screens.',
            'contact_name' => 'Alex Morgan',
            'contact_emails' => json_encode([['value' => 'warehouse@example.test', 'label' => 'work']]),
            'contact_numbers' => json_encode([['value' => '+1-555-0199', 'label' => 'work']]),
            'contact_address' => json_encode(['address' => '100 Demo Street', 'city' => 'Austin', 'country' => 'United States']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $locationId = null;

        if ($this->has('warehouse_locations')) {
            $existing = $connection->table('warehouse_locations')
                ->where('warehouse_id', $warehouseId)
                ->where('name', 'Aisle A')
                ->value('id');

            $locationId = $existing ? (int) $existing : (int) $connection->table('warehouse_locations')->insertGetId([
                'name' => 'Aisle A',
                'warehouse_id' => $warehouseId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($this->has('product_inventories')) {
            foreach ($productIds as $index => $productId) {
                $exists = $connection->table('product_inventories')
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $connection->table('product_inventories')->insert($this->only('product_inventories', [
                    'in_stock' => 10 + ($index * 5),
                    'allocated' => $index,
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'warehouse_location_id' => $locationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        if ($this->has('warehouse_activities') && $this->has('activities')) {
            $activityId = $connection->table('activities')->orderBy('id')->value('id');

            if ($activityId) {
                $exists = $connection->table('warehouse_activities')
                    ->where('warehouse_id', $warehouseId)
                    ->where('activity_id', $activityId)
                    ->exists();

                if (! $exists) {
                    $connection->table('warehouse_activities')->insert([
                        'warehouse_id' => $warehouseId,
                        'activity_id' => $activityId,
                    ]);
                }
            }
        }

        return ['id' => $warehouseId, 'location_id' => $locationId];
    }

    /**
     * @return array<int, int>
     */
    private function seedTags(Connection $connection, int $userId, $persons, $leads, array $productIds, ?int $warehouseId): array
    {
        if (! $this->has('tags')) {
            return [];
        }

        $names = ['DEMO - Priority', 'DEMO - Customer', 'DEMO - Inventory'];
        $colors = ['#0f766e', '#2563eb', '#b45309'];
        $ids = [];

        foreach ($names as $index => $name) {
            $ids[] = $this->firstOrCreate($connection, 'tags', 'name', $name, [
                'name' => $name,
                'color' => $colors[$index],
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $person = $persons->first();
        $lead = $leads->first();

        $this->attachTag($connection, 'person_tags', 'person_id', $person?->id, $ids[1] ?? null);
        $this->attachTag($connection, 'lead_tags', 'lead_id', $lead?->id, $ids[0] ?? null);
        $this->attachTag($connection, 'product_tags', 'product_id', $productIds[0] ?? null, $ids[2] ?? null);
        $this->attachTag($connection, 'warehouse_tags', 'warehouse_id', $warehouseId, $ids[2] ?? null);

        return $ids;
    }

    private function ensureEmailTemplate(Connection $connection): ?int
    {
        if (! $this->has('email_templates')) {
            return null;
        }

        $existing = $connection->table('email_templates')->orderBy('id')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) $connection->table('email_templates')->insertGetId($this->only('email_templates', [
            'name' => 'DEMO - Outreach',
            'subject' => 'DEMO - A note from the team',
            'content' => '<p>Sample email template created by the all-features demo seeder.</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function seedMarketingEvent(Connection $connection): ?int
    {
        if (! $this->has('marketing_events')) {
            return null;
        }

        return $this->firstOrCreate($connection, 'marketing_events', 'name', 'DEMO - Product webinar', [
            'name' => 'DEMO - Product webinar',
            'description' => 'Sample marketing event for campaign linking.',
            'date' => now()->addDays(14)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedMarketingCampaign(Connection $connection, ?int $templateId, ?int $eventId): void
    {
        if (! $this->has('marketing_campaigns') || ! $templateId || ! $eventId) {
            return;
        }

        $name = 'DEMO - Webinar invite';

        if ($connection->table('marketing_campaigns')->where('name', $name)->exists()) {
            return;
        }

        $connection->table('marketing_campaigns')->insert($this->only('marketing_campaigns', [
            'name' => $name,
            'subject' => 'Join the DEMO product webinar',
            'status' => 0,
            'type' => 'email',
            'mail_to' => 'lead',
            'spooling' => null,
            'marketing_template_id' => $templateId,
            'marketing_event_id' => $eventId,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function seedWebForm(Connection $connection): ?int
    {
        if (! $this->has('web_forms')) {
            return null;
        }

        $pipelineId = $this->has('lead_pipelines')
            ? $connection->table('lead_pipelines')->orderByDesc('is_default')->orderBy('id')->value('id')
            : null;

        $formId = $this->firstOrCreate($connection, 'web_forms', 'form_id', 'demo-contact-form', [
            'form_id' => 'demo-contact-form',
            'title' => 'DEMO - Contact form',
            'description' => 'Sample web form that creates a lead.',
            'submit_button_label' => 'Send',
            'submit_success_action' => 'message',
            'submit_success_content' => 'Thanks, we will be in touch.',
            'create_lead' => true,
            'lead_pipeline_id' => $pipelineId,
            'background_color' => '#f8fafc',
            'form_background_color' => '#ffffff',
            'form_title_color' => '#0f172a',
            'form_submit_button_color' => '#0f766e',
            'attribute_label_color' => '#334155',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $this->has('web_form_attributes') || ! $this->has('attributes')) {
            return $formId;
        }

        $attributeId = $connection->table('attributes')
            ->where('entity_type', 'leads')
            ->where('code', 'title')
            ->value('id');

        if (! $attributeId) {
            return $formId;
        }

        $exists = $connection->table('web_form_attributes')
            ->where('web_form_id', $formId)
            ->where('attribute_id', $attributeId)
            ->exists();

        if (! $exists) {
            $connection->table('web_form_attributes')->insert([
                'name' => 'Title',
                'placeholder' => 'What do you need?',
                'is_required' => true,
                'is_hidden' => false,
                'sort_order' => 1,
                'attribute_id' => $attributeId,
                'web_form_id' => $formId,
            ]);
        }

        return $formId;
    }

    private function seedWebhook(Connection $connection): void
    {
        if (! $this->has('webhooks')) {
            return;
        }

        $name = 'DEMO - Lead created';

        if ($connection->table('webhooks')->where('name', $name)->exists()) {
            return;
        }

        $connection->table('webhooks')->insert($this->only('webhooks', [
            'name' => $name,
            'entity_type' => 'leads',
            'description' => 'Sample webhook. The endpoint is not a live service.',
            'method' => 'POST',
            'end_point' => 'https://example.test/hooks/demo-lead',
            'query_params' => json_encode([]),
            'headers' => json_encode([['key' => 'Accept', 'value' => 'application/json']]),
            'payload_type' => 'default',
            'raw_payload_type' => 'json',
            'payload' => json_encode(['lead_id' => '{%leads.id%}']),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function seedMail(Connection $connection, $persons, $leads): ?int
    {
        if (! $this->has('emails')) {
            return null;
        }

        $messageId = 'demo-mail-northstar@example.test';
        $existing = $connection->table('emails')->where('message_id', $messageId)->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $person = $persons->first();
        $lead = $leads->first();

        return (int) $connection->table('emails')->insertGetId($this->only('emails', [
            'subject' => 'DEMO - Northstar rollout follow-up',
            'source' => 'web',
            'user_type' => 'admin',
            'name' => 'Alex Morgan',
            'reply' => 'Thanks for the intro call. Sharing the sample proposal next.',
            'is_read' => false,
            'folders' => json_encode(['sent']),
            'from' => json_encode(['email' => 'admin@example.test', 'name' => 'Tenant Admin']),
            'sender' => json_encode(['email' => 'admin@example.test', 'name' => 'Tenant Admin']),
            'reply_to' => json_encode(['email' => 'admin@example.test']),
            'cc' => json_encode([]),
            'bcc' => json_encode([]),
            'unique_id' => $messageId,
            'message_id' => $messageId,
            'reference_ids' => json_encode([$messageId]),
            'person_id' => $person?->id,
            'lead_id' => $lead?->id,
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    /**
     * @param  array<int, int>  $productIds
     */
    private function seedQuoteItems(Connection $connection, array $productIds): void
    {
        if (! $this->has('quote_items') || ! $this->has('quotes') || $productIds === []) {
            return;
        }

        $quotes = $connection->table('quotes')->where('subject', 'like', 'DEMO - %')->orderBy('id')->get(['id', 'subject']);

        foreach ($quotes as $index => $quote) {
            $productId = $productIds[$index] ?? $productIds[0];
            $product = $connection->table('products')->where('id', $productId)->first();

            if (! $product) {
                continue;
            }

            $exists = $connection->table('quote_items')
                ->where('quote_id', $quote->id)
                ->where('product_id', $productId)
                ->exists();

            if ($exists) {
                continue;
            }

            $price = (float) ($product->price ?? 0);

            $connection->table('quote_items')->insert($this->only('quote_items', [
                'sku' => $product->sku,
                'name' => $product->name,
                'quantity' => 1,
                'price' => $price,
                'coupon_code' => null,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_percent' => 0,
                'tax_amount' => 0,
                'total' => $price,
                'product_id' => $productId,
                'quote_id' => $quote->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function seedWorkflow(Connection $connection): void
    {
        if (! $this->has('workflows')) {
            return;
        }

        $name = 'DEMO - Email owner when a lead is created';

        if ($connection->table('workflows')->where('name', $name)->exists()) {
            return;
        }

        $connection->table('workflows')->insert($this->only('workflows', [
            'name' => $name,
            'description' => 'Sample workflow created by the all-features demo seeder.',
            'entity_type' => 'leads',
            'event' => 'lead.create.after',
            'condition_type' => 'and',
            'conditions' => json_encode([]),
            'actions' => json_encode([['id' => 'send_email_to_sales_owner', 'value' => '1']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function seedImport(Connection $connection): void
    {
        if (! $this->has('imports')) {
            return;
        }

        $path = 'demo/imports/leads.csv';

        if ($connection->table('imports')->where('file_path', $path)->exists()) {
            return;
        }

        $connection->table('imports')->insert($this->only('imports', [
            'state' => 'completed',
            'process_in_queue' => false,
            'type' => 'leads',
            'action' => 'append',
            'validation_strategy' => 'skip',
            'allowed_errors' => 0,
            'processed_rows_count' => 6,
            'invalid_rows_count' => 0,
            'errors_count' => 0,
            'errors' => json_encode([]),
            'field_separator' => ',',
            'file_path' => $path,
            'error_file_path' => null,
            'summary' => json_encode(['created' => 6, 'demo' => true]),
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(50),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function seedSavedFilter(Connection $connection, int $userId): void
    {
        if (! $this->has('datagrid_saved_filters')) {
            return;
        }

        $exists = $connection->table('datagrid_saved_filters')
            ->where('user_id', $userId)
            ->where('name', 'DEMO - Open leads')
            ->exists();

        if ($exists) {
            return;
        }

        $connection->table('datagrid_saved_filters')->insert([
            'user_id' => $userId,
            'name' => 'DEMO - Open leads',
            'src' => 'leads',
            'applied' => json_encode(['status' => 1]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedMarketingAutomation(Connection $connection, $persons, $leads, ?int $formId): void
    {
        if (! $this->has('ma_audiences') || ! $this->has('ma_campaigns')) {
            return;
        }

        $person = $persons->first();
        $lead = $leads->first();

        $audienceId = $this->firstOrCreate($connection, 'ma_audiences', 'name', 'DEMO - Decision makers', [
            'name' => 'DEMO - Decision makers',
            'rules' => json_encode([['field' => 'job_title', 'operator' => 'contains', 'value' => 'Director']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $campaignId = $this->firstOrCreate($connection, 'ma_campaigns', 'name', 'DEMO - Spring outreach', [
            'name' => 'DEMO - Spring outreach',
            'status' => 'active',
            'budget' => 5000,
            'spent' => 800,
            'currency' => 'USD',
            'audience_id' => $audienceId,
            'source_name' => 'Demo webinar',
            'utm_campaign' => 'demo-spring',
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->addDays(21),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($this->has('ma_email_sequences')) {
            $sequenceId = $this->firstOrCreate($connection, 'ma_email_sequences', 'name', 'DEMO - Welcome sequence', [
                'campaign_id' => $campaignId,
                'audience_id' => $audienceId,
                'name' => 'DEMO - Welcome sequence',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stepId = null;

            if ($this->has('ma_email_steps')) {
                $existingStep = $connection->table('ma_email_steps')
                    ->where('sequence_id', $sequenceId)
                    ->where('sort', 0)
                    ->value('id');

                $stepId = $existingStep ? (int) $existingStep : (int) $connection->table('ma_email_steps')->insertGetId([
                    'sequence_id' => $sequenceId,
                    'sort' => 0,
                    'delay_minutes' => 0,
                    'subject' => 'Welcome to the demo workspace',
                    'body' => 'This is a sample sequence email. Refunds are processed within 5 business days.',
                    'link_url' => 'https://example.test/demo',
                    'link_label' => 'View details',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($person && $this->has('ma_email_enrollments')) {
                $enrollment = $connection->table('ma_email_enrollments')
                    ->where('sequence_id', $sequenceId)
                    ->where('person_id', $person->id)
                    ->first();

                $enrollmentId = $enrollment?->id ?? $connection->table('ma_email_enrollments')->insertGetId([
                    'sequence_id' => $sequenceId,
                    'person_id' => $person->id,
                    'status' => 'active',
                    'current_step' => 1,
                    'attempts' => 1,
                    'next_run_at' => now()->addDay(),
                    'unsubscribe_token' => Str::random(40),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($this->has('ma_email_messages')) {
                    $token = 'demo-sequence-message-1';
                    $exists = $connection->table('ma_email_messages')->where('token', $token)->exists();

                    if (! $exists) {
                        $email = 'demo@example.test';
                        $decoded = json_decode((string) $person->emails, true);
                        if (is_array($decoded) && isset($decoded[0]['value'])) {
                            $email = (string) $decoded[0]['value'];
                        }

                        $connection->table('ma_email_messages')->insert([
                            'enrollment_id' => $enrollmentId,
                            'step_id' => $stepId,
                            'person_id' => $person->id,
                            'lead_id' => $lead?->id,
                            'email' => $email,
                            'subject' => 'Welcome to the demo workspace',
                            'link_url' => 'https://example.test/demo',
                            'token' => $token,
                            'status' => 'sent',
                            'scheduled_at' => now()->subDay(),
                            'sent_at' => now()->subDay(),
                            'opened_at' => now()->subHours(20),
                            'clicked_at' => null,
                            'error' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        if ($this->has('ma_workflows')) {
            $workflowId = $this->firstOrCreate($connection, 'ma_workflows', 'name', 'DEMO - Branch on lead value', [
                'name' => 'DEMO - Branch on lead value',
                'entity_type' => 'leads',
                'event' => 'lead.create.after',
                'condition_type' => 'and',
                'conditions' => json_encode([['attribute' => 'lead_value', 'operator' => 'gte', 'value' => '1000']]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($this->has('ma_workflow_actions')) {
                $exists = $connection->table('ma_workflow_actions')
                    ->where('workflow_id', $workflowId)
                    ->where('branch', 'yes')
                    ->exists();

                if (! $exists) {
                    $connection->table('ma_workflow_actions')->insert([
                        'workflow_id' => $workflowId,
                        'branch' => 'yes',
                        'sort' => 0,
                        'type' => 'notification',
                        'config' => json_encode(['title' => 'High-value demo lead']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if ($lead && $this->has('ma_attributions')) {
            $exists = $connection->table('ma_attributions')
                ->where('lead_id', $lead->id)
                ->where('utm_campaign', 'demo-spring')
                ->exists();

            if (! $exists) {
                $connection->table('ma_attributions')->insert($this->only('ma_attributions', [
                    'lead_id' => $lead->id,
                    'person_id' => $person?->id,
                    'campaign_id' => $campaignId,
                    'web_form_id' => $formId,
                    'utm_source' => 'demo',
                    'utm_medium' => 'email',
                    'utm_campaign' => 'demo-spring',
                    'utm_content' => 'welcome',
                    'utm_term' => null,
                    'referrer' => 'https://example.test/webinar',
                    'landing_url' => 'https://example.test/demo-contact-form',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        if ($lead && $this->has('ma_engagement_scores')) {
            $exists = $connection->table('ma_engagement_scores')->where('lead_id', $lead->id)->exists();

            if (! $exists) {
                $connection->table('ma_engagement_scores')->insert([
                    'lead_id' => $lead->id,
                    'points' => 15,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if ($lead && $this->has('ma_engagement_events')) {
            $exists = $connection->table('ma_engagement_events')
                ->where('lead_id', $lead->id)
                ->where('event_type', 'email_open')
                ->where('source_key', 'demo-sequence-message-1')
                ->exists();

            if (! $exists) {
                $connection->table('ma_engagement_events')->insert([
                    'lead_id' => $lead->id,
                    'event_type' => 'email_open',
                    'source_key' => 'demo-sequence-message-1',
                    'points' => 5,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedCallLogs(Connection $connection, int $userId, $persons, $leads): void
    {
        if (! $this->has('call_logs')) {
            return;
        }

        $person = $persons->first();
        $lead = $leads->first();
        $rows = [
            [
                'provider_call_id' => 'demo-call-0001',
                'direction' => 'inbound',
                'status' => 'completed',
                'from_number' => '+15550101',
                'to_number' => '+15550001',
                'duration_seconds' => 320,
            ],
            [
                'provider_call_id' => 'demo-call-0002',
                'direction' => 'outbound',
                'status' => 'no-answer',
                'from_number' => '+15550001',
                'to_number' => '+15550102',
                'duration_seconds' => 0,
            ],
        ];

        foreach ($rows as $row) {
            if ($connection->table('call_logs')->where('provider_call_id', $row['provider_call_id'])->exists()) {
                continue;
            }

            $connection->table('call_logs')->insert($this->only('call_logs', [
                ...$row,
                'contact_id' => $person?->id,
                'lead_id' => $lead?->id,
                'user_id' => $userId,
                'recording_url' => null,
                'recording_sid' => null,
                'started_at' => now()->subHours(3),
                'ended_at' => now()->subHours(3)->addMinutes(5),
                'provider_payload' => json_encode(['demo' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function seedGoogleContacts(Connection $connection, int $userId, $persons): void
    {
        if (! $this->has('google_contact_accounts')) {
            return;
        }

        $accountExists = $connection->table('google_contact_accounts')->where('user_id', $userId)->exists();

        if (! $accountExists) {
            $connection->table('google_contact_accounts')->insert([
                'user_id' => $userId,
                'google_email' => 'demo.google@example.test',
                'access_token' => 'demo-not-a-real-access-token',
                'refresh_token' => 'demo-not-a-real-refresh-token',
                'expires_at' => now()->addHour(),
                'scopes' => 'https://www.googleapis.com/auth/contacts',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! $this->has('contact_export_batches') || ! $this->has('contact_export_batch_items') || ! $persons->first()) {
            return;
        }

        $personId = (int) $persons->first()->id;
        $existingItem = $connection->table('contact_export_batch_items')
            ->where('person_id', $personId)
            ->where('google_resource_name', 'people/demo-contact-1')
            ->exists();

        if ($existingItem) {
            return;
        }

        $batchId = $connection->table('contact_export_batches')->insertGetId([
            'user_id' => $userId,
            'state' => 'completed',
            'total_count' => 1,
            'exported_count' => 1,
            'duplicate_count' => 0,
            'failed_count' => 0,
            'error_file_path' => null,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(50),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $connection->table('contact_export_batch_items')->insert([
            'batch_id' => $batchId,
            'person_id' => $personId,
            'status' => 'exported',
            'google_resource_name' => 'people/demo-contact-1',
            'error_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedKnowledge(Connection $connection, int $userId): void
    {
        if (! $this->has('ai_knowledge_documents')) {
            return;
        }

        $path = 'demo/ai-knowledge/refund-policy.txt';
        $content = 'Refunds are processed within 5 business days. Returns are accepted within 30 days for unused items.';
        $documentId = $connection->table('ai_knowledge_documents')->where('storage_path', $path)->value('id');

        if (! $documentId) {
            $documentId = $connection->table('ai_knowledge_documents')->insertGetId($this->only('ai_knowledge_documents', [
                'original_filename' => 'refund-policy.txt',
                'storage_path' => $path,
                'mime_type' => 'text/plain',
                'extension' => 'txt',
                'size_bytes' => strlen($content),
                'sha256' => hash('sha256', $content),
                'status' => 'ready',
                'uploaded_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        if (! $this->has('ai_knowledge_chunks')) {
            return;
        }

        $exists = $connection->table('ai_knowledge_chunks')
            ->where('document_id', $documentId)
            ->where('chunk_index', 0)
            ->exists();

        if ($exists) {
            return;
        }

        $connection->table('ai_knowledge_chunks')->insert([
            'document_id' => $documentId,
            'chunk_index' => 0,
            'content' => $content,
            'content_hash' => hash('sha256', $content),
            'metadata' => json_encode([
                'document_id' => $documentId,
                'source' => 'refund-policy.txt',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedUnifiedChannels(int $userId, $persons, $leads): void
    {
        if (! $this->has('unified_inbox_connections') || ! $this->has('unified_inbox_conversations') || ! $this->has('unified_inbox_messages')) {
            return;
        }

        $links = [];

        foreach ($persons->values() as $index => $person) {
            $lead = $leads->firstWhere('person_id', $person->id) ?? $leads->get($index);

            $links[] = [
                'person_id' => (int) $person->id,
                'lead_id' => $lead ? (int) $lead->id : null,
                'organization_id' => $person->organization_id ? (int) $person->organization_id : null,
                'user_id' => $userId,
            ];
        }

        app(UnifiedInboxDemoSeeder::class)->seedChannels($links);
    }

    private function attachTag(Connection $connection, string $table, string $foreignKey, mixed $foreignId, ?int $tagId): void
    {
        if (! $foreignId || ! $tagId || ! $this->has($table)) {
            return;
        }

        $exists = $connection->table($table)
            ->where($foreignKey, $foreignId)
            ->where('tag_id', $tagId)
            ->exists();

        if (! $exists) {
            $connection->table($table)->insert([
                $foreignKey => $foreignId,
                'tag_id' => $tagId,
            ]);
        }
    }

    private function firstOrCreate(Connection $connection, string $table, string $column, mixed $value, array $payload): int
    {
        $existing = $connection->table($table)->where($column, $value)->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) $connection->table($table)->insertGetId($this->only($table, $payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function only(string $table, array $payload): array
    {
        $columns = Schema::connection('tenant')->getColumnListing($table);

        return array_intersect_key($payload, array_flip($columns));
    }

    private function has(string $table): bool
    {
        return Schema::connection('tenant')->hasTable($table);
    }
}
