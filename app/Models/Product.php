<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'category_id',
        'brand_id',
        'product_name',
        'description',
        'regular_price',
        'sale_price',
        'sale_status_id',
        'approval_status_id',
        'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'regular_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function saleStatus(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'sale_status_id');
    }

    public function approvalStatus(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'approval_status_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class)->orderBy('sort_order');
    }
}
