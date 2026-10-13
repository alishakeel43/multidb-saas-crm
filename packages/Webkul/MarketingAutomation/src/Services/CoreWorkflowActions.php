<?php

namespace Webkul\MarketingAutomation\Services;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\Activity\Models\Activity;
use Webkul\Automation\Helpers\Validator;
use Webkul\Automation\Repositories\WorkflowRepository;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\MarketingAutomation\Models\CoreWorkflowRun;
use Webkul\MarketingAutomation\Support\CrmEntity;
use Webkul\Quote\Models\Quote;

class CoreWorkflowActions
{
    private static int $depth = 0;

    /**
     * @return list<array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            [
                'id' => 'ma_delay',
                'name' => 'Delay',
                'attributes' => [
                    ['id' => 'minutes', 'name' => 'Minutes', 'type' => 'integer'],
                ],
            ],
            [
                'id' => 'ma_task',
                'name' => 'Create task',
                'attributes' => [
                    ['id' => 'title', 'name' => 'Task title', 'type' => 'text'],
                ],
            ],
            [
                'id' => 'ma_notification',
                'name' => 'Notify owner',
                'attributes' => [
                    ['id' => 'title', 'name' => 'Notification', 'type' => 'text'],
                ],
            ],
            [
                'id' => 'ma_otherwise',
                'name' => 'Otherwise',
            ],
        ];
    }

    public function handleOtherwise(string $event, mixed $entity): void
    {
        if (self::$depth > 0 || ! is_object($entity) || ! Schema::hasTable('workflows')) {
            return;
        }

        if (! class_exists(WorkflowRepository::class) || ! class_exists(Validator::class)) {
            return;
        }

        self::$depth++;

        try {
            $validator = app(Validator::class);

            foreach (app(WorkflowRepository::class)->findByField('event', $event) as $workflow) {
                $class = config('workflows.trigger_entities.'.$workflow->entity_type.'.class');

                if (! is_string($class) || ! class_exists($class)) {
                    continue;
                }

                $helper = app($class);
                $resolved = $helper->getEntity($entity);

                if (! $resolved || ! method_exists($helper, 'executeOtherwise')) {
                    continue;
                }

                if ($validator->validate($workflow, $resolved)) {
                    continue;
                }

                $helper->executeOtherwise($workflow, $resolved);
            }
        } catch (Throwable $exception) {
            Log::warning('marketing_automation.core_workflow_else_failed', [
                'event' => $event,
                'error' => $exception->getMessage(),
            ]);
        } finally {
            self::$depth--;
        }
    }

    public function runMatch(object $workflow, object $entity, Closure $core): void
    {
        $this->run($workflow, $entity, $core, false);
    }

    public function runElse(object $workflow, object $entity, Closure $core): void
    {
        $this->run($workflow, $entity, $core, true);
    }

    public function processDue(): int
    {
        if (! Schema::hasTable('ma_core_workflow_runs')) {
            return 0;
        }

        $processed = 0;

        $runs = CoreWorkflowRun::query()
            ->where('status', 'pending')
            ->where('run_at', '<=', now())
            ->orderBy('id')
            ->limit(50)
            ->get();

        foreach ($runs as $run) {
            $run->forceFill(['status' => 'processing'])->save();
            $entity = $this->reload((string) $run->entity_type, (int) $run->entity_id);

            if (! $entity) {
                $run->forceFill(['status' => 'cancelled', 'finished_at' => now()])->save();

                continue;
            }

            try {
                $workflow = (object) [
                    'id' => $run->workflow_id,
                    'actions' => $run->actions ?? [],
                ];

                $this->walk($workflow, $entity, $run->actions ?? [], function (array $action) use ($run, $entity): void {
                    $this->executeStoredCoreAction($run->entity_type, $action, $entity);
                });

                if ($run->fresh()?->status === 'processing') {
                    $run->forceFill(['status' => 'done', 'finished_at' => now(), 'last_error' => null])->save();
                }

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

    private function run(object $workflow, object $entity, Closure $core, bool $otherwise): void
    {
        $actions = array_values(is_array($workflow->actions ?? null) ? $workflow->actions : []);
        $marker = null;

        foreach ($actions as $index => $action) {
            if (($action['id'] ?? '') === 'ma_otherwise') {
                $marker = $index;
                break;
            }
        }

        if ($otherwise) {
            if ($marker === null) {
                return;
            }

            $slice = array_slice($actions, $marker + 1);
        } else {
            $slice = $marker === null ? $actions : array_slice($actions, 0, $marker);
        }

        $this->walk($workflow, $entity, $slice, $core);
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     */
    public function walk(object $workflow, object $entity, array $actions, Closure $core): void
    {
        self::$depth++;

        try {
            $this->walkActions($workflow, $entity, $actions, $core);
        } finally {
            self::$depth--;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     */
    private function walkActions(object $workflow, object $entity, array $actions, Closure $core): void
    {
        foreach (array_values($actions) as $index => $action) {
            $id = (string) ($action['id'] ?? '');

            if ($id === 'ma_delay') {
                $minutes = max(1, (int) ($action['value'] ?? 1));
                $this->schedule($workflow, $entity, array_values(array_slice($actions, $index + 1)), $minutes);

                return;
            }

            if ($id === 'ma_task' || $id === 'ma_notification') {
                $context = CrmEntity::context($entity);

                if ($context) {
                    app(WorkflowEngine::class)->performConfigured(
                        $id === 'ma_task' ? 'task' : 'notification',
                        ['title' => (string) ($action['value'] ?? 'Workflow')],
                        $context,
                    );
                }

                continue;
            }

            if (str_starts_with($id, 'ma_')) {
                continue;
            }

            $core($action);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $remaining
     */
    private function schedule(object $workflow, object $entity, array $remaining, int $minutes): void
    {
        if ($remaining === [] || ! Schema::hasTable('ma_core_workflow_runs')) {
            return;
        }

        $context = CrmEntity::context($entity);

        if ($context === null) {
            return;
        }

        CoreWorkflowRun::query()->create([
            'workflow_id' => (int) ($workflow->id ?? 0),
            'entity_type' => $context['entity_type'],
            'entity_id' => $context['entity_id'],
            'actions' => $remaining,
            'status' => 'pending',
            'run_at' => now()->addMinutes($minutes),
        ]);
    }

    private function reload(string $type, int $id): ?object
    {
        $class = config('workflows.trigger_entities.'.$type.'.class');

        if (is_string($class) && class_exists($class)) {
            try {
                $entity = app($class)->getEntity($id);

                if ($entity) {
                    return $entity;
                }
            } catch (Throwable) {
                // Fall back to the CRM model when the workflow helper cannot boot.
            }
        }

        $model = match ($type) {
            'leads' => Lead::class,
            'persons' => Person::class,
            'activities' => Activity::class,
            'quotes' => Quote::class,
            default => null,
        };

        if (! $model) {
            return null;
        }

        return $model::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function executeStoredCoreAction(string $type, array $action, object $entity): void
    {
        $class = config('workflows.trigger_entities.'.$type.'.class');

        if (! is_string($class) || ! class_exists($class)) {
            return;
        }

        $helper = app($class);

        if (method_exists($helper, 'executeCoreAction')) {
            $helper->executeCoreAction($action, $entity);
        }
    }
}
