<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ProductView;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    // 홈은 판매중인 판매자 상품만 보여 준다. 배너 이미지는 resources/data/catalog.php 에 있다.
    public function index()
    {
        $catalog = require resource_path('data/catalog.php');
        $sections = collect();
        $sellerSection = $this->sellerSection();

        if ($sellerSection) {
            $sections->push($sellerSection);
        }

        $buyer = Auth::guard('buyer')->user();

        return view('app', [
            'page' => [
                'component' => 'Home',
                'buyer' => $buyer ? [
                    'loginId' => $buyer->login_id,
                    'name' => $buyer->buyer_name,
                    'nickname' => $buyer->nickname,
                ] : null,
                'shortcuts' => ['오늘도착', '신선마켓', '골드딜', '기획전', '해외직구', '브랜드특가'],
                'categories' => ['패션의류', '뷰티', '식품', '주방용품', '생활용품', '가전디지털', '스포츠', '완구', '반려용품', '자동차용품'],
                'banner' => $catalog['banner'],
                'sideCards' => [
                    ['title' => '로그인하고 혜택 받기', 'body' => '가입 즉시 쓸 수 있는 할인 쿠폰을 보여 드립니다.', 'action' => '로그인'],
                    ['title' => '오늘도착', 'body' => '밤 12시 전 주문하면 내일 도착하는 상품만 모았습니다.', 'action' => '상품 보기'],
                    ['title' => '골드딜', 'body' => '오늘 23:59까지 수량이 한정된 딜입니다.', 'action' => '남은 시간 02:14:33'],
                ],
                'sections' => $sections->values(),
            ],
        ]);
    }

    private function sellerSection(): ?array
    {
        $products = Product::query()
            ->with(['images', 'skus'])
            ->where('sale_status', 'ON_SALE')
            ->where('approval_status', 'APPROVED')
            ->whereHas('store', function ($query) {
                $query->where('operation_status', 'OPEN')->whereHas('seller');
            })
            ->latest()
            ->limit(12)
            ->get();

        if ($products->isEmpty()) {
            return null;
        }

        return [
            'id' => 'seller-products',
            'title' => '상품목록',
            'tone' => 'plain',
            'products' => $products->map(fn (Product $product) => ProductView::card($product))->values()->all(),
        ];
    }

}
