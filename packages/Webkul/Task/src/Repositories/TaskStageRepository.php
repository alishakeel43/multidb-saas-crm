<?php

namespace Webkul\Task\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\Task\Contracts\TaskStage;
use Webkul\Task\Models\Task;

class TaskStageRepository extends Repository
{
    public function model(): string
    {
        return TaskStage::class;
    }

    /**
     * Seed the pipeline once per tenant database. Later visits keep the tenant's own columns.
     *
     * @return Collection<int, \Webkul\Task\Models\TaskStage>
     */
    public function ensureDefaults(): Collection
    {
        if ($this->model->newQuery()->exists()) {
            return $this->ordered();
        }

        $defaults = [
            [Task::STATUS_PENDING, 'Pending', '#D97706', 1, true],
            [Task::STATUS_IN_PROGRESS, 'In progress', '#2563EB', 2, false],
            [Task::STATUS_COMPLETED, 'Completed', '#059669', 3, false],
            [Task::STATUS_CANCELLED, 'Cancelled', '#6B7280', 4, false],
        ];

        foreach ($defaults as [$code, $name, $color, $sort, $isDefault]) {
            $this->create([
                'code' => $code,
                'name' => $name,
                'color' => $color,
                'sort_order' => $sort,
                'is_default' => $isDefault,
            ]);
        }

        return $this->ordered();
    }

    /**
     * @return Collection<int, \Webkul\Task\Models\TaskStage>
     */
    public function ordered(): Collection
    {
        return $this->model->newQuery()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function codeFromName(string $name): string
    {
        $base = Str::slug($name, '_');
        $base = $base !== '' ? $base : 'stage';
        $code = $base;
        $suffix = 2;

        while ($this->model->newQuery()->where('code', $code)->exists()) {
            $code = $base.'_'.$suffix;
            $suffix++;
        }

        return $code;
    }
}
