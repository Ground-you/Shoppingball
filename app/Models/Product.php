<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'price',
        'original_price',
        'rating',
        'review_count',
        'badge',
        'is_recommended',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'original_price' => 'integer',
            'rating' => 'decimal:1',
            'review_count' => 'integer',
            'is_recommended' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
