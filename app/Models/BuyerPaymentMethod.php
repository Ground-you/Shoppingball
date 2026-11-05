<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerPaymentMethod extends Model
{
    protected $fillable = [
        'buyer_id',
        'method_type',
        'alias',
        'provider_name',
        'number_last4',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
