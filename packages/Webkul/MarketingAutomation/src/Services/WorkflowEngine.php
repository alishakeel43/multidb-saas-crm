<?php

namespace Webkul\MarketingAutomation\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\MarketingAutomation\Models\Workflow;
use Webkul\MarketingAutomation\Models\WorkflowAction;
use Webkul\MarketingAutomation\Models\WorkflowRun;
use Webkul\MarketingAutomation\Support\Catalog;
use Webkul\MarketingAutomation\Support\CrmEntity;
use Webkul\Notification\Services\NotificationService;

class WorkflowEngine
{
    public function __construct(
        private ConditionEvaluator $conditions,
        private EmailDispatcher $mail,
    ) {}

    public function handle(string $event, object $entity): void
    {
        if (! Schema::hasTable('ma_workflows')) {
            return;
        }

        $context = CrmEntity::context($entity);

        if ($context === null) {
            return;
        }

        $workflows = Workflow::query()
            ->where('is_active', true)
            ->where('entity_type', $context['entity_type'])
            ->where('event', $event)
            ->with('actions')
            ->get();

        foreach ($workflows as $workflow) {
            try {
                $matched = $this->conditions->matches(
                    $workflow->condition_type,
                    $workflow->conditions,
                    $context['attributes'],
                );

                $this->execute($workflow, $matched ? 'when_match' : 'when_else', $context, 0, null);
            } catch (Throwable $exception) {
                Log::warning('marketing_automation.workflow_failed', [
                    'workflow_id' => $workflow->id,
                    'event' => $event,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function processDue(): int
    {
        if (! Schema::hasTable('ma_workflow_runs')) {
            return 0;
        }

        $processed = 0;

        $runs = WorkflowRun::query()
            ->where('status', 'pending')
            ->where('run_at', '<=', now())
            ->orderBy('id')
            ->limit(50)
            ->get();

        foreach ($runs as $run) {
            $run->forceFill(['status' => 'processing'])->save();
            $workflow = Workflow::query()->with('actions')->find($run->workflow_id);

            if (! $workflow || ! $workflow->is_active) {
                $run->forceFill(['status' => 'cancelled', 'finished_at' => now()])->save();

                continue;
            }

            try {
                $this->execute(
                    $workflow,
                    $run->branch,
                    $run->context ?? [],
                    (int) $run->next_action_index,
                    $run,
                );
                $processed++;
            } catch (Throwable $exception) {
                $run->forceFill([
                    'status' => 'failed',
                    'last_error' => $exception->getMessage(),
                    'finished_at' => now(),
                ])->save();
            }
        }

        return $processed;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(Workflow $workflow, string $branch, array $context, int $start, ?WorkflowRun $run): void
    {
        $actions = $workflow->actions
            ->where('branch', $branch)
            ->sortBy('sort')
            ->values()
            ->slice($start)
            ->values();

        foreach ($actions as $offset => $action) {
            if ($action->type === 'delay') {
                $minutes = max(1, (int) ($action->config['minutes'] ?? 1));
                $this->schedule($workflow, $branch, $context, $start + $offset + 1, now()->addMinutes($minutes), $run);

                return;
            }

            $this->perform($action, $context);
        }

        if ($run) {
            $run->forceFill([
                'status' => 'done',
                'finished_at' => now(),
                'last_error' => null,
            ])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function schedule(Workflow $workflow, string $branch, array $context, int $nextIndex, $runAt, ?WorkflowRun $run): void
    {
        $payload = [
            'workflow_id' => $workflow->id,
            'branch' => $branch,
            'next_action_index' => $nextIndex,
            'context' => $context,
            'status' => 'pending',
            'run_at' => $runAt,
            'finished_at' => null,
            'last_error' => null,
        ];

        if ($run) {
            $run->forceFill($payload)->save();

            return;
        }

        WorkflowRun::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function perform(WorkflowAction $action, array $context): void
    {
        $config = $action->config ?? [];

        match ($action->type) {
            'task' => $this->createTask($context, $config),
            'notification' => $this->notify($context, $config),
            'email' => $this->mail->send(
                $context,
                (string) ($config['subject'] ?? 'Message'),
                (string) ($config['body'] ?? ''),
                $config['link_url'] ?? null,
                $config['link_label'] ?? null,
            ),
            'update' => $this->updateRecord($context, $config),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $config
     */
    private function createTask(array $context, array $config): void
    {
        if (! Schema::hasTable('activities')) {
            return;
        }

        $userId = $context['user_id'] ?? null;

        if (! $userId && Schema::hasTable('users')) {
            $userId = DB::table('users')->orderBy('id')->value('id');
        }

        if (! $userId) {
            return;
        }

        $activityId = DB::table('activities')->insertGetId([
            'title' => $config['title'] ?? 'Task',
            'type' => 'task',
            'comment' => $config['body'] ?? null,
            'additional' => null,
            'schedule_from' => null,
            'schedule_to' => null,
            'is_done' => 0,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! empty($context['lead_id']) && Schema::hasTable('lead_activities')) {
            DB::table('lead_activities')->insert([
                'activity_id' => $activityId,
                'lead_id' => $context['lead_id'],
            ]);
        }

        if (! empty($context['person_id']) && Schema::hasTable('person_activities')) {
            DB::table('person_activities')->insert([
                'activity_id' => $activityId,
                'person_id' => $context['person_id'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $config
     */
    private function notify(array $context, array $config): void
    {
        $userId = (int) ($context['user_id'] ?? 0);

        if ($userId < 1 || ! Schema::hasTable('user_notifications') || ! class_exists(NotificationService::class)) {
            return;
        }

        app(NotificationService::class)->notify(
            $userId,
            'marketing_automation',
            (string) ($config['title'] ?? 'Workflow'),
            $config['body'] ?? null,
            null,
            [],
            $context['entity_type'] ?? null,
            isset($context['entity_id']) ? (int) $context['entity_id'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $config
     */
    private function updateRecord(array $context, array $config): void
    {
        $entityType = (string) ($context['entity_type'] ?? '');
        $field = (string) ($config['field'] ?? '');
        $allowed = Catalog::UPDATE_FIELDS[$entityType] ?? [];

        if (! in_array($field, $allowed, true) || ! isset($context['entity_id'])) {
            return;
        }

        $table = match ($entityType) {
            'leads' => 'leads',
            'persons' => 'persons',
            'activities' => 'activities',
            'quotes' => 'quotes',
            default => null,
        };

        if (! $table || ! Schema::hasTable($table)) {
            return;
        }

        $value = $config['value'] ?? null;

        if (in_array($field, ['lead_value', 'status', 'grand_total'], true)) {
            $value = is_numeric($value) ? $value + 0 : $value;
        }

        DB::table($table)->where('id', $context['entity_id'])->update([
            $field => $value,
            'updated_at' => now(),
        ]);
    }
}
