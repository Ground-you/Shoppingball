<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ProductView;
use App\Support\StatusLabel;
use App\Support\SellerContext;
use App\Support\SellerPage;

class DashboardController extends Controller
{
    public function index()
    {
        $store = SellerContext::store();
        $products = Product::query()
            ->with(['skus', 'images'])
            ->where('store_id', $store->id)
            ->latest()
            ->get();

        $counts = [
            'total' => $products->count(),
            'onSale' => $products->where('sale_status', 'ON_SALE')->count(),
            'stopped' => $products->where('sale_status', 'STOPPED')->count(),
            'soldOutProducts' => $products->where('sale_status', 'SOLD_OUT')->count(),
            'lowStock' => $products->flatMap->skus->filter(
                fn ($sku) => $sku->stock_quantity <= 5 && $sku->status !== 'SOLD_OUT'
            )->count(),
            'soldOut' => $products->flatMap->skus->filter(
                fn ($sku) => $sku->status === 'SOLD_OUT'
            )->count(),
        ];

        return SellerPage::render('SellerDashboard', [
            'section' => 'home',
            'storeOpen' => $store->operation_status === 'OPEN',
            'counts' => $counts,
            'recent' => $products->take(5)->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->product_name,
                'price' => ProductView::money($product->sale_price),
                'statusName' => StatusLabel::name('sale', $product->sale_status),
                'stock' => $product->skus->sum('stock_quantity'),
            ])->values()->all(),
        ]);
    }
}
