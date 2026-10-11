<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantChannelRoute extends Model
{
    protected $connection = 'landlord';

    protected $table = 'tenant_channel_routes';

    protected $fillable = [
        'tenant_id',
        'channel',
        'external_id',
        'waba_id',
        'account_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
