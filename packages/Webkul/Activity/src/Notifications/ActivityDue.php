<?php

namespace Webkul\Activity\Notifications;

use Illuminate\Notifications\Notification;
use Webkul\Activity\Models\Activity;
use Webkul\Admin\Notifications\Common;

class ActivityDue extends Notification
{
    public function __construct(public Activity $activity, public string $channel, string $key)
    {
        $this->id = $key;
    }

    public function via($notifiable): array
    {
        return [$this->channel === 'in_app' ? 'database' : 'mail'];
    }

    public function toArray($notifiable): array
    {
        return [
            'activity_id' => $this->activity->id,
            'title' => $this->activity->title,
            'due_at' => $this->activity->schedule_from->toIso8601String(),
            'url' => route('admin.activities.index', ['id[eq]' => $this->activity->id]),
        ];
    }

    public function toMail($notifiable): Common
    {
        $data = $this->toArray($notifiable);

        return new Common([
            'to' => $notifiable->email,
            'subject' => trans('admin::app.reminders.subject'),
            'body' => '<p>'.e($data['title']).'</p><p>'.e($data['due_at']).'</p><a href="'.e($data['url']).'">'.e(trans('admin::app.reminders.open')).'</a>',
        ]);
    }
}
