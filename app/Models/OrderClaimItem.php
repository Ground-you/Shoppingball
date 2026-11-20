<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderClaimItem extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_claim_id',
        'order_item_id',
        'quantity',
        'exchange_product_sku_id',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(OrderClaim::class, 'order_claim_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function exchangeSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'exchange_product_sku_id');
    }
}
