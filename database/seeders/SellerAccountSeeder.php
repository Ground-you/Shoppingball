<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Seller;
use App\Models\SellerMember;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SellerAccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->categories();
            $this->account();
        });
    }

    private function categories(): void
    {
        $names = ['패션의류', '뷰티', '식품', '주방용품', '생활용품', '가전디지털', '스포츠', '반려용품'];

        foreach ($names as $index => $name) {
            Category::query()->firstOrCreate(
                ['category_name' => $name, 'parent_category_id' => null],
                ['display_order' => $index + 1, 'is_active' => true],
            );
        }
    }

    private function account(): void
    {
        $email = 'seller@ccuppang.test';

        $seller = Seller::withTrashed()
            ->where('business_registration_number', '123-45-67890')
            ->first();

        if (! $seller) {
            $seller = Seller::query()->create([
                'seller_name' => '씨씨업팡',
                'manager_name' => '진헌',
                'contact_phone' => '010-0000-0000',
                'business_registration_number' => '123-45-67890',
                'business_address' => '서울특별시 송파구 송파대로 570',
                'representative_name' => '진헌',
                'settlement_bank_name' => '국민은행',
                'settlement_account_number_enc' => Crypt::encryptString('12345678901234'),
                'settlement_account_holder' => '씨씨업팡',
                'status' => 'APPROVED',
                'reviewed_at' => now(),
            ]);
        } elseif ($seller->trashed()) {
            $seller->restore();
            $seller->forceFill(['status' => 'APPROVED', 'reject_reason' => null])->save();
        }

        $member = SellerMember::withTrashed()->where('login_email', $email)->first();

        if (! $member) {
            SellerMember::query()->create([
                'seller_id' => $seller->id,
                'login_email' => $email,
                'password_hash' => 'Seller1!',
                'member_name' => $seller->manager_name ?: '진헌',
                'member_role' => 'OWNER',
                'status' => 'ACTIVE',
            ]);
        } elseif ($member->trashed()) {
            $member->restore();
            $member->forceFill([
                'seller_id' => $seller->id,
                'password_hash' => 'Seller1!',
                'member_role' => 'OWNER',
                'status' => 'ACTIVE',
            ])->save();
        }

        $store = Store::withTrashed()->where('seller_id', $seller->id)->first();

        if (! $store) {
            Store::query()->create([
                'seller_id' => $seller->id,
                'store_name' => 'ccuppang 스토어',
                'banner_image_url' => '/images/banner.jpg',
                'introduction' => '임시로 모든 판매 권한이 열린 스토어입니다.',
                'operation_status' => 'OPEN',
            ]);
        } elseif ($store->trashed()) {
            $store->restore();
            $store->forceFill(['operation_status' => 'OPEN'])->save();
        }
    }
}
