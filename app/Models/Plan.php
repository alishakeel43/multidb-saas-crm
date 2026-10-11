<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Plan extends Model
{
    use UsesLandlordConnection;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'monthly_price',
        'currency',
        'is_active',
        'sort_order',
        'storage_quota_mb',
        'stripe_price_id',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'storage_quota_mb' => 'integer',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
