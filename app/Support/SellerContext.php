<?php

namespace App\Support;

use App\Models\Seller;
use App\Models\SellerMember;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;

class SellerContext
{
    public static function member(): SellerMember
    {
        /** @var SellerMember $member */
        $member = Auth::guard('seller')->user();

        return $member;
    }

    public static function seller(): Seller
    {
        return Seller::query()->findOrFail(self::member()->seller_id);
    }

    public static function store(): Store
    {
        $seller = self::seller();
        $store = $seller->stores()->first();

        if ($store) {
            return $store;
        }

        return $seller->stores()->create([
            'store_name' => $seller->seller_name.' 스토어',
            'operation_status_id' => CatalogStatus::id('STORE', 'OPEN', '영업중', 1),
        ]);
    }
}
