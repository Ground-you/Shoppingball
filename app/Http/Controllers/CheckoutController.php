<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\ProductSku;
use App\Support\OrderService;
use App\Support\ProductView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
        $unit = ProductView::skuSale($product, $sku);

        return view('app', [
            'page' => [
                'component' => 'Checkout',
                'shortcuts' => ['오늘도착', '신선마켓', '골드딜', '기획전', '해외직구', '브랜드특가'],
                'buyer' => [
                    'loginId' => $buyer->login_id,
                    'name' => $buyer->buyer_name,
                    'nickname' => $buyer->nickname,
                    'email' => $buyer->email,
                ],
                'paymentError' => mb_substr((string) $request->query('payment_error', ''), 0, 200),
                'payment' => [
                    'driver' => $this->orders->driver(),
                    'tossClientKey' => config('payments.driver') === 'toss' ? config('payments.toss.client_key') : null,
                    'customerKey' => 'buyer.'.substr(hash_hmac('sha256', (string) $buyer->id, (string) config('app.key')), 0, 40),
                ],
                'sku' => [
                    'id' => $sku->id,
                    'name' => $product->product_name,
                    'option' => $sku->option_name,
                    'unitPrice' => $unit,
                    'stock' => $sku->stock_quantity,
                    'soldOut' => $sku->status !== 'ON_SALE',
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

        if ($this->orders->driver() === 'toss') {
            $order = $this->orders->checkout(
                $buyer,
                (int) $data['sku_id'],
                (int) $data['quantity'],
                $address,
                null,
                true,
            );
            $order->load('items');
            $request->session()->put('checkout_return', [
                'sku' => (int) $data['sku_id'],
                'quantity' => (int) $data['quantity'],
            ]);

            return response()->json([
                'orderNumber' => $order->order_number,
                'amount' => ProductView::money($order->final_payment_amount),
                'orderName' => mb_substr((string) $order->items->first()?->product_name, 0, 100),
            ]);
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

    public function success(Request $request)
    {
        /** @var Buyer $buyer */
        $buyer = Auth::guard('buyer')->user();

        try {
            $this->orders->confirmToss(
                $buyer,
                (string) $request->query('orderId', ''),
                (string) $request->query('paymentKey', ''),
                (int) $request->query('amount', 0),
            );
        } catch (ValidationException $exception) {
            return $this->backToCheckout($request, collect($exception->errors())->flatten()->first() ?: '결제에 실패했습니다.');
        }

        return redirect()->route('mypage');
    }

    public function fail(Request $request)
    {
        $message = (string) $request->query('message', '결제가 취소되었습니다.');

        return $this->backToCheckout($request, $message);
    }

    private function backToCheckout(Request $request, string $message)
    {
        $back = $request->session()->get('checkout_return', []);

        return redirect()->route('checkout', array_filter([
            'sku' => $back['sku'] ?? null,
            'quantity' => $back['quantity'] ?? null,
            'payment_error' => mb_substr($message, 0, 200),
        ]));
    }
}
