<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderClaim extends Model
{
    protected $fillable = [
        'claim_number',
        'order_id',
        'claim_type',
        'claim_status',
        'reason_code',
        'reason_detail',
        'pickup_postal_code',
        'pickup_address_line1',
        'pickup_address_line2',
        'shipping_fee_amount',
        'seller_response',
        'responded_at',
        'reject_reason',
        'handled_by_admin_id',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_fee_amount' => 'decimal:2',
            'responded_at' => 'datetime',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderClaimItem::class);
    }
}
