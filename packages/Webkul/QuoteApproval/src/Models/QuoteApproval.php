<?php

namespace Webkul\QuoteApproval\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Quote\Models\QuoteProxy;
use Webkul\QuoteApproval\Contracts\QuoteApproval as QuoteApprovalContract;
use Webkul\User\Models\UserProxy;

class QuoteApproval extends Model implements QuoteApprovalContract
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const DELIVERY_NONE = 'none';

    public const DELIVERY_SENT = 'sent';

    public const DELIVERY_CONVERTED = 'converted';

    protected $fillable = [
        'quote_id',
        'status',
        'requested_by',
        'reviewed_by',
        'requested_at',
        'reviewed_at',
        'notes',
        'delivery_status',
        'order_reference',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(QuoteProxy::modelClass());
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'reviewed_by');
    }
}
