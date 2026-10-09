<?php

namespace Webkul\Activity\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityReminder extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'remind_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
