<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Policy extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'store_id',
        'version_no',
        'default_shipping_fee',
        'free_shipping_threshold',
        'return_shipping_fee',
        'exchange_shipping_fee',
        'return_period_days',
        'exchange_period_days',
        'cancel_policy_description',
        'shipping_policy_notice',
        'exchange_return_policy_notice',
        'effective_from',
        'registered_by_seller_member_id',
        'registered_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
