<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\CartItem;
use App\Models\ProductSku;
use App\Support\ProductView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    private const MAX_QUANTITY = 99;

    public function show()
    {
        /** @var Buyer $buyer */
        $buyer = Auth::guard('buyer')->user();

        $items = CartItem::query()
            ->with(['sku.product.store', 'sku.product.images'])
            ->where('buyer_id', $buyer->id)
            ->orderByDesc('id')
            ->get();

        $groups = [];

        foreach ($items as $item) {
            $sku = $item->sku;
            $product = $sku?->product;
            $store = $product?->store;

            if (! $sku || ! $product || ! $store) {
                // 삭제된 상품은 장바구니에서 조용히 치운다.
                $item->delete();

                continue;
            }

            $key = $store->id;

            if (! isset($groups[$key])) {
                $policy = $store->policies()->orderByDesc('version_no')->first();
                $groups[$key] = [
                    'storeId' => $store->id,
                    'storeName' => $store->store_name,
                    'shippingFee' => ProductView::money($policy?->default_shipping_fee ?? 0),
                    'freeThreshold' => $policy?->free_shipping_threshold !== null ? ProductView::money($policy->free_shipping_threshold) : null,
                    'items' => [],
                ];
            }

            $available = $this->purchasable($sku);

            $groups[$key]['items'][] = [
                'id' => $item->id,
                'skuId' => $sku->id,
                'productId' => $product->id,
                'name' => $product->product_name,
                'option' => $sku->option_name,
                'image' => ($product->images->firstWhere('is_main', true) ?? $product->images->first())?->image_url,
                'unitPrice' => ProductView::skuSale($product, $sku),
                'quantity' => $item->quantity,
                'stock' => $sku->stock_quantity,
                'available' => $available,
                'unavailableReason' => $available ? null : $this->unavailableReason($sku),
            ];
        }

        return view('app', [
            'page' => [
                'component' => 'Cart',
                'shortcuts' => ['오늘도착', '신선마켓', '골드딜', '기획전', '해외직구', '브랜드특가'],
                'buyer' => [
                    'loginId' => $buyer->login_id,
                    'name' => $buyer->buyer_name,
                    'nickname' => $buyer->nickname,
                ],
                'groups' => array_values($groups),
            ],
        ]);
    }

    public function count()
    {
        $buyer = Auth::guard('buyer')->user();

        return response()->json(['count' => CartItem::query()->where('buyer_id', $buyer->id)->count()]);
    }

    public function store(Request $request)
    {
        /** @var Buyer $buyer */
        $buyer = Auth::guard('buyer')->user();

        $data = $request->validate([
            'sku_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY],
        ], [
            'quantity.min' => '수량은 1개 이상이어야 합니다.',
            'quantity.max' => '한 번에 담을 수 있는 수량을 넘었습니다.',
        ]);

        $sku = ProductSku::query()->with('product.store.seller')->find($data['sku_id']);

        if (! $sku || ! $this->purchasable($sku)) {
            throw ValidationException::withMessages(['sku_id' => '지금 담을 수 없는 상품입니다.']);
        }

        $item = CartItem::query()->firstOrNew(['buyer_id' => $buyer->id, 'product_sku_id' => $sku->id]);
        $quantity = ($item->exists ? $item->quantity : 0) + (int) $data['quantity'];

        if ($quantity > $sku->stock_quantity) {
            throw ValidationException::withMessages(['quantity' => "재고가 부족합니다. (재고 {$sku->stock_quantity}개, 장바구니 ".($item->exists ? $item->quantity : 0).'개)']);
        }

        $item->quantity = min($quantity, self::MAX_QUANTITY);
        $item->save();

        return response()->json([
            'message' => '장바구니에 담았습니다.',
            'count' => CartItem::query()->where('buyer_id', $buyer->id)->count(),
        ]);
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $buyer = Auth::guard('buyer')->user();
        abort_unless($cartItem->buyer_id === $buyer->id, 404);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY],
        ], [
            'quantity.min' => '수량은 1개 이상이어야 합니다.',
        ]);

        $sku = $cartItem->sku;

        if ($sku && $data['quantity'] > $sku->stock_quantity) {
            throw ValidationException::withMessages(['quantity' => "재고가 부족합니다. (재고 {$sku->stock_quantity}개)"]);
        }

        $cartItem->update(['quantity' => (int) $data['quantity']]);

        return response()->json(['quantity' => $cartItem->quantity]);
    }

    public function destroy(CartItem $cartItem)
    {
        $buyer = Auth::guard('buyer')->user();
        abort_unless($cartItem->buyer_id === $buyer->id, 404);

        $cartItem->delete();

        return response()->json(['ok' => true]);
    }

    private function purchasable(ProductSku $sku): bool
    {
        $sku->loadMissing('product.store.seller');
        $product = $sku->product;
        $store = $product?->store;

        return $product
            && $store
            && $store->seller
            && $product->sale_status === 'ON_SALE'
            && $product->approval_status === 'APPROVED'
            && $store->operation_status === 'OPEN'
            && $sku->status === 'ON_SALE'
            && $sku->stock_quantity > 0;
    }

    private function unavailableReason(ProductSku $sku): string
    {
        if ($sku->status === 'SOLD_OUT' || $sku->stock_quantity < 1) {
            return '품절';
        }

        return '판매 중단';
    }
}
