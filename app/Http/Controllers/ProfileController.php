<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\BuyerPaymentMethod;
use App\Support\BuyerInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        /** @var Buyer $buyer */
        $buyer = Auth::guard('buyer')->user();

        $orders = DB::table('orders')
            ->where('buyer_id', $buyer->id)
            ->orderByDesc('ordered_at')
            ->get();

        $items = DB::table('order_items')
            ->whereIn('order_id', $orders->pluck('id'))
            ->get()
            ->groupBy('order_id');

        return view('app', [
            'page' => [
                'component' => 'Profile',
                'shortcuts' => ['오늘도착', '신선마켓', '골드딜', '기획전', '해외직구', '브랜드특가'],
                'buyer' => [
                    'loginId' => $buyer->login_id,
                    'name' => $buyer->buyer_name,
                    'nickname' => $buyer->nickname,
                    'email' => $buyer->email,
                    'phone' => $buyer->phone,
                ],
                'orders' => $orders->map(function ($order) use ($items) {
                    return [
                        'id' => $order->id,
                        'orderNumber' => $order->order_number,
                        'orderedAt' => $order->ordered_at,
                        'finalPaymentAmount' => $order->final_payment_amount,
                        'items' => ($items[$order->id] ?? collect())->map(fn ($item) => [
                            'name' => $item->product_name,
                            'option' => $item->option_name,
                            'quantity' => $item->quantity,
                            'amount' => $item->item_amount,
                        ])->values(),
                    ];
                })->values(),
                'addresses' => BuyerAddress::query()
                    ->where('buyer_id', $buyer->id)
                    ->latest()
                    ->get(['id', 'address_name', 'recipient_name', 'recipient_phone', 'postal_code', 'address_line1', 'address_line2', 'is_default']),
                'paymentMethods' => BuyerPaymentMethod::query()
                    ->where('buyer_id', $buyer->id)
                    ->latest()
                    ->get(['id', 'method_type', 'alias', 'provider_name', 'number_last4', 'is_default']),
            ],
        ]);
    }

    public function update(Request $request)
    {
        /** @var Buyer $buyer */
        $buyer = Auth::guard('buyer')->user();

        BuyerInput::prepare($request);

        $data = $request->validate([
            'buyer_name' => BuyerInput::name(),
            'nickname' => BuyerInput::nickname(),
            'email' => BuyerInput::email($buyer->id),
            'phone' => BuyerInput::phone(),
        ], BuyerInput::messages());

        $buyer->fill([
            'buyer_name' => $data['buyer_name'],
            'nickname' => $data['nickname'] ?: null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
        ])->save();

        return response()->json(['ok' => true]);
    }

    public function storeAddress(Request $request)
    {
        $buyer = Auth::guard('buyer')->user();
        $address = BuyerAddress::query()->create([
            'buyer_id' => $buyer->id,
            ...$this->addressData($request),
        ]);

        if (! BuyerAddress::query()->where('buyer_id', $buyer->id)->where('id', '!=', $address->id)->exists()) {
            $address->is_default = true;
            $address->save();
        }

        $this->syncDefaultAddress($buyer->id, $address);

        return response()->json(['ok' => true]);
    }

    public function updateAddress(Request $request, BuyerAddress $address)
    {
        $this->ownAddress($address);
        $address->fill($this->addressData($request))->save();
        $this->syncDefaultAddress($address->buyer_id, $address);

        return response()->json(['ok' => true]);
    }

    public function destroyAddress(BuyerAddress $address)
    {
        $this->ownAddress($address);
        $wasDefault = $address->is_default;
        $buyerId = $address->buyer_id;
        $address->delete();

        if ($wasDefault) {
            $next = BuyerAddress::query()->where('buyer_id', $buyerId)->latest()->first();

            if ($next) {
                $next->is_default = true;
                $next->save();
            }
        }

        return response()->json(['ok' => true]);
    }

    public function setDefaultAddress(BuyerAddress $address)
    {
        $this->ownAddress($address);
        $address->is_default = true;
        $address->save();
        $this->syncDefaultAddress($address->buyer_id, $address);

        return response()->json(['ok' => true]);
    }

    public function storePaymentMethod(Request $request)
    {
        $buyer = Auth::guard('buyer')->user();
        $method = BuyerPaymentMethod::query()->create([
            'buyer_id' => $buyer->id,
            ...$this->paymentData($request),
        ]);
        $this->syncDefaultPayment($buyer->id, $method);

        return response()->json(['ok' => true]);
    }

    public function updatePaymentMethod(Request $request, BuyerPaymentMethod $paymentMethod)
    {
        $this->ownPayment($paymentMethod);
        $paymentMethod->fill($this->paymentData($request))->save();
        $this->syncDefaultPayment($paymentMethod->buyer_id, $paymentMethod);

        return response()->json(['ok' => true]);
    }

    public function destroyPaymentMethod(BuyerPaymentMethod $paymentMethod)
    {
        $this->ownPayment($paymentMethod);
        $paymentMethod->delete();

        return response()->json(['ok' => true]);
    }

    private function addressData(Request $request): array
    {
        $data = $request->validate([
            'address_name' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:50'],
            'recipient_phone' => ['required', 'string', 'max:30'],
            'postal_code' => ['required', 'string', 'max:10'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'is_default' => ['boolean'],
        ], [
            'recipient_name.required' => '받는 사람 이름을 입력해 주세요.',
            'recipient_phone.required' => '연락처를 입력해 주세요.',
            'postal_code.required' => '우편번호를 입력해 주세요.',
            'address_line1.required' => '주소를 입력해 주세요.',
        ]);

        $data['address_name'] = $data['address_name'] ?: null;
        $data['address_line2'] = $data['address_line2'] ?: null;
        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        return $data;
    }

    private function paymentData(Request $request): array
    {
        $data = $request->validate([
            'method_type' => ['required', Rule::in(['CARD', 'ACCOUNT'])],
            'alias' => ['required', 'string', 'max:50'],
            'provider_name' => ['required', 'string', 'max:50'],
            'number_last4' => ['required', 'digits:4'],
            'is_default' => ['boolean'],
        ], [
            'method_type.required' => '결제 수단 종류를 선택해 주세요.',
            'alias.required' => '별칭을 입력해 주세요.',
            'provider_name.required' => '카드사 또는 은행을 입력해 주세요.',
            'number_last4.required' => '끝 4자리를 입력해 주세요.',
            'number_last4.digits' => '끝 4자리는 숫자 4자리입니다.',
        ]);

        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        return $data;
    }

    private function ownAddress(BuyerAddress $address): void
    {
        abort_unless($address->buyer_id === Auth::guard('buyer')->id(), 404);
    }

    private function ownPayment(BuyerPaymentMethod $method): void
    {
        abort_unless($method->buyer_id === Auth::guard('buyer')->id(), 404);
    }

    private function syncDefaultAddress(int $buyerId, BuyerAddress $address): void
    {
        if (! $address->is_default) {
            return;
        }

        BuyerAddress::query()
            ->where('buyer_id', $buyerId)
            ->where('id', '!=', $address->id)
            ->update(['is_default' => false]);
    }

    private function syncDefaultPayment(int $buyerId, BuyerPaymentMethod $method): void
    {
        if (! $method->is_default) {
            return;
        }

        BuyerPaymentMethod::query()
            ->where('buyer_id', $buyerId)
            ->where('id', '!=', $method->id)
            ->update(['is_default' => false]);
    }
}
