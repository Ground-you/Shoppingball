<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSku extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku_code',
        'option_name',
        'additional_price',
        'stock_quantity',
        'status_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'additional_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }
}
