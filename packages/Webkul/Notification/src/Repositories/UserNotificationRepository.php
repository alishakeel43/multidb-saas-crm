<?php

namespace Webkul\Notification\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Notification\Contracts\UserNotification;

class UserNotificationRepository extends Repository
{
    public function model(): string
    {
        return UserNotification::class;
    }
}
