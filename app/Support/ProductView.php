<?php

namespace App\Support;

use App\Models\Product;

class ProductView
{
    public static function money(mixed $value): int
    {
        return (int) round((float) $value);
    }

    public static function discount(int $regular, int $sale): int
    {
        if ($regular <= 0 || $sale >= $regular) {
            return 0;
        }

        return (int) round((1 - $sale / $regular) * 100);
    }

    public static function card(Product $product): array
    {
        $image = $product->images->firstWhere('is_main', true) ?? $product->images->first();
        $regular = self::money($product->regular_price);
        $sale = self::money($product->sale_price);

        return [
            'id' => 'seller-'.$product->id,
            'name' => $product->product_name,
            'description' => $product->description,
            'image' => $image?->image_url,
            'price' => $sale,
            'originalPrice' => $regular,
            'discount' => self::discount($regular, $sale),
            'rating' => 0,
            'reviews' => 0,
            'badge' => '판매자',
            'href' => '/products/'.$product->id,
        ];
    }

    public static function detail(Product $product): array
    {
        $regular = self::money($product->regular_price);
        $sale = self::money($product->sale_price);
        $images = $product->images
            ->sortBy([['is_main', 'desc'], ['sort_order', 'asc']])
            ->values()
            ->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->image_url,
                'isMain' => (bool) $image->is_main,
            ])
            ->all();

        return [
            'id' => $product->id,
            'name' => $product->product_name,
            'description' => $product->description ?? '',
            'regularPrice' => $regular,
            'salePrice' => $sale,
            'discount' => self::discount($regular, $sale),
            'statusName' => $product->saleStatus?->status_name,
            'statusCode' => $product->saleStatus?->status_code,
            'images' => $images,
            'options' => $product->skus->map(fn ($sku) => [
                'id' => $sku->id,
                'name' => $sku->option_name,
                'additionalPrice' => self::money($sku->additional_price),
                'price' => $sale + self::money($sku->additional_price),
                'stock' => $sku->stock_quantity,
                'soldOut' => $sku->status?->status_code === 'SOLD_OUT',
            ])->values()->all(),
        ];
    }
}
