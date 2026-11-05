<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Support\CatalogStatus;
use App\Support\PublicUpload;
use App\Support\SellerContext;
use App\Support\SellerPage;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function edit()
    {
        $store = SellerContext::store()->load('operationStatus');
        $policy = Policy::query()
            ->where('store_id', $store->id)
            ->orderByDesc('version_no')
            ->first();

        return SellerPage::render('SellerStore', [
            'section' => 'store',
            'store' => [
                'name' => $store->store_name,
                'logo' => $store->logo_url,
                'banner' => $store->banner_image_url,
                'introduction' => $store->introduction ?? '',
                'operationStatus' => $store->operationStatus?->status_code === 'PAUSED' ? 'PAUSED' : 'OPEN',
                'shippingNotice' => $policy?->shipping_policy_notice ?? '',
                'exchangeNotice' => $policy?->exchange_return_policy_notice ?? '',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'min:2', 'max:100'],
            'introduction' => ['nullable', 'string', 'max:2000'],
            'operation_status' => ['required', 'in:OPEN,PAUSED'],
            'shipping_policy_notice' => ['nullable', 'string', 'max:2000'],
            'exchange_return_policy_notice' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_banner' => ['nullable', 'boolean'],
        ], [
            'store_name.required' => '상점 이름을 입력해 주세요.',
            'logo.image' => '로고는 이미지 파일만 올릴 수 있습니다.',
            'banner.image' => '배너는 이미지 파일만 올릴 수 있습니다.',
            'logo.max' => '이미지는 5MB 이하만 올릴 수 있습니다.',
            'banner.max' => '이미지는 5MB 이하만 올릴 수 있습니다.',
        ]);

        $store = SellerContext::store();
        $store->store_name = $data['store_name'];
        $store->introduction = $data['introduction'] ?: null;
        $store->operation_status_id = $data['operation_status'] === 'PAUSED'
            ? CatalogStatus::id('STORE', 'PAUSED', '휴업', 2)
            : CatalogStatus::id('STORE', 'OPEN', '영업중', 1);

        if ($request->hasFile('logo')) {
            PublicUpload::delete($store->logo_url);
            $store->logo_url = PublicUpload::store($request->file('logo'), 'stores');
        } elseif ($request->boolean('remove_logo')) {
            PublicUpload::delete($store->logo_url);
            $store->logo_url = null;
        }

        if ($request->hasFile('banner')) {
            PublicUpload::delete($store->banner_image_url);
            $store->banner_image_url = PublicUpload::store($request->file('banner'), 'stores');
        } elseif ($request->boolean('remove_banner')) {
            PublicUpload::delete($store->banner_image_url);
            $store->banner_image_url = null;
        }

        $store->save();
        $this->savePolicy($store->id, $data['shipping_policy_notice'] ?? null, $data['exchange_return_policy_notice'] ?? null);

        return response()->json(['message' => '상점 정보를 저장했습니다.']);
    }

    private function savePolicy(int $storeId, ?string $shipping, ?string $exchange): void
    {
        $shipping = $shipping !== null && trim($shipping) !== '' ? trim($shipping) : null;
        $exchange = $exchange !== null && trim($exchange) !== '' ? trim($exchange) : null;

        $latest = Policy::query()->where('store_id', $storeId)->orderByDesc('version_no')->first();

        if ($latest && $latest->shipping_policy_notice === $shipping && $latest->exchange_return_policy_notice === $exchange) {
            return;
        }

        Policy::query()->create([
            'store_id' => $storeId,
            'version_no' => ($latest->version_no ?? 0) + 1,
            'shipping_policy_notice' => $shipping,
            'exchange_return_policy_notice' => $exchange,
            'effective_from' => now(),
            'registered_by_seller_member_id' => SellerContext::member()->id,
        ]);
    }
}
