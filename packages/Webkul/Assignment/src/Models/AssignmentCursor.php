<?php

namespace Webkul\Assignment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Assignment\Contracts\AssignmentCursor as AssignmentCursorContract;

class AssignmentCursor extends Model implements AssignmentCursorContract
{
    protected $fillable = [
        'assignment_rule_id',
        'last_user_id',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AssignmentRuleProxy::modelClass(), 'assignment_rule_id');
    }
}
