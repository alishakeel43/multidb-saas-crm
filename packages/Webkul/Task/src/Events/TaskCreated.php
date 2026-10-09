<?php

namespace Webkul\Task\Events;

use Illuminate\Queue\SerializesModels;
use Webkul\Task\Contracts\Task;

class TaskCreated
{
    use SerializesModels;

    public function __construct(public Task $task) {}
}
