<?php

namespace App\Support;

use App\Models\Seller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class SellerPage
{
    public static function render(string $component, array $props = []): View
    {
        $member = Auth::guard('seller')->user();
        $seller = $member ? Seller::query()->find($member->seller_id) : null;
        $store = $seller?->stores()->first();

        return view('app', [
            'page' => array_merge([
                'component' => $component,
                'account' => $member ? [
                    'name' => $member->member_name,
                    'email' => $member->login_email,
                    'sellerName' => $seller?->seller_name,
                    'storeName' => $store?->store_name,
                ] : null,
            ], $props),
        ]);
    }
}
