<?php

namespace Webkul\Task\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Task\Models\Task;

class TaskStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Task $task,
        public string $from,
        public string $to,
    ) {}
}
