<?php

namespace Webkul\Assignment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Assignment\Contracts\AssignmentRule as AssignmentRuleContract;
use Webkul\User\Models\UserProxy;

class AssignmentRule extends Model implements AssignmentRuleContract
{
    protected $fillable = [
        'name',
        'entity_type',
        'strategy',
        'is_active',
        'only_when_empty',
        'priority',
        'conditions',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'only_when_empty' => 'boolean',
            'conditions' => 'array',
            'priority' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            UserProxy::modelClass(),
            'assignment_rule_users',
            'assignment_rule_id',
            'user_id',
        )->withPivot(['sort_order', 'is_active'])->withTimestamps();
    }

    public function cursor(): HasOne
    {
        return $this->hasOne(AssignmentCursorProxy::modelClass());
    }
}
