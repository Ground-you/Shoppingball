<?php

namespace App\Support;

use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\Carrier;
use App\Models\Order;
use App\Models\OrderClaim;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Policy;
use App\Models\ProductSku;
use App\Models\Refund;
use App\Models\Shipment;
use App\Models\Store;
use App\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private PaymentGateway $gateway) {}

    public function driver(): string
    {
        return $this->gateway->driver();
    }

    public function checkout(Buyer $buyer, int $skuId, int $quantity, array $address, ?string $paymentKey = null, bool $deferCharge = false): Order
    {
        return DB::transaction(function () use ($buyer, $skuId, $quantity, $address, $paymentKey, $deferCharge) {
            $sku = ProductSku::query()->whereKey($skuId)->lockForUpdate()->first();

            if (! $sku) {
                throw ValidationException::withMessages(['sku' => '상품 옵션을 찾을 수 없습니다.']);
            }

            $sku->load(['product.store.seller']);
            $product = $sku->product;
            $store = $product?->store;

            if (
                ! $product
                || ! $store
                || ! $store->seller
                || $product->sale_status !== 'ON_SALE'
                || $product->approval_status !== 'APPROVED'
                || $store->operation_status !== 'OPEN'
                || $sku->status !== 'ON_SALE'
            ) {
                throw ValidationException::withMessages(['sku' => '지금 구매할 수 없는 상품입니다.']);
            }

            if ($sku->stock_quantity < $quantity) {
                throw ValidationException::withMessages(['quantity' => '재고가 부족합니다.']);
            }

            $policy = $this->policyFor($store);
            $unit = ProductView::skuSale($product, $sku);
            $productAmount = $unit * $quantity;
            $shipping = $this->shippingFee($policy, $productAmount);
            $final = $productAmount + $shipping;
            $orderNumber = $this->number('C');

            if ($deferCharge) {
                $pending = Order::query()
                    ->where('buyer_id', $buyer->id)
                    ->where('order_status', 'PENDING_PAYMENT')
                    ->whereHas('items', fn ($query) => $query->where('product_sku_id', $sku->id))
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($pending) {
                    $pending->forceFill([
                        'recipient_name' => $address['recipient_name'],
                        'recipient_phone' => $address['recipient_phone'],
                        'shipping_postal_code' => $address['postal_code'],
                        'shipping_address_line1' => $address['address_line1'],
                        'shipping_address_line2' => $address['address_line2'] ?? null,
                        'delivery_request' => $address['delivery_request'] ?? null,
                        'total_product_amount' => $productAmount,
                        'shipping_fee' => $shipping,
                        'final_payment_amount' => $final,
                        'ordered_at' => now(),
                    ])->save();

                    $pending->items()->first()?->forceFill([
                        'product_name' => $product->product_name,
                        'option_name' => $sku->option_name,
                        'unit_price' => $unit,
                        'quantity' => $quantity,
                        'item_amount' => $productAmount,
                    ])->save();

                    return $pending;
                }
            }

            $order = Order::query()->create([
                'order_number' => $orderNumber,
                'buyer_id' => $buyer->id,
                'order_status' => $deferCharge ? 'PENDING_PAYMENT' : 'PAID',
                'recipient_name' => $address['recipient_name'],
                'recipient_phone' => $address['recipient_phone'],
                'shipping_postal_code' => $address['postal_code'],
                'shipping_address_line1' => $address['address_line1'],
                'shipping_address_line2' => $address['address_line2'] ?? null,
                'delivery_request' => $address['delivery_request'] ?? null,
                'total_product_amount' => $productAmount,
                'shipping_fee' => $shipping,
                'final_payment_amount' => $final,
                'ordered_at' => now(),
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_sku_id' => $sku->id,
                'store_id' => $store->id,
                'applied_policy_id' => $policy->id,
                'product_name' => $product->product_name,
                'option_name' => $sku->option_name,
                'unit_price' => $unit,
                'quantity' => $quantity,
                'item_amount' => $productAmount,
                'item_status' => 'PREPARING',
            ]);

            if ($deferCharge) {
                return $order;
            }

            $result = $this->gateway->charge([
                'orderNumber' => $orderNumber,
                'amount' => $final,
                'orderName' => $product->product_name,
                'paymentKey' => $paymentKey,
            ]);

            if (! $result->successful) {
                throw ValidationException::withMessages([
                    'payment' => $result->message ?: '결제에 실패했습니다.',
                ]);
            }

            Payment::query()->create([
                'order_id' => $order->id,
                'payment_method' => $this->gateway->driver(),
                'payment_amount' => $final,
                'payment_status' => 'PAID',
                'transaction_id' => $result->transactionId,
                'paid_at' => now(),
            ]);

            $this->takeStock($sku, $quantity);

            return $order;
        });
    }

    public function confirmToss(Buyer $buyer, string $orderNumber, string $paymentKey, int $amount): Order
    {
        $order = Order::query()
            ->with('items')
            ->where('buyer_id', $buyer->id)
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            throw ValidationException::withMessages(['order' => '주문을 찾을 수 없습니다.']);
        }

        if (in_array($order->order_status, ['PAID', 'ACCEPTED', 'PREPARING', 'SHIPPING', 'DELIVERED', 'CONFIRMED'], true) && $order->payments()->where('payment_status', 'PAID')->exists()) {
            return $order;
        }

        if ($order->order_status !== 'PENDING_PAYMENT') {
            throw ValidationException::withMessages(['order' => '결제할 수 없는 주문입니다.']);
        }

        if (ProductView::money($order->final_payment_amount) !== $amount) {
            throw ValidationException::withMessages(['amount' => '결제 금액이 주문 금액과 다릅니다.']);
        }

        $result = $this->gateway->charge([
            'orderNumber' => $order->order_number,
            'amount' => $amount,
            'orderName' => (string) $order->items->first()?->product_name,
            'paymentKey' => $paymentKey,
        ]);

        if (! $result->successful) {
            throw ValidationException::withMessages([
                'payment' => $result->message ?: '결제에 실패했습니다.',
            ]);
        }

        try {
            DB::transaction(function () use ($order, $result, $amount) {
                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();

                if ($locked->order_status !== 'PENDING_PAYMENT') {
                    return;
                }

                $locked->load('items');

                foreach ($locked->items as $item) {
                    $sku = ProductSku::query()->whereKey($item->product_sku_id)->lockForUpdate()->first();

                    if (! $sku || $sku->stock_quantity < $item->quantity) {
                        throw new \RuntimeException('stock');
                    }
                }

                foreach ($locked->items as $item) {
                    $sku = ProductSku::query()->whereKey($item->product_sku_id)->lockForUpdate()->first();
                    $this->takeStock($sku, (int) $item->quantity);
                }

                $locked->order_status = 'PAID';
                $locked->save();

                Payment::query()->create([
                    'order_id' => $locked->id,
                    'payment_method' => 'toss',
                    'payment_amount' => $amount,
                    'payment_status' => 'PAID',
                    'transaction_id' => $result->transactionId,
                    'paid_at' => now(),
                ]);
            });
        } catch (\RuntimeException) {
            $this->gateway->refund([
                'transactionId' => $result->transactionId,
                'orderNumber' => $order->order_number,
                'amount' => $amount,
                'reason' => '재고 부족으로 결제를 취소합니다.',
            ]);

            throw ValidationException::withMessages(['quantity' => '재고가 부족해 결제를 취소했습니다.']);
        }

        return $order->fresh();
    }

    public function requestCancel(Buyer $buyer, Order $order, string $reason): void
    {
        DB::transaction(function () use ($buyer, $order, $reason) {
            $order = $this->lockBuyerOrder($buyer, $order);

            if (! in_array($order->order_status, ['PAID', 'ACCEPTED', 'PREPARING'], true)) {
                throw ValidationException::withMessages(['order' => '발송 전 주문만 취소할 수 있습니다.']);
            }

            $this->guardOpenClaim($order);

            $claim = OrderClaim::query()->create([
                'claim_number' => $this->number('M'),
                'order_id' => $order->id,
                'claim_type' => 'CANCEL',
                'claim_status' => 'REQUESTED',
                'reason_code' => 'BUYER',
                'reason_detail' => $reason,
                'requested_at' => now(),
            ]);

            foreach ($order->items as $item) {
                $claim->items()->create([
                    'order_item_id' => $item->id,
                    'quantity' => $item->quantity,
                ]);
            }
        });
    }

    public function requestExchange(Buyer $buyer, Order $order, int $itemId, int $exchangeSkuId, int $quantity, string $reason): void
    {
        DB::transaction(function () use ($buyer, $order, $itemId, $exchangeSkuId, $quantity, $reason) {
            $order = $this->lockBuyerOrder($buyer, $order);
            $reason = $this->assertReason($reason, '교환');

            if ($order->order_status === 'CANCELED') {
                throw ValidationException::withMessages(['order' => '취소된 주문은 교환할 수 없습니다.']);
            }

            $this->guardOpenClaim($order);
            $item = $order->items->firstWhere('id', $itemId);

            if (! $item) {
                throw ValidationException::withMessages(['order_item_id' => '주문 상품을 찾을 수 없습니다.']);
            }

            if ($quantity < 1 || $quantity > $item->quantity) {
                throw ValidationException::withMessages(['quantity' => '교환 수량은 주문 수량을 넘을 수 없습니다.']);
            }

            $current = ProductSku::query()->find($item->product_sku_id);
            $exchange = ProductSku::query()->find($exchangeSkuId);

            if (! $current || ! $exchange || $exchange->product_id !== $current->product_id) {
                throw ValidationException::withMessages(['exchange_product_sku_id' => '같은 상품의 옵션만 교환할 수 있습니다.']);
            }

            if ($exchange->status !== 'ON_SALE') {
                throw ValidationException::withMessages(['exchange_product_sku_id' => '판매중인 옵션만 교환할 수 있습니다.']);
            }

            $claim = OrderClaim::query()->create([
                'claim_number' => $this->number('M'),
                'order_id' => $order->id,
                'claim_type' => 'EXCHANGE',
                'claim_status' => 'REQUESTED',
                'reason_code' => 'BUYER',
                'reason_detail' => $reason,
                'requested_at' => now(),
            ]);

            $claim->items()->create([
                'order_item_id' => $item->id,
                'quantity' => $quantity,
                'exchange_product_sku_id' => $exchange->id,
            ]);
        });
    }

    public function requestReturn(Buyer $buyer, Order $order, string $reason): void
    {
        DB::transaction(function () use ($buyer, $order, $reason) {
            $order = $this->lockBuyerOrder($buyer, $order);
            $reason = $this->assertReason($reason, '환불');

            if (! in_array($order->order_status, ['DELIVERED', 'CONFIRMED'], true)) {
                throw ValidationException::withMessages(['order' => '배송이 완료된 주문만 환불을 요청할 수 있습니다.']);
            }

            $this->guardOpenClaim($order);

            $claim = OrderClaim::query()->create([
                'claim_number' => $this->number('M'),
                'order_id' => $order->id,
                'claim_type' => 'RETURN',
                'claim_status' => 'REQUESTED',
                'reason_code' => 'BUYER',
                'reason_detail' => $reason,
                'requested_at' => now(),
            ]);

            foreach ($order->items as $item) {
                $claim->items()->create([
                    'order_item_id' => $item->id,
                    'quantity' => $item->quantity,
                ]);
            }
        });
    }

    public function hideFromBuyer(Buyer $buyer, Order $order): void
    {
        DB::transaction(function () use ($buyer, $order) {
            $order = $this->lockBuyerOrder($buyer, $order);

            if (! in_array($order->order_status, ['CANCELED', 'DELIVERED', 'CONFIRMED'], true)) {
                throw ValidationException::withMessages(['order' => '취소되었거나 배송이 완료된 주문만 삭제할 수 있습니다.']);
            }

            $order->forceFill(['buyer_hidden_at' => now()])->save();
        });
    }

    public function progress(Store $store, Order $order, string $action, ?int $carrierId = null): void
    {
        DB::transaction(function () use ($store, $order, $action, $carrierId) {
            $order = $this->lockStoreOrder($store, $order);
            $order->load('shipments');
            $shipment = $order->shipments->firstWhere('shipment_type', 'OUTBOUND');

            if ($order->claims()->where('claim_status', 'REQUESTED')->exists()) {
                throw ValidationException::withMessages(['action' => '취소·환불·교환 요청을 먼저 처리해 주세요.']);
            }

            $carrier = null;

            if ($action === 'ship') {
                $carrier = Carrier::query()->where('is_active', true)->find($carrierId);

                if (! $carrier) {
                    throw ValidationException::withMessages(['carrier_id' => '택배사를 선택해 주세요.']);
                }
            } elseif ($carrierId) {
                $carrier = Carrier::query()->where('is_active', true)->find($carrierId);
            }

            if ($action === 'approve') {
                if ($order->order_status !== 'PAID') {
                    throw ValidationException::withMessages(['action' => '이 주문은 지금 그 상태로 바꿀 수 없습니다.']);
                }

                $order->order_status = 'ACCEPTED';
                $order->save();

                return;
            }

            if ($action === 'prepare') {
                if ($order->order_status !== 'ACCEPTED') {
                    throw ValidationException::withMessages(['action' => '이 주문은 지금 그 상태로 바꿀 수 없습니다.']);
                }

                $order->order_status = 'PREPARING';
                $order->save();
                $order->items()->update(['item_status' => 'PREPARING']);

                return;
            }

            if ($action === 'ready') {
                if ($order->order_status !== 'PREPARING' || $shipment) {
                    throw ValidationException::withMessages(['action' => '이 주문은 지금 그 상태로 바꿀 수 없습니다.']);
                }

                $shipment = $this->outboundShipment($order, $store);
                $shipment->shipment_status = 'PREPARING';
                $shipment->carrier_id = $carrier?->id ?? $shipment->carrier_id;
                $shipment->save();

                return;
            }

            if ($action === 'ship') {
                if ($order->order_status !== 'PREPARING' || ! $shipment || $shipment->shipment_status !== 'PREPARING') {
                    throw ValidationException::withMessages(['action' => '배송 준비를 먼저 해 주세요.']);
                }
                $shipment->carrier_id = $carrier->id;
                $shipment->shipment_status = 'SHIPPING';
                $shipment->dispatched_at = now();
                $shipment->save();
                $order->order_status = 'SHIPPING';
                $order->save();
                $order->items()->update(['item_status' => 'SHIPPING']);

                return;
            }

            if ($action === 'deliver') {
                if ($order->order_status !== 'SHIPPING') {
                    throw ValidationException::withMessages(['action' => '이 주문은 지금 그 상태로 바꿀 수 없습니다.']);
                }

                $shipment = $this->outboundShipment($order, $store);
                $shipment->shipment_status = 'DELIVERED';
                $shipment->delivered_at = now();
                $shipment->save();
                $order->order_status = 'DELIVERED';
                $order->save();
                $order->items()->update(['item_status' => 'DELIVERED']);

                return;
            }

            throw ValidationException::withMessages(['action' => '처리할 수 없는 요청입니다.']);
        });
    }

    public function decideClaim(Store $store, Order $order, OrderClaim $claim, string $decision, ?string $rejectReason = null): void
    {
        DB::transaction(function () use ($store, $order, $claim, $decision, $rejectReason) {
            $order = $this->lockStoreOrder($store, $order);
            $claim = $order->claims()->whereKey($claim->id)->lockForUpdate()->first();

            if (! $claim) {
                throw ValidationException::withMessages(['claim' => '요청을 찾을 수 없습니다.']);
            }

            $claim->load('items');
            $status = $claim->claim_status;

            if ($decision === 'reject') {
                if ($status !== 'REQUESTED') {
                    throw ValidationException::withMessages(['decision' => '요청 상태만 거절할 수 있습니다.']);
                }

                if (! $rejectReason) {
                    throw ValidationException::withMessages(['reject_reason' => '거절 사유를 입력해 주세요.']);
                }

                $claim->forceFill([
                    'claim_status' => 'REJECTED',
                    'reject_reason' => $rejectReason,
                    'responded_at' => now(),
                ])->save();

                return;
            }

            if ($claim->claim_type === 'CANCEL' && $decision === 'approve') {
                if ($status !== 'REQUESTED') {
                    throw ValidationException::withMessages(['decision' => '취소 요청만 승인할 수 있습니다.']);
                }

                $this->refundAndRestock($order, $claim);

                return;
            }

            if ($claim->claim_type === 'EXCHANGE' && $decision === 'approve') {
                if ($status !== 'REQUESTED') {
                    throw ValidationException::withMessages(['decision' => '교환 요청만 승인할 수 있습니다.']);
                }

                $claim->forceFill([
                    'claim_status' => 'APPROVED',
                    'responded_at' => now(),
                    'seller_response' => '교환 승인',
                ])->save();

                return;
            }

            if ($claim->claim_type === 'RETURN' && $decision === 'approve') {
                if ($status !== 'REQUESTED') {
                    throw ValidationException::withMessages(['decision' => '환불 요청만 수락할 수 있습니다.']);
                }

                $this->refundAndRestock($order, $claim, 'RETURNED', null, '환불 수락, 결제 취소, 재고 복원');

                return;
            }

            if ($claim->claim_type === 'EXCHANGE' && $decision === 'complete') {
                if ($status !== 'APPROVED') {
                    throw ValidationException::withMessages(['decision' => '승인한 교환만 완료할 수 있습니다.']);
                }

                $claim->forceFill([
                    'claim_status' => 'COMPLETED',
                    'completed_at' => now(),
                    'seller_response' => '교환 처리 완료',
                ])->save();

                return;
            }

            throw ValidationException::withMessages(['decision' => '처리할 수 없는 요청입니다.']);
        });
    }

    public function buyerOrders(Buyer $buyer): array
    {
        $orders = Order::query()
            ->with(['payments.refunds', 'items', 'claims', 'shipments.carrier'])
            ->where('buyer_id', $buyer->id)
            ->whereNull('buyer_hidden_at')
            ->orderByDesc('ordered_at')
            ->get();

        $skus = ProductSku::query()
            ->with(['product.images', 'product.skus'])
            ->whereIn('id', $orders->flatMap->items->pluck('product_sku_id'))
            ->get()
            ->keyBy('id');

        return $orders->map(function (Order $order) use ($skus) {
            $open = $order->claims->contains(fn ($claim) => $claim->claim_status === 'REQUESTED');
            $code = $order->order_status;
            $statusName = $this->claimHeadline($order) ?? StatusLabel::name('order', $code);
            $payment = $order->payments->sortByDesc('id')->first();
            $refundAmount = $order->payments->sum(fn (Payment $payment) => $payment->refunds->sum(fn (Refund $refund) => (float) $refund->refund_amount));
            $shipment = $order->shipments->firstWhere('shipment_type', 'OUTBOUND');
            $trackingNumber = $shipment?->tracking_number;
            $template = $shipment?->carrier?->tracking_url_template;
            $trackingUrl = ($template && $trackingNumber)
                ? str_replace(['{tracking_number}', '{trackingNumber}'], $trackingNumber, $template)
                : null;

            return [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'orderedAt' => $order->ordered_at?->format('Y-m-d H:i'),
                'statusName' => $statusName,
                'claimNote' => $this->claimNote($order),
                'paymentStatus' => StatusLabel::name('payment', $payment?->payment_status),
                'refundAmount' => ProductView::money($refundAmount),
                'finalPaymentAmount' => ProductView::money($order->final_payment_amount),
                'canCancel' => in_array($code, ['PAID', 'ACCEPTED', 'PREPARING'], true) && ! $open,
                'canExchange' => ! in_array($code, ['CANCELED', 'PENDING_PAYMENT'], true) && ! $open,
                'canReturn' => in_array($code, ['DELIVERED', 'CONFIRMED'], true) && ! $open,
                'canDelete' => in_array($code, ['CANCELED', 'DELIVERED', 'CONFIRMED'], true),
                'tracking' => [
                    'shipped' => in_array($code, ['SHIPPING', 'DELIVERED'], true),
                    'carrier' => $shipment?->carrier?->carrier_name,
                    'number' => $trackingNumber,
                    'url' => $trackingUrl,
                ],
                'items' => $order->items->map(function (OrderItem $item) use ($skus) {
                    $sku = $skus->get($item->product_sku_id);
                    $options = $sku?->product?->skus ?? collect();
                    $image = $sku?->product?->images?->firstWhere('is_main', true) ?? $sku?->product?->images?->first();

                    return [
                        'id' => $item->id,
                        'productId' => $sku?->product_id,
                        'name' => $item->product_name,
                        'image' => $image?->image_url,
                        'option' => $item->option_name,
                        'quantity' => $item->quantity,
                        'amount' => ProductView::money($item->item_amount),
                        'exchangeOptions' => $options->map(fn (ProductSku $option) => [
                            'id' => $option->id,
                            'name' => $option->option_name,
                            'stock' => $option->stock_quantity,
                            'soldOut' => $option->status !== 'ON_SALE',
                        ])->values(),
                    ];
                })->values(),
            ];
        })->values()->all();
    }

    private function claimHeadline(Order $order): ?string
    {
        if (! $order->relationLoaded('claims')) {
            return null;
        }

        $claims = $order->claims;

        if ($order->order_status !== 'CANCELED' && $claims->contains(fn (OrderClaim $claim) => $claim->claim_type === 'CANCEL' && $claim->claim_status === 'REQUESTED')) {
            return '취소요청';
        }

        if ($claims->contains(fn (OrderClaim $claim) => $claim->claim_type === 'RETURN' && in_array($claim->claim_status, ['REQUESTED', 'APPROVED', 'PICKING_UP'], true))) {
            return '환불요청';
        }

        if ($claims->contains(fn (OrderClaim $claim) => $claim->claim_type === 'RETURN' && $claim->claim_status === 'COMPLETED')) {
            return '환불완료';
        }

        if ($order->order_status !== 'CANCELED' && $claims->contains(fn (OrderClaim $claim) => $claim->claim_type === 'EXCHANGE' && in_array($claim->claim_status, ['REQUESTED', 'APPROVED', 'PICKING_UP'], true))) {
            return '교환요청';
        }

        return null;
    }

    private function claimNote(Order $order): ?string
    {
        $latest = $order->claims->sortByDesc('id')->first();

        if (! $latest) {
            return null;
        }

        $name = match ($latest->claim_type) {
            'CANCEL' => '취소',
            'RETURN' => '환불',
            default => '교환',
        };

        if ($latest->claim_status === 'REJECTED') {
            $reason = trim((string) $latest->reject_reason);

            return $reason !== '' ? $name.' 요청이 거절되었습니다. 사유: '.$reason : $name.' 요청이 거절되었습니다.';
        }

        if ($latest->claim_status === 'REQUESTED') {
            return '판매자 확인을 기다리고 있습니다.';
        }

        if ($latest->claim_status === 'COMPLETED' && in_array($latest->claim_type, ['CANCEL', 'RETURN'], true)) {
            return '환불이 완료되었습니다.';
        }

        if ($latest->claim_type === 'EXCHANGE' && $latest->claim_status === 'APPROVED') {
            return '교환이 승인되었습니다.';
        }

        if ($latest->claim_type === 'EXCHANGE' && $latest->claim_status === 'COMPLETED') {
            return '교환 처리가 완료되었습니다.';
        }

        return null;
    }

    public function sellerRows(Store $store, ?string $keyword, ?string $status): array
    {
        $keyword = trim((string) $keyword);

        return Order::query()
            ->with(['payments.refunds', 'items', 'claims'])
            ->where('order_status', '!=', 'PENDING_PAYMENT')
            ->whereHas('items', fn ($query) => $query->where('store_id', $store->id))
            ->when($keyword !== '', function ($query) use ($store, $keyword) {
                $term = '%'.addcslashes($keyword, '%_\\').'%';
                $query->whereHas('items', fn ($items) => $items
                    ->where('store_id', $store->id)
                    ->where('product_name', 'ilike', $term));
            })
            ->when($status === 'PAID', fn ($query) => $query->where('order_status', 'PAID'))
            ->when($status === 'CLAIM', fn ($query) => $query->whereHas(
                'claims',
                fn ($claims) => $claims->where('claim_status', 'REQUESTED')->whereIn('claim_type', ['CANCEL', 'RETURN']),
            ))
            ->orderByDesc('ordered_at')
            ->get()
            ->map(fn (Order $order) => $this->sellerSummary($order))
            ->values()
            ->all();
    }

    public function sellerDetail(Store $store, Order $order): array
    {
        abort_if($order->order_status === 'PENDING_PAYMENT', 404);
        $this->assertStoreOrder($store, $order);
        $order->load([
            'payments.refunds',
            'items',
            'shipments.carrier',
            'claims.items.orderItem',
            'claims.items.exchangeSku.product',
        ]);

        $summary = $this->sellerSummary($order);
        $code = $order->order_status;
        $openClaim = $order->claims->contains(fn ($claim) => $claim->claim_status === 'REQUESTED');
        $shipment = $order->shipments->firstWhere('shipment_type', 'OUTBOUND');

        return $summary + [
            'phone' => $order->recipient_phone,
            'postalCode' => $order->shipping_postal_code,
            'address' => trim($order->shipping_address_line1.' '.($order->shipping_address_line2 ?? '')),
            'deliveryRequest' => $order->delivery_request,
            'shippingFee' => ProductView::money($order->shipping_fee),
            'productAmount' => ProductView::money($order->total_product_amount),
            'carrierId' => $shipment?->carrier_id,
            'shipmentStatus' => $shipment ? StatusLabel::name('shipment', $shipment->shipment_status) : null,
            'actions' => [
                'approve' => $code === 'PAID' && ! $openClaim,
                'prepare' => $code === 'ACCEPTED' && ! $openClaim,
                'ready' => $code === 'PREPARING' && ! $shipment && ! $openClaim,
                'ship' => $code === 'PREPARING' && $shipment?->shipment_status === 'PREPARING' && ! $openClaim,
                'deliver' => $code === 'SHIPPING' && ! $openClaim,
            ],
            'claims' => $order->claims->map(fn (OrderClaim $claim) => $this->presentClaim($claim))->values(),
        ];
    }

    public function carriers(): array
    {
        foreach ([
            ['CJ', 'CJ대한통운'],
            ['HANJIN', '한진택배'],
            ['LOTTE', '롯데택배'],
            ['EPOST', '우체국택배'],
            ['LOGEN', '로젠택배'],
        ] as [$code, $name]) {
            Carrier::query()->firstOrCreate(
                ['carrier_code' => $code],
                ['carrier_name' => $name, 'is_active' => true],
            );
        }

        return Carrier::query()
            ->where('is_active', true)
            ->orderBy('carrier_name')
            ->get()
            ->map(fn (Carrier $carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->carrier_name,
            ])
            ->all();
    }

    public function addressFromBuyer(Buyer $buyer, int $addressId): array
    {
        $address = BuyerAddress::query()
            ->where('buyer_id', $buyer->id)
            ->find($addressId);

        if (! $address) {
            throw ValidationException::withMessages(['address_id' => '배송지를 찾을 수 없습니다.']);
        }

        return [
            'recipient_name' => $address->recipient_name,
            'recipient_phone' => $address->recipient_phone,
            'postal_code' => $address->postal_code,
            'address_line1' => $address->address_line1,
            'address_line2' => $address->address_line2,
        ];
    }

    private function sellerSummary(Order $order): array
    {
        $payment = $order->payments->sortByDesc('id')->first();

        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'orderedAt' => $order->ordered_at?->format('Y-m-d H:i'),
            'statusName' => $this->claimHeadline($order) ?? StatusLabel::name('order', $order->order_status),
            'statusCode' => $order->order_status,
            'recipient' => $order->recipient_name,
            'amount' => ProductView::money($order->final_payment_amount),
            'payment' => [
                'method' => $payment?->payment_method === 'toss' ? '토스페이먼츠' : '테스트 결제',
                'status' => StatusLabel::name('payment', $payment?->payment_status),
                'paid' => $payment?->payment_status === 'PAID',
                'paidAt' => $payment?->paid_at?->format('Y-m-d H:i'),
                'amount' => ProductView::money($payment?->payment_amount),
                'refund' => $payment?->relationLoaded('refunds')
                    ? ProductView::money($payment->refunds->sum(fn (Refund $refund) => (float) $refund->refund_amount))
                    : 0,
            ],
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'name' => $item->product_name,
                'option' => $item->option_name,
                'quantity' => $item->quantity,
                'unitPrice' => ProductView::money($item->unit_price),
                'amount' => ProductView::money($item->item_amount),
            ])->values(),
        ];
    }

    private function presentClaim(OrderClaim $claim): array
    {
        $item = $claim->items->first();
        $exchange = $item?->exchangeSku;
        $orderItem = $item?->orderItem;
        $difference = null;
        $stock = null;

        if ($exchange && $orderItem) {
            $exchangeUnit = $exchange->product
                ? ProductView::skuSale($exchange->product, $exchange)
                : ProductView::money($exchange->sale_price);
            $difference = ($exchangeUnit - ProductView::money($orderItem->unit_price)) * $item->quantity;
            $stock = $exchange->stock_quantity;
        }

        $code = $claim->claim_status;

        return [
            'id' => $claim->id,
            'number' => $claim->claim_number,
            'type' => $claim->claim_type,
            'typeName' => match ($claim->claim_type) {
                'CANCEL' => '취소',
                'RETURN' => '환불',
                default => '교환',
            },
            'status' => StatusLabel::name('claim', $code),
            'statusCode' => $code,
            'reason' => $claim->reason_detail,
            'rejectReason' => $claim->reject_reason,
            'quantity' => $item?->quantity,
            'productName' => $orderItem?->product_name,
            'optionName' => $orderItem?->option_name,
            'exchangeOption' => $exchange?->option_name,
            'exchangeStock' => $stock,
            'exchangeSoldOut' => $exchange?->status === 'SOLD_OUT',
            'additionalAmount' => $difference,
            'canApprove' => $code === 'REQUESTED',
            'canReject' => $code === 'REQUESTED',
            'canComplete' => $claim->claim_type === 'EXCHANGE' && $code === 'APPROVED',
        ];
    }

    private function refundAndRestock(Order $order, OrderClaim $claim, string $itemStatus = 'CANCELED', ?string $orderStatus = 'CANCELED', string $sellerResponse = '취소 승인, 결제 취소, 재고 복원'): void
    {
        $payment = $order->payments()->lockForUpdate()->latest('id')->first();

        if (! $payment || $payment->payment_status !== 'PAID') {
            throw ValidationException::withMessages(['payment' => '결제 완료된 주문만 취소할 수 있습니다.']);
        }

        $amount = ProductView::money($payment->payment_amount);
        $result = $this->gateway->refund([
            'transactionId' => (string) $payment->transaction_id,
            'orderNumber' => $order->order_number,
            'amount' => $amount,
            'reason' => $claim->reason_detail ?: '판매자 취소 승인',
        ]);

        if (! $result->successful) {
            throw ValidationException::withMessages([
                'payment' => $result->message ?: '결제 취소에 실패했습니다.',
            ]);
        }

        $payment->forceFill([
            'payment_status' => 'CANCELED',
            'canceled_at' => now(),
        ])->save();

        Refund::query()->create([
            'payment_id' => $payment->id,
            'order_claim_id' => $claim->id,
            'refund_amount' => $amount,
            'refund_status' => 'COMPLETED',
            'reason' => $claim->reason_detail,
            'refunded_at' => now(),
            'pg_refund_transaction_id' => $result->transactionId,
        ]);

        foreach ($order->items as $item) {
            $sku = ProductSku::query()->whereKey($item->product_sku_id)->lockForUpdate()->first();

            if ($sku) {
                $this->restoreStock($sku, $item->quantity);
            }
        }

        if ($orderStatus !== null) {
            $order->forceFill([
                'order_status' => $orderStatus,
                'canceled_at' => now(),
            ])->save();
        }

        $order->items()->update(['item_status' => $itemStatus]);

        $claim->forceFill([
            'claim_status' => 'COMPLETED',
            'responded_at' => now(),
            'completed_at' => now(),
            'seller_response' => $sellerResponse,
        ])->save();
    }

    private function takeStock(ProductSku $sku, int $quantity): void
    {
        $sku->stock_quantity -= $quantity;

        if ($sku->stock_quantity <= 0) {
            $sku->stock_quantity = 0;
            $sku->status = 'SOLD_OUT';
        }

        $sku->save();
    }

    private function restoreStock(ProductSku $sku, int $quantity): void
    {
        $sku->stock_quantity += $quantity;

        if ($sku->stock_quantity > 0 && $sku->status === 'SOLD_OUT') {
            $sku->status = 'ON_SALE';
        }

        $sku->save();
    }

    private function outboundShipment(Order $order, Store $store): Shipment
    {
        $shipment = $order->shipments()->where('shipment_type', 'OUTBOUND')->first();

        if ($shipment) {
            return $shipment;
        }

        $shipment = Shipment::query()->create([
            'order_id' => $order->id,
            'store_id' => $store->id,
            'shipment_type' => 'OUTBOUND',
            'shipment_status' => 'PREPARING',
        ]);

        foreach ($order->items as $item) {
            $shipment->items()->create([
                'order_item_id' => $item->id,
                'quantity' => $item->quantity,
            ]);
        }

        return $shipment;
    }

    private function policyFor(Store $store): Policy
    {
        $policy = $store->policies()->orderByDesc('version_no')->first();

        if ($policy) {
            return $policy;
        }

        return $store->policies()->create([
            'version_no' => 1,
            'default_shipping_fee' => 0,
            'effective_from' => now(),
        ]);
    }

    private function shippingFee(Policy $policy, int $productAmount): int
    {
        $fee = ProductView::money($policy->default_shipping_fee);
        $threshold = $policy->free_shipping_threshold !== null
            ? ProductView::money($policy->free_shipping_threshold)
            : null;

        if ($threshold !== null && $productAmount >= $threshold) {
            return 0;
        }

        return $fee;
    }

    private function assertReason(string $reason, string $label): string
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reason_detail' => $label.' 사유는 5자 이상 입력해 주세요.',
            ]);
        }

        return $reason;
    }

    private function lockBuyerOrder(Buyer $buyer, Order $order): Order
    {
        $locked = Order::query()->where('buyer_id', $buyer->id)->whereKey($order->id)->lockForUpdate()->first();

        if (! $locked) {
            throw ValidationException::withMessages(['order' => '주문을 찾을 수 없습니다.']);
        }

        $locked->load('items', 'claims');

        return $locked;
    }

    private function lockStoreOrder(Store $store, Order $order): Order
    {
        $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();

        if (! $locked) {
            throw ValidationException::withMessages(['order' => '주문을 찾을 수 없습니다.']);
        }

        $locked->load('items');
        $this->assertStoreOrder($store, $locked);

        return $locked;
    }

    private function assertStoreOrder(Store $store, Order $order): void
    {
        $order->loadMissing('items');
        $ownsAll = $order->items->isNotEmpty()
            && $order->items->every(fn (OrderItem $item) => (int) $item->store_id === (int) $store->id);

        if (! $ownsAll) {
            abort(404);
        }
    }

    private function guardOpenClaim(Order $order): void
    {
        $open = $order->claims->contains(fn ($claim) => $claim->claim_status === 'REQUESTED');

        if ($open) {
            throw ValidationException::withMessages(['order' => '처리 중인 요청이 있습니다.']);
        }
    }

    private function number(string $prefix): string
    {
        return $prefix.now()->format('ymdHis').str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
