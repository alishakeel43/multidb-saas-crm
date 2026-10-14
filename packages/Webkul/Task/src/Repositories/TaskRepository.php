<?php

namespace Webkul\Task\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Task\Contracts\Task;

class TaskRepository extends Repository
{
    public function model(): string
    {
        return Task::class;
    }
}
