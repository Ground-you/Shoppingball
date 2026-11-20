<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\ProductSku;
use App\Support\OrderService;
use App\Support\ProductView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function show(Request $request)
    {
        /** @var Buyer $buyer */
        $buyer = Auth::guard('buyer')->user();
        $sku = ProductSku::query()->with(['product.store', 'product.images'])->find($request->integer('sku'));
        abort_unless($sku && $sku->product && $sku->product->store, 404);

        $product = $sku->product;
        $policy = $product->store->policies()->orderByDesc('version_no')->first();
        $unit = ProductView::money($product->sale_price) + ProductView::money($sku->additional_price);

        return view('app', [
            'page' => [
                'component' => 'Checkout',
                'shortcuts' => ['오늘도착', '신선마켓', '골드딜', '기획전', '해외직구', '브랜드특가'],
                'buyer' => [
                    'loginId' => $buyer->login_id,
                    'name' => $buyer->buyer_name,
                    'nickname' => $buyer->nickname,
                ],
                'payment' => [
                    'driver' => $this->orders->driver(),
                    'tossClientKey' => config('payments.driver') === 'toss' ? config('payments.toss.client_key') : null,
                ],
                'sku' => [
                    'id' => $sku->id,
                    'name' => $product->product_name,
                    'option' => $sku->option_name,
                    'unitPrice' => $unit,
                    'stock' => $sku->stock_quantity,
                    'soldOut' => $sku->status === 'SOLD_OUT',
                    'image' => ($product->images->firstWhere('is_main', true) ?? $product->images->first())?->image_url,
                    'shippingFee' => ProductView::money($policy?->default_shipping_fee ?? 0),
                    'freeThreshold' => $policy?->free_shipping_threshold !== null ? ProductView::money($policy->free_shipping_threshold) : null,
                ],
                'quantity' => max(1, $request->integer('quantity', 1)),
                'addresses' => BuyerAddress::query()
                    ->where('buyer_id', $buyer->id)
                    ->orderByDesc('is_default')
                    ->latest()
                    ->get(['id', 'address_name', 'recipient_name', 'recipient_phone', 'postal_code', 'address_line1', 'address_line2', 'is_default']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        /** @var Buyer $buyer */
        $buyer = Auth::guard('buyer')->user();

        $data = $request->validate([
            'sku_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'address_id' => ['required', 'integer'],
            'delivery_request' => ['nullable', 'string', 'max:255'],
            'payment_key' => ['nullable', 'string', 'max:200'],
        ], [
            'quantity.required' => '수량을 입력해 주세요.',
            'address_id.required' => '등록된 배송지를 선택해 주세요.',
        ]);

        $address = $this->orders->addressFromBuyer($buyer, (int) $data['address_id']);

        if (! empty($data['delivery_request'])) {
            $address['delivery_request'] = $data['delivery_request'];
        }

        $order = $this->orders->checkout(
            $buyer,
            (int) $data['sku_id'],
            (int) $data['quantity'],
            $address,
            $data['payment_key'] ?? null,
        );

        return response()->json(['redirect' => route('mypage'), 'orderNumber' => $order->order_number]);
    }
}
