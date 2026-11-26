<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'buyer_id',
        'order_status',
        'recipient_name',
        'recipient_phone',
        'shipping_postal_code',
        'shipping_address_line1',
        'shipping_address_line2',
        'delivery_request',
        'total_product_amount',
        'shipping_fee',
        'final_payment_amount',
        'ordered_at',
        'canceled_at',
        'buyer_hidden_at',
    ];

    protected function casts(): array
    {
        return [
            'total_product_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'final_payment_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
            'canceled_at' => 'datetime',
            'buyer_hidden_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(OrderClaim::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
