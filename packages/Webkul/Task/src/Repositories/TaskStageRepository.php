<?php

namespace Webkul\Task\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Task\Contracts\TaskStage as TaskStageContract;
use Webkul\Task\Models\TaskStage;

class TaskStageRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return TaskStageContract::class;
    }

    /**
     * Get stages for the current tenant database, provisioning defaults if needed.
     */
    public function getStagesForCurrentTenant(): Collection
    {
        return $this->ensureDefaultStages();
    }

    /**
     * Provision the standard workflow stages if not already present.
     *
     * Defaults are back-filled per missing code, not only when the workspace has no
     * stages at all, so creating a custom list can never hide the standard columns.
     */
    public function ensureDefaultStages(): Collection
    {
        $existingCodes = $this->query()->pluck('code')->all();

        $missing = array_filter(
            TaskStage::DEFAULT_STAGES,
            fn ($stageData) => ! in_array($stageData['code'], $existingCodes, true)
        );

        foreach ($missing as $stageData) {
            $this->create([
                'code' => $stageData['code'],
                'name' => trans('task::app.status.'.$stageData['code'], [], null) ?: $stageData['name'],
                'color' => $stageData['color'],
                'sort_order' => $stageData['sort_order'],
                'is_default' => $stageData['is_default'],
            ]);
        }

        if ($missing) {
            $this->resyncSortOrder();
        }

        return $this->orderedQuery()->get();
    }

    /**
     * Next free sort order for a new stage.
     */
    public function getNextSortOrder(): int
    {
        return (int) $this->query()->max('sort_order') + 1;
    }

    /**
     * Re-sequence so the standard columns come first and no two stages share a sort order.
     */
    public function resyncSortOrder(): void
    {
        $defaultCodes = array_column(TaskStage::DEFAULT_STAGES, 'code');

        $stages = $this->orderedQuery()->get();

        $ordered = $stages
            ->filter(fn ($stage) => in_array($stage->code, $defaultCodes, true))
            ->sortBy(fn ($stage) => array_search($stage->code, $defaultCodes))
            ->concat($stages->reject(fn ($stage) => in_array($stage->code, $defaultCodes, true)))
            ->values();

        foreach ($ordered as $index => $stage) {
            $sortOrder = $index + 1;

            if ((int) $stage->sort_order === $sortOrder) {
                continue;
            }

            $stage->update(['sort_order' => $sortOrder]);
        }
    }

    /**
     * Stage query for the current tenant database.
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Stage query in board order.
     */
    protected function orderedQuery(): Builder
    {
        return $this->query()->orderBy('sort_order')->orderBy('id');
    }
}
