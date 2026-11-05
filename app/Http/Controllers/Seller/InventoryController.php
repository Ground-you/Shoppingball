<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductSku;
use App\Support\CatalogStatus;
use App\Support\ProductView;
use App\Support\SellerContext;
use App\Support\SellerPage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index()
    {
        $store = SellerContext::store();
        $rows = ProductSku::query()
            ->with(['product', 'status'])
            ->whereHas('product', fn ($query) => $query->where('store_id', $store->id))
            ->get()
            ->sortBy(fn (ProductSku $sku) => sprintf('%s-%04d', $sku->product?->product_name, $sku->sort_order))
            ->map(fn (ProductSku $sku) => $this->present($sku))
            ->values()
            ->all();

        return SellerPage::render('SellerInventory', [
            'section' => 'inventory',
            'rows' => $rows,
        ]);
    }

    public function update(Request $request, ProductSku $sku)
    {
        $store = SellerContext::store();
        $sku->load('product', 'status');
        abort_unless($sku->product && $sku->product->store_id === $store->id, 404);

        $data = $request->validate([
            'action' => ['required', Rule::in(['set', 'add', 'subtract', 'sold_out', 'restock'])],
            'quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ], [
            'quantity.min' => '재고는 0 이상이어야 합니다.',
        ]);

        $quantity = (int) ($data['quantity'] ?? 1);

        if ($data['action'] === 'set') {
            if (! $request->exists('quantity')) {
                return response()->json(['message' => '재고 수량을 입력해 주세요.'], 422);
            }
            $sku->stock_quantity = $quantity;
        } elseif ($data['action'] === 'add') {
            $sku->stock_quantity += max($quantity, 1);
        } elseif ($data['action'] === 'subtract') {
            $sku->stock_quantity = max(0, $sku->stock_quantity - max($quantity, 1));
        } elseif ($data['action'] === 'sold_out') {
            $sku->status_id = CatalogStatus::id('SKU', 'SOLD_OUT', '품절', 2);
        } elseif ($data['action'] === 'restock') {
            $sku->status_id = CatalogStatus::id('SKU', 'ACTIVE', '판매중', 1);
        }

        $sku->save();
        $sku->load('status');

        return response()->json(['sku' => $this->present($sku)]);
    }

    private function present(ProductSku $sku): array
    {
        $soldOut = $sku->status?->status_code === 'SOLD_OUT';

        return [
            'id' => $sku->id,
            'productId' => $sku->product_id,
            'productName' => $sku->product?->product_name,
            'optionName' => $sku->option_name,
            'skuCode' => $sku->sku_code,
            'stock' => $sku->stock_quantity,
            'soldOut' => $soldOut,
            'additionalPrice' => ProductView::money($sku->additional_price),
            'low' => $sku->stock_quantity <= 5 && ! $soldOut,
        ];
    }
}
