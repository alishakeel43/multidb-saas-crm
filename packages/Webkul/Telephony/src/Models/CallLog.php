<?php

namespace Webkul\Telephony\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\LeadProxy;
use Webkul\Telephony\Contracts\CallLog as CallLogContract;
use Webkul\User\Models\UserProxy;

class CallLog extends Model implements CallLogContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'call_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'provider_call_id',
        'direction',
        'status',
        'from_number',
        'to_number',
        'contact_id',
        'lead_id',
        'user_id',
        'duration_seconds',
        'recording_url',
        'recording_sid',
        'started_at',
        'ended_at',
        'provider_payload',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'provider_payload' => 'array',
    ];

    /**
     * Get the contact (person) associated with this call log.
     */
    public function contact()
    {
        return $this->belongsTo(PersonProxy::modelClass(), 'contact_id');
    }

    /**
     * Get the lead associated with this call log.
     */
    public function lead()
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    /**
     * Get the agent (user) who handled this call.
     */
    public function user()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'user_id');
    }
}
