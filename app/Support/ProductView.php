<?php

namespace App\Support;

use App\Models\Product;

class ProductView
{
    public static function skuRegular(Product $product, mixed $sku): int
    {
        if ($sku->regular_price !== null) {
            return self::money($sku->regular_price);
        }

        return self::money($product->regular_price) + self::money($sku->additional_price);
    }

    public static function skuSale(Product $product, mixed $sku): int
    {
        if ($sku->sale_price !== null) {
            return self::money($sku->sale_price);
        }

        return self::money($product->sale_price) + self::money($sku->additional_price);
    }

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
        $priced = $product->relationLoaded('skus')
            ? $product->skus->filter(fn ($sku) => $sku->sale_price !== null)->sortBy(fn ($sku) => (float) $sku->sale_price)->first()
            : null;
        $regular = self::money($priced?->regular_price ?? $product->regular_price);
        $sale = self::money($priced?->sale_price ?? $product->sale_price);

        return [
            'id' => 'seller-'.$product->id,
            'name' => $product->product_name,
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
            'statusName' => StatusLabel::name('sale', $product->sale_status),
            'statusCode' => $product->sale_status,
            'images' => $images,
            'options' => $product->skus->map(function ($sku) use ($product) {
                $regular = self::skuRegular($product, $sku);
                $optionSale = self::skuSale($product, $sku);

                return [
                    'id' => $sku->id,
                    'name' => $sku->option_name,
                    'regularPrice' => $regular,
                    'salePrice' => $optionSale,
                    'price' => $optionSale,
                    'discount' => self::discount($regular, $optionSale),
                    'stock' => $sku->stock_quantity,
                    'soldOut' => $sku->status !== 'ON_SALE',
                ];
            })->values()->all(),
        ];
    }
}
