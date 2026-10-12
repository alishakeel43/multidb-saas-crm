<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTelephonyRoute extends Model
{
    protected $connection = 'landlord';

    protected $table = 'tenant_telephony_routes';

    protected $fillable = [
        'tenant_id',
        'twilio_phone_number',
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
