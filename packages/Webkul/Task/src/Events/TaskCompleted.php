<?php

namespace Webkul\Task\Events;

use Illuminate\Queue\SerializesModels;
use Webkul\Task\Contracts\Task;

class TaskCompleted
{
    use SerializesModels;

    public function __construct(public Task $task) {}
}
