<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'seller_id',
        'store_name',
        'logo_url',
        'banner_image_url',
        'introduction',
        'operation_status_id',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function operationStatus(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'operation_status_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }
}
