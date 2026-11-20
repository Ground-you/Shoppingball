<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\Order;
use App\Support\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyerOrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function cancel(Request $request, Order $order)
    {
        /** @var Buyer $buyer */
        $buyer = Auth::guard('buyer')->user();
        abort_unless($order->buyer_id === $buyer->id, 404);

        $data = $request->validate([
            'reason_detail' => ['required', 'string', 'max:500'],
        ], [
            'reason_detail.required' => '취소 사유를 입력해 주세요.',
        ]);

        $this->orders->requestCancel($buyer, $order, $data['reason_detail']);

        return response()->json(['redirect' => route('mypage')]);
    }

    public function exchange(Request $request, Order $order)
    {
        /** @var Buyer $buyer */
        $buyer = Auth::guard('buyer')->user();
        abort_unless($order->buyer_id === $buyer->id, 404);

        $data = $request->validate([
            'order_item_id' => ['required', 'integer'],
            'exchange_product_sku_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason_detail' => ['required', 'string', 'max:500'],
        ], [
            'exchange_product_sku_id.required' => '교환할 옵션을 선택해 주세요.',
            'reason_detail.required' => '교환 사유를 입력해 주세요.',
        ]);

        $this->orders->requestExchange(
            $buyer,
            $order,
            (int) $data['order_item_id'],
            (int) $data['exchange_product_sku_id'],
            (int) $data['quantity'],
            $data['reason_detail'],
        );

        return response()->json(['redirect' => route('mypage')]);
    }
}
