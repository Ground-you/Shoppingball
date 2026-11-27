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
        $lines = $this->parseItems($request);
        abort_if($lines === [], 404);

        $skus = ProductSku::query()
            ->with(['product.store', 'product.images'])
            ->whereIn('id', array_keys($lines))
            ->get()
            ->keyBy('id');

        abort_unless($skus->count() === count($lines), 404);

        $store = null;
        $items = [];

        foreach ($lines as $skuId => $quantity) {
            $sku = $skus->get($skuId);
            $product = $sku->product;
            abort_unless($product && $product->store, 404);
            // 서로 다른 판매자의 상품은 한 번에 주문할 수 없다.
            abort_if($store && $store->id !== $product->store->id, 404);
            $store = $product->store;

            $items[] = [
                'skuId' => $sku->id,
                'name' => $product->product_name,
                'option' => $sku->option_name,
                'unitPrice' => ProductView::skuSale($product, $sku),
                'stock' => $sku->stock_quantity,
                'soldOut' => $sku->status !== 'ON_SALE' || $sku->stock_quantity < 1,
                'image' => ($product->images->firstWhere('is_main', true) ?? $product->images->first())?->image_url,
                'quantity' => max(1, min($quantity, max($sku->stock_quantity, 1))),
            ];
        }

        $policy = $store->policies()->orderByDesc('version_no')->first();

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
                'storeName' => $store->store_name,
                'items' => $items,
                'shipping' => [
                    'fee' => ProductView::money($policy?->default_shipping_fee ?? 0),
                    'freeThreshold' => $policy?->free_shipping_threshold !== null ? ProductView::money($policy->free_shipping_threshold) : null,
                ],
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
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.sku_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'address_id' => ['required', 'integer'],
            'delivery_request' => ['nullable', 'string', 'max:255'],
            'payment_key' => ['nullable', 'string', 'max:200'],
        ], [
            'items.required' => '주문할 상품이 없습니다.',
            'items.*.quantity.required' => '수량을 입력해 주세요.',
            'items.*.quantity.min' => '수량은 1개 이상이어야 합니다.',
            'address_id.required' => '등록된 배송지를 선택해 주세요.',
        ]);

        $address = $this->orders->addressFromBuyer($buyer, (int) $data['address_id']);

        if (! empty($data['delivery_request'])) {
            $address['delivery_request'] = $data['delivery_request'];
        }

        if ($this->orders->driver() === 'toss') {
            $order = $this->orders->checkoutItems($buyer, $data['items'], $address, null, true);
            $order->load('items');
            $request->session()->put('checkout_return', [
                'items' => collect($data['items'])->map(fn ($line) => $line['sku_id'].':'.$line['quantity'])->implode(','),
            ]);

            return response()->json([
                'orderNumber' => $order->order_number,
                'amount' => ProductView::money($order->final_payment_amount),
                'orderName' => $this->orders->orderName($order->items->pluck('product_name')->all()),
            ]);
        }

        $order = $this->orders->checkoutItems($buyer, $data['items'], $address, $data['payment_key'] ?? null);

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
            'items' => $back['items'] ?? null,
            'payment_error' => mb_substr($message, 0, 200),
        ]));
    }

    /**
     * ?items=12:2,15:1 (장바구니) 또는 ?sku=12&quantity=2 (바로구매) -> [skuId => quantity]
     *
     * @return array<int, int>
     */
    private function parseItems(Request $request): array
    {
        $lines = [];

        foreach (array_filter(explode(',', (string) $request->query('items', ''))) as $part) {
            [$id, $quantity] = array_pad(explode(':', $part, 2), 2, '1');
            $id = (int) $id;

            if ($id > 0) {
                $lines[$id] = ($lines[$id] ?? 0) + max(1, min(99, (int) $quantity));
            }
        }

        if (! $lines && $request->integer('sku') > 0) {
            $lines[$request->integer('sku')] = max(1, min(99, $request->integer('quantity', 1)));
        }

        return array_slice($lines, 0, 30, true);
    }
}
