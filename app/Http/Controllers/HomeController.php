<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\CatalogStatus;
use App\Support\ProductView;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    // 홈 인덱스. 상품은 resources/data/catalog.php, 사진은 public/images 에서 읽는다.
    public function index()
    {
        $catalog = require resource_path('data/catalog.php');
        $products = collect($catalog['products'])->values();

        $sections = collect($catalog['sections'])->map(function (array $section) use ($products) {
            $items = $products
                ->filter(fn (array $product) => in_array($section['key'], $product['sections'], true))
                ->values();

            return [
                'id' => $section['key'],
                'title' => $section['title'],
                'tone' => $section['tone'],
                'products' => $this->presentMany($items),
            ];
        });

        $recommended = $products
            ->filter(fn (array $product) => $product['is_recommended'])
            ->values();

        if ($recommended->isNotEmpty()) {
            $sections->prepend([
                'id' => 'recommended',
                'title' => '추천 상품',
                'tone' => 'plain',
                'products' => $this->presentMany($recommended),
            ]);
        }

        $sellerSection = $this->sellerSection();
        if ($sellerSection) {
            $sections->prepend($sellerSection);
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
        $onSale = CatalogStatus::findId('PRODUCT_SALE', 'ON_SALE');
        $approved = CatalogStatus::findId('PRODUCT_APPROVAL', 'APPROVED');
        $open = CatalogStatus::findId('STORE', 'OPEN');

        if (! $onSale || ! $approved || ! $open) {
            return null;
        }

        $products = Product::query()
            ->with('images')
            ->where('sale_status_id', $onSale)
            ->where('approval_status_id', $approved)
            ->whereHas('store', function ($query) use ($open) {
                $query->where('operation_status_id', $open)->whereHas('seller');
            })
            ->latest()
            ->limit(12)
            ->get();

        if ($products->isEmpty()) {
            return null;
        }

        return [
            'id' => 'seller-products',
            'title' => '판매자 상품',
            'tone' => 'plain',
            'products' => $products->map(fn (Product $product) => ProductView::card($product))->values()->all(),
        ];
    }

    private function presentMany($products): array
    {
        return $products->map(function (array $product) {
            $original = (int) $product['original_price'];
            $price = (int) $product['price'];
            $discount = $original > 0 ? (int) round((1 - $price / $original) * 100) : 0;

            return [
                'id' => $product['image'],
                'name' => $product['name'],
                'description' => $product['description'],
                'image' => $product['image'],
                'price' => $price,
                'originalPrice' => $original,
                'discount' => $discount,
                'rating' => $product['rating'],
                'reviews' => $product['review_count'],
                'badge' => $product['badge'],
            ];
        })->values()->all();
    }
}
