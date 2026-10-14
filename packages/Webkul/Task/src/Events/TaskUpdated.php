<?php

namespace Webkul\Task\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Task\Models\Task;

class TaskUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Task $task) {}
}
