<?php

namespace Database\Seeders;

use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * 데이터베이스를 연결한 뒤 실행한다.
     * 상품 내용의 원본은 resources/data/catalog.php 다.
     */
    public function run(): void
    {
        $catalog = require resource_path('data/catalog.php');

        DB::table('home_section_product')->delete();
        Product::query()->delete();
        HomeSection::query()->delete();

        $products = collect($catalog['products'])->values()->map(function (array $item, int $index) {
            return Product::query()->create([
                'name' => $item['name'],
                'description' => $item['description'],
                'image' => $item['image'],
                'price' => $item['price'],
                'original_price' => $item['original_price'],
                'rating' => $item['rating'],
                'review_count' => $item['review_count'],
                'badge' => $item['badge'],
                'is_recommended' => $item['is_recommended'],
                'sort_order' => $index + 1,
            ]);
        });

        foreach ($catalog['sections'] as $index => $section) {
            $model = HomeSection::query()->create([
                'key' => $section['key'],
                'title' => $section['title'],
                'tone' => $section['tone'],
                'sort_order' => $index + 1,
            ]);

            $order = 1;
            foreach ($catalog['products'] as $productIndex => $item) {
                if (! in_array($section['key'], $item['sections'], true)) {
                    continue;
                }

                $model->products()->attach($products[$productIndex]->id, [
                    'sort_order' => $order,
                ]);
                $order++;
            }
        }
    }
}
