<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ProductView;
use Illuminate\Support\Facades\Auth;

class StorefrontProductController extends Controller
{
    public function show(string $product)
    {
        $product = Product::withTrashed()->find($product);

        if (! $product || $product->trashed()) {
            return $this->deleted();
        }

        $product->load(['images', 'skus', 'store.seller']);
        $store = $product->store;

        abort_unless(
            $store
            && $store->seller
            && $product->sale_status === 'ON_SALE'
            && $product->approval_status === 'APPROVED'
            && $store->operation_status === 'OPEN',
            404
        );

        $policy = $store->policies()->orderByDesc('version_no')->first();
        $buyer = Auth::guard('buyer')->user();

        return view('app', [
            'page' => [
                'component' => 'ProductDetail',
                'preview' => false,
                'shortcuts' => ['오늘도착', '신선마켓', '골드딜', '기획전', '해외직구', '브랜드특가'],
                'buyer' => $buyer ? [
                    'loginId' => $buyer->login_id,
                    'name' => $buyer->buyer_name,
                    'nickname' => $buyer->nickname,
                ] : null,
                'product' => ProductView::detail($product),
                'store' => [
                    'name' => $store->store_name,
                    'logo' => $store->logo_url,
                    'banner' => $store->banner_image_url,
                    'introduction' => $store->introduction,
                    'shippingNotice' => $policy?->shipping_policy_notice,
                    'exchangeNotice' => $policy?->exchange_return_policy_notice,
                ],
            ],
        ]);
    }

    private function deleted()
    {
        $buyer = Auth::guard('buyer')->user();

        return view('app', [
            'page' => [
                'component' => 'ProductDetail',
                'deleted' => true,
                'preview' => false,
                'shortcuts' => ['오늘도착', '신선마켓', '골드딜', '기획전', '해외직구', '브랜드특가'],
                'buyer' => $buyer ? [
                    'loginId' => $buyer->login_id,
                    'name' => $buyer->buyer_name,
                    'nickname' => $buyer->nickname,
                ] : null,
            ],
        ]);
    }
}
