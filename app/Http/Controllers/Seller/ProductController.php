<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSku;
use App\Support\CatalogStatus;
use App\Support\ProductView;
use App\Support\PublicUpload;
use App\Support\SellerContext;
use App\Support\SellerPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        $store = SellerContext::store();
        $products = Product::query()
            ->with(['images', 'skus', 'saleStatus', 'category'])
            ->where('store_id', $store->id)
            ->latest()
            ->get()
            ->map(function (Product $product) {
                $image = $product->images->firstWhere('is_main', true) ?? $product->images->first();

                return [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'image' => $image?->image_url,
                    'regularPrice' => ProductView::money($product->regular_price),
                    'salePrice' => ProductView::money($product->sale_price),
                    'status' => $product->saleStatus?->status_code,
                    'statusName' => $product->saleStatus?->status_name,
                    'stock' => $product->skus->sum('stock_quantity'),
                    'category' => $product->category?->category_name,
                ];
            })
            ->values()
            ->all();

        return SellerPage::render('SellerProducts', [
            'section' => 'products',
            'products' => $products,
        ]);
    }

    public function create()
    {
        return SellerPage::render('SellerProductForm', $this->formProps(null));
    }

    public function store(Request $request)
    {
        $product = $this->persist($request, null);

        return response()->json([
            'redirect' => route('seller.products.index'),
            'id' => $product->id,
        ]);
    }

    public function edit(Product $product)
    {
        return SellerPage::render('SellerProductForm', $this->formProps($this->own($product)));
    }

    public function update(Request $request, Product $product)
    {
        $this->persist($request, $this->own($product));

        return response()->json([
            'message' => '상품을 저장했습니다.',
            'redirect' => route('seller.products.index'),
        ]);
    }

    public function destroy(Product $product)
    {
        $this->own($product)->delete();

        return response()->json(['message' => '상품을 삭제했습니다.']);
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'sale_status' => ['required', Rule::in(['ON_SALE', 'STOPPED'])],
        ], [
            'ids.required' => '상품을 선택해 주세요.',
            'sale_status.required' => '변경할 판매 상태를 선택해 주세요.',
        ]);

        $store = SellerContext::store();
        $statusId = $this->saleStatusId($data['sale_status']);

        Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $data['ids'])
            ->update([
                'sale_status_id' => $statusId,
                'approval_status_id' => CatalogStatus::id('PRODUCT_APPROVAL', 'APPROVED', '승인', 1),
                'rejected_reason' => null,
            ]);

        return response()->json(['message' => '선택한 상품의 판매 상태를 변경했습니다.']);
    }

    public function preview(Product $product)
    {
        $product = $this->own($product)->load(['images', 'skus.status', 'saleStatus', 'store.seller']);
        $store = $product->store;
        $policy = $store->policies()->orderByDesc('version_no')->first();
        $buyer = Auth::guard('buyer')->user();

        return view('app', [
            'page' => [
                'component' => 'ProductDetail',
                'preview' => true,
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

    private function persist(Request $request, ?Product $product): Product
    {
        $this->decodeSkus($request);
        $creating = $product === null;
        $data = $this->validateProduct($request, $creating);
        $createdUrls = [];
        $removedUrls = [];

        try {
            $saved = DB::transaction(function () use ($request, $product, $data, $creating, &$createdUrls, &$removedUrls) {
                $store = SellerContext::store();
                $brandId = $this->brandId($data['brand_name'] ?? null);
                $payload = [
                    'store_id' => $store->id,
                    'category_id' => $data['category_id'] ?? null,
                    'brand_id' => $brandId,
                    'product_name' => $data['product_name'],
                    'description' => $data['description'] ?? null,
                    'regular_price' => $data['regular_price'],
                    'sale_price' => $data['sale_price'],
                    'sale_status_id' => $this->saleStatusId($data['sale_status']),
                    'approval_status_id' => CatalogStatus::id('PRODUCT_APPROVAL', 'APPROVED', '승인', 1),
                    'rejected_reason' => null,
                ];

                if ($creating) {
                    $product = Product::query()->create($payload);
                } else {
                    $product->update($payload);
                }

                $this->syncSkus($product, $data['skus']);
                $this->syncImages($request, $product, $creating, $createdUrls, $removedUrls);

                return $product;
            });
        } catch (\Throwable $exception) {
            foreach ($createdUrls as $url) {
                PublicUpload::delete($url);
            }

            throw $exception;
        }

        foreach ($removedUrls as $url) {
            PublicUpload::delete($url);
        }

        return $saved;
    }

    private function decodeSkus(Request $request): void
    {
        if (! is_string($request->input('skus'))) {
            return;
        }

        $decoded = json_decode($request->input('skus'), true);
        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'skus' => '옵션 정보를 확인해 주세요.',
            ]);
        }

        $request->merge(['skus' => $decoded]);
    }

    private function validateProduct(Request $request, bool $creating): array
    {
        $request->merge([
            'category_id' => $request->filled('category_id') ? $request->input('category_id') : null,
            'brand_name' => trim((string) $request->input('brand_name', '')) ?: null,
            'description' => trim((string) $request->input('description', '')) ?: null,
        ]);

        return $request->validate([
            'product_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'regular_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'sale_price' => ['required', 'numeric', 'min:0', 'lte:regular_price'],
            'sale_status' => ['required', Rule::in(['ON_SALE', 'STOPPED', 'DRAFT'])],
            'skus' => ['required', 'array', 'min:1', 'max:30'],
            'skus.*.id' => ['nullable', 'integer'],
            'skus.*.option_name' => ['required', 'string', 'max:200'],
            'skus.*.additional_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'skus.*.stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'skus.*.sold_out' => ['nullable', 'boolean'],
            'images' => [$creating ? 'required' : 'nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'keep_image_ids' => ['nullable', 'string', 'max:500'],
            'main_image' => ['nullable', 'string', 'max:40'],
        ], [
            'product_name.required' => '상품명을 입력해 주세요.',
            'regular_price.required' => '정상가를 입력해 주세요.',
            'sale_price.required' => '판매가를 입력해 주세요.',
            'sale_price.lte' => '판매가는 정상가보다 클 수 없습니다.',
            'skus.required' => '옵션을 하나 이상 등록해 주세요.',
            'skus.*.option_name.required' => '옵션 이름을 입력해 주세요.',
            'images.required' => '상품 이미지를 1장 이상 등록해 주세요.',
            'images.*.image' => '상품 이미지는 이미지 파일만 올릴 수 있습니다.',
            'images.*.max' => '이미지는 5MB 이하만 올릴 수 있습니다.',
        ]);
    }

    private function syncSkus(Product $product, array $rows): void
    {
        $active = CatalogStatus::id('SKU', 'ACTIVE', '판매중', 1);
        $soldOut = CatalogStatus::id('SKU', 'SOLD_OUT', '품절', 2);
        $kept = [];

        foreach (array_values($rows) as $index => $row) {
            $payload = [
                'option_name' => $row['option_name'],
                'additional_price' => $row['additional_price'],
                'stock_quantity' => $row['stock_quantity'],
                'status_id' => ! empty($row['sold_out']) ? $soldOut : $active,
                'sort_order' => $index,
            ];

            if (! empty($row['id'])) {
                $sku = ProductSku::query()
                    ->where('product_id', $product->id)
                    ->whereKey($row['id'])
                    ->firstOrFail();
                $sku->update($payload);
            } else {
                $sku = $product->skus()->create($payload + [
                    'sku_code' => $this->nextSkuCode($product),
                ]);
            }

            $kept[] = $sku->id;
        }

        ProductSku::query()
            ->where('product_id', $product->id)
            ->whereNotIn('id', $kept)
            ->delete();
    }

    private function syncImages(Request $request, Product $product, bool $creating, array &$createdUrls, array &$removedUrls): void
    {
        $keepIds = collect(explode(',', (string) $request->input('keep_image_ids', '')))
            ->filter(fn ($id) => $id !== '')
            ->map(fn ($id) => (int) $id)
            ->values();

        $newFiles = $request->file('images', []) ?? [];
        if (! is_array($newFiles)) {
            $newFiles = [$newFiles];
        }

        if ($keepIds->count() + count($newFiles) < 1) {
            throw ValidationException::withMessages([
                'images' => '상품 이미지를 1장 이상 등록해 주세요.',
            ]);
        }

        if ($keepIds->count() + count($newFiles) > 8) {
            throw ValidationException::withMessages([
                'images' => '이미지는 8장까지 등록할 수 있습니다.',
            ]);
        }

        if (! $creating) {
            $removed = $product->images()->whereNotIn('id', $keepIds->all() ?: [0])->get();
            foreach ($removed as $image) {
                $removedUrls[] = $image->image_url;
                $image->delete();
            }
        }

        $created = [];
        foreach ($newFiles as $file) {
            $url = PublicUpload::store($file, 'products');
            $createdUrls[] = $url;
            $created[] = $product->images()->create([
                'image_url' => $url,
                'is_main' => false,
                'sort_order' => 0,
            ]);
        }

        $images = $product->images()->get();
        $mainKey = (string) $request->input('main_image', '');
        $main = null;

        if (str_starts_with($mainKey, 'new-')) {
            $main = $created[(int) substr($mainKey, 4)] ?? null;
        } elseif ($mainKey !== '') {
            $main = $images->firstWhere('id', (int) $mainKey);
        }

        $main ??= $images->firstWhere('is_main', true) ?? $images->first();

        foreach ($images->values() as $index => $image) {
            $image->update([
                'is_main' => $main && $image->id === $main->id,
                'sort_order' => $index,
            ]);
        }
    }

    private function nextSkuCode(Product $product): string
    {
        do {
            $code = 'CCU-'.$product->id.'-'.Str::upper(Str::random(4));
        } while (ProductSku::withTrashed()->where('sku_code', $code)->exists());

        return $code;
    }

    private function brandId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        $brand = Brand::query()->firstOrCreate(
            ['brand_name' => $name],
            ['status_id' => CatalogStatus::id('BRAND', 'ACTIVE', '사용', 1)],
        );

        return $brand->id;
    }

    private function saleStatusId(string $code): int
    {
        return match ($code) {
            'STOPPED' => CatalogStatus::id('PRODUCT_SALE', 'STOPPED', '판매중지', 2),
            'DRAFT' => CatalogStatus::id('PRODUCT_SALE', 'DRAFT', '임시저장', 3),
            default => CatalogStatus::id('PRODUCT_SALE', 'ON_SALE', '판매중', 1),
        };
    }

    private function own(Product $product): Product
    {
        abort_unless($product->store_id === SellerContext::store()->id, 404);

        return $product->load(['images', 'skus.status', 'saleStatus', 'brand', 'category']);
    }

    private function formProps(?Product $product): array
    {
        return [
            'section' => 'products',
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get(['id', 'category_name'])
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->category_name,
                ])
                ->values()
                ->all(),
            'product' => $product ? [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'description' => $product->description ?? '',
                'category_id' => $product->category_id,
                'brand_name' => $product->brand?->brand_name ?? '',
                'regular_price' => ProductView::money($product->regular_price),
                'sale_price' => ProductView::money($product->sale_price),
                'sale_status' => $product->saleStatus?->status_code ?? 'DRAFT',
                'images' => $product->images->map(fn (ProductImage $image) => [
                    'id' => $image->id,
                    'url' => $image->image_url,
                    'isMain' => (bool) $image->is_main,
                ])->values()->all(),
                'skus' => $product->skus->map(fn (ProductSku $sku) => [
                    'id' => $sku->id,
                    'option_name' => $sku->option_name,
                    'additional_price' => ProductView::money($sku->additional_price),
                    'stock_quantity' => $sku->stock_quantity,
                    'sold_out' => $sku->status?->status_code === 'SOLD_OUT',
                ])->values()->all(),
            ] : null,
        ];
    }
}
