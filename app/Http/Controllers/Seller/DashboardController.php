<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ProductView;
use App\Support\SellerContext;
use App\Support\SellerPage;

class DashboardController extends Controller
{
    public function index()
    {
        $store = SellerContext::store()->load('operationStatus');
        $products = Product::query()
            ->with(['saleStatus', 'skus.status', 'images'])
            ->where('store_id', $store->id)
            ->latest()
            ->get();

        $counts = [
            'total' => $products->count(),
            'onSale' => $products->where('saleStatus.status_code', 'ON_SALE')->count(),
            'stopped' => $products->where('saleStatus.status_code', 'STOPPED')->count(),
            'draft' => $products->where('saleStatus.status_code', 'DRAFT')->count(),
            'lowStock' => $products->flatMap->skus->filter(
                fn ($sku) => $sku->stock_quantity <= 5 && $sku->status?->status_code !== 'SOLD_OUT'
            )->count(),
            'soldOut' => $products->flatMap->skus->filter(
                fn ($sku) => $sku->status?->status_code === 'SOLD_OUT'
            )->count(),
        ];

        return SellerPage::render('SellerDashboard', [
            'section' => 'home',
            'storeOpen' => $store->operationStatus?->status_code === 'OPEN',
            'counts' => $counts,
            'recent' => $products->take(5)->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->product_name,
                'price' => ProductView::money($product->sale_price),
                'statusName' => $product->saleStatus?->status_name,
                'stock' => $product->skus->sum('stock_quantity'),
            ])->values()->all(),
        ]);
    }
}
