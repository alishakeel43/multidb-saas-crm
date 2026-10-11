<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Invoice extends Model
{
    use UsesLandlordConnection;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'number',
        'status',
        'currency',
        'subtotal',
        'tax',
        'total',
        'due_at',
        'paid_at',
        'stripe_invoice_id',
        'stripe_checkout_session_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['draft', 'open'], true);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
