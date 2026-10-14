<?php

namespace Webkul\Task\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Task\Models\Task;

class TaskEntityLookup
{
    /**
     * @return Collection<int, array{id: int, title: string}>
     */
    public function search(string $type, string $term): Collection
    {
        if (! isset(Task::ENTITIES[$type])) {
            return collect();
        }

        $column = match ($type) {
            'leads' => 'title',
            'quotes' => 'subject',
            default => 'name',
        };

        $table = match ($type) {
            'leads' => 'leads',
            'persons' => 'persons',
            'organizations' => 'organizations',
            'quotes' => 'quotes',
        };

        if (! Schema::hasTable($table)) {
            return collect();
        }

        $query = DB::table($table)->select('id', $column.' as title')->orderBy('id', 'desc')->limit(10);

        if ($term !== '') {
            $query->where($column, 'like', '%'.$term.'%');
        }

        $userIds = function_exists('bouncer') ? bouncer()->getAuthorizedUserIds() : null;

        if (is_array($userIds) && Schema::hasColumn($table, 'user_id')) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'title' => (string) $row->title,
        ]);
    }
}
