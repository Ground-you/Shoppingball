<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuyerAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'buyer_id',
        'address_name',
        'recipient_name',
        'recipient_phone',
        'postal_code',
        'address_line1',
        'address_line2',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
