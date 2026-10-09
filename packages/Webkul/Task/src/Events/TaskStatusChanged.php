<?php

namespace Webkul\Task\Events;

use Illuminate\Queue\SerializesModels;
use Webkul\Task\Contracts\Task;

class TaskStatusChanged
{
    use SerializesModels;

    public function __construct(
        public Task $task,
        public string $oldStatus,
        public string $newStatus
    ) {}
}
