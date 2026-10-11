<?php

namespace Webkul\Consent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Consent\Contracts\PersonConsent as PersonConsentContract;
use Webkul\Contact\Models\PersonProxy;

class PersonConsent extends Model implements PersonConsentContract
{
    protected $fillable = [
        'person_id',
        'channel',
        'is_opted_in',
        'source',
        'changed_by',
        'captured_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_opted_in' => 'boolean',
            'captured_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass());
    }
}
