<?php

namespace Webkul\UnifiedInbox\Models;

use Illuminate\Database\Eloquent\Model;

class QuickReply extends Model
{
    protected $table = 'unified_inbox_quick_replies';

    protected $fillable = ['title', 'shortcut', 'body', 'user_id', 'sort_order'];
}
