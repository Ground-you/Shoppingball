<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderClaim;
use App\Support\OrderService;
use App\Support\SellerContext;
use App\Support\SellerPage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request)
    {
        $store = SellerContext::store();
        $status = $request->string('status')->toString();

        return SellerPage::render('SellerOrders', [
            'section' => 'orders',
            'keyword' => $request->string('q')->toString(),
            'status' => $status === 'PREPARING' ? 'PREPARING' : '',
            'orders' => $this->orders->sellerRows($store, $request->string('q')->toString(), $status === 'PREPARING' ? 'PREPARING' : null),
        ]);
    }

    public function show(Order $order)
    {
        $store = SellerContext::store();

        return SellerPage::render('SellerOrderDetail', [
            'section' => 'orders',
            'order' => $this->orders->sellerDetail($store, $order),
            'carriers' => $this->orders->carriers(),
        ]);
    }

    public function progress(Request $request, Order $order)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'prepare', 'ready', 'ship', 'deliver'])],
            'carrier_id' => ['nullable', 'integer'],
        ]);

        $this->orders->progress(
            SellerContext::store(),
            $order,
            $data['action'],
            isset($data['carrier_id']) ? (int) $data['carrier_id'] : null,
        );

        return response()->json(['redirect' => route('seller.orders.show', $order)]);
    }

    public function claim(Request $request, Order $order, OrderClaim $claim)
    {
        abort_unless($claim->order_id === $order->id, 404);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'complete'])],
            'reject_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->orders->decideClaim(
            SellerContext::store(),
            $order,
            $claim,
            $data['decision'],
            $data['reject_reason'] ?? null,
        );

        return response()->json(['redirect' => route('seller.orders.show', $order)]);
    }
}
