<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSku;
use App\Support\ProductHtml;
use App\Support\ProductView;
use App\Support\StatusLabel;
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
            ->with(['images', 'skus', 'category'])
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
                    'status' => $product->sale_status,
                    'statusName' => StatusLabel::name('sale', $product->sale_status),
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

    public function autosave(Request $request)
    {
        return $this->autosaveResponse($this->persist($request, null, true));
    }

    public function autosaveExisting(Request $request, Product $product)
    {
        $product = $this->own($product);
        abort_unless($product->sale_status === 'DRAFT', 422);

        return $this->autosaveResponse($this->persist($request, $product, true));
    }

    public function detailImage(Request $request)
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ], [
            'image.required' => '상세 설명에 넣을 이미지를 선택해 주세요.',
            'image.image' => '상세 설명에는 이미지 파일만 넣을 수 있습니다.',
            'image.max' => '이미지는 5MB 이하만 올릴 수 있습니다.',
        ]);

        return response()->json([
            'url' => PublicUpload::store($data['image'], 'products'),
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

        $selected = Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $data['ids']);
        $drafts = (clone $selected)->where('sale_status', 'DRAFT')->count();

        (clone $selected)
            ->where('sale_status', '!=', 'DRAFT')
            ->update([
                'sale_status' => $data['sale_status'],
                'approval_status' => 'APPROVED',
                'rejected_reason' => null,
            ]);

        return response()->json([
            'message' => $drafts
                ? '임시저장 상품은 상품 화면에서 저장해야 공개됩니다. 그 외 선택 상품의 상태를 변경했습니다.'
                : '선택한 상품의 판매 상태를 변경했습니다.',
        ]);
    }

    public function preview(Product $product)
    {
        $product = $this->own($product)->load(['images', 'skus', 'store.seller']);
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

    private function persist(Request $request, ?Product $product, bool $draft = false): Product
    {
        $this->decodeSkus($request);
        $creating = $product === null;
        $data = $draft ? $this->validateDraft($request) : $this->validateProduct($request);
        $prices = $this->listingPrices($data['skus']);
        $createdUrls = [];
        $removedUrls = [];

        try {
            $saved = DB::transaction(function () use ($request, $product, $data, $creating, $draft, $prices, &$createdUrls, &$removedUrls) {
                $store = SellerContext::store();
                $brandId = $this->brandId($data['brand_name'] ?? null);
                $payload = [
                    'store_id' => $store->id,
                    'category_id' => $data['category_id'] ?? null,
                    'brand_id' => $brandId,
                    'product_name' => $data['product_name'],
                    'description' => ProductHtml::clean($data['description'] ?? null),
                    'regular_price' => $prices['regular'],
                    'sale_price' => $prices['sale'],
                    'sale_status' => $draft ? 'DRAFT' : $data['sale_status'],
                    'approval_status' => 'APPROVED',
                    'rejected_reason' => null,
                ];

                if ($creating) {
                    $product = Product::query()->create($payload);
                } else {
                    $product->update($payload);
                }

                $this->syncSkus($product, $data['skus']);
                $this->syncImages($request, $product, $creating, $createdUrls, $removedUrls, ! $draft);

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

    private function validateProduct(Request $request): array
    {
        $request->merge([
            'category_id' => $request->filled('category_id') ? $request->input('category_id') : null,
            'brand_name' => trim((string) $request->input('brand_name', '')) ?: null,
            'description' => $request->input('description'),
        ]);

        $data = $request->validate([
            'product_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:200000'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'sale_status' => ['required', Rule::in(['ON_SALE', 'SOLD_OUT', 'STOPPED', 'DRAFT'])],
            'skus' => ['required', 'array', 'min:1', 'max:30'],
            'skus.*.id' => ['nullable', 'integer'],
            'skus.*.option_name' => ['required', 'string', 'max:200'],
            'skus.*.regular_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'skus.*.sale_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'skus.*.stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'skus.*.sold_out' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'keep_image_ids' => ['nullable', 'string', 'max:500'],
            'main_image' => ['nullable', 'string', 'max:40'],
        ], [
            'product_name.required' => '상품명을 입력해 주세요.',
            'skus.required' => '옵션을 하나 이상 등록해 주세요.',
            'skus.*.option_name.required' => '옵션 이름을 입력해 주세요.',
            'skus.*.regular_price.required' => '옵션 정상가를 입력해 주세요.',
            'skus.*.sale_price.required' => '옵션 판매가를 입력해 주세요.',
            'images.*.image' => '상품 이미지는 이미지 파일만 올릴 수 있습니다.',
            'images.*.max' => '이미지는 5MB 이하만 올릴 수 있습니다.',
        ]);

        foreach ($data['skus'] as $index => $row) {
            if ((float) $row['sale_price'] > (float) $row['regular_price']) {
                throw ValidationException::withMessages([
                    "skus.$index.sale_price" => '옵션 판매가는 그 옵션의 정상가보다 클 수 없습니다.',
                ]);
            }
        }

        return $data;
    }

    private function validateDraft(Request $request): array
    {
        $skus = $request->input('skus');
        if (! is_array($skus) || $skus === []) {
            $skus = [[
                'option_name' => '기본',
                'regular_price' => 0,
                'sale_price' => 0,
                'stock_quantity' => 0,
                'sold_out' => false,
            ]];
        }

        foreach ($skus as $index => $row) {
            $skus[$index]['option_name'] = trim((string) ($row['option_name'] ?? '')) ?: '기본';
            $skus[$index]['regular_price'] = is_numeric($row['regular_price'] ?? null) ? $row['regular_price'] : 0;
            $skus[$index]['sale_price'] = is_numeric($row['sale_price'] ?? null) ? $row['sale_price'] : 0;
            $skus[$index]['stock_quantity'] = is_numeric($row['stock_quantity'] ?? null) ? (int) $row['stock_quantity'] : 0;
            if ((float) $skus[$index]['sale_price'] > (float) $skus[$index]['regular_price']) {
                $skus[$index]['sale_price'] = $skus[$index]['regular_price'];
            }
        }

        $request->merge([
            'product_name' => trim((string) $request->input('product_name', '')) ?: '제목 없음',
            'category_id' => $request->filled('category_id') ? $request->input('category_id') : null,
            'brand_name' => trim((string) $request->input('brand_name', '')) ?: null,
            'sale_status' => 'DRAFT',
            'skus' => $skus,
        ]);

        return $request->validate([
            'product_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:200000'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'sale_status' => ['required', Rule::in(['DRAFT'])],
            'skus' => ['required', 'array', 'min:1', 'max:30'],
            'skus.*.id' => ['nullable', 'integer'],
            'skus.*.option_name' => ['required', 'string', 'max:200'],
            'skus.*.regular_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'skus.*.sale_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'skus.*.stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'skus.*.sold_out' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'keep_image_ids' => ['nullable', 'string', 'max:500'],
            'main_image' => ['nullable', 'string', 'max:40'],
        ]);
    }

    private function syncSkus(Product $product, array $rows): void
    {
        $kept = [];

        foreach (array_values($rows) as $index => $row) {
            $payload = [
                'option_name' => $row['option_name'],
                'additional_price' => 0,
                'regular_price' => $row['regular_price'],
                'sale_price' => $row['sale_price'],
                'stock_quantity' => $row['stock_quantity'],
                'status' => ! empty($row['sold_out']) ? 'SOLD_OUT' : 'ON_SALE',
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

    private function syncImages(Request $request, Product $product, bool $creating, array &$createdUrls, array &$removedUrls, bool $requireImages = true): void
    {
        $keepIds = collect(explode(',', (string) $request->input('keep_image_ids', '')))
            ->filter(fn ($id) => $id !== '')
            ->map(fn ($id) => (int) $id)
            ->values();

        $newFiles = $request->file('images', []) ?? [];
        if (! is_array($newFiles)) {
            $newFiles = [$newFiles];
        }

        if ($requireImages && $keepIds->count() + count($newFiles) < 1) {
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
            ['status' => 'ACTIVE'],
        );

        return $brand->id;
    }

    private function listingPrices(array $rows): array
    {
        $cheapest = collect($rows)->sortBy(fn (array $row) => (float) $row['sale_price'])->first();

        return [
            'regular' => $cheapest['regular_price'] ?? 0,
            'sale' => $cheapest['sale_price'] ?? 0,
        ];
    }

    private function autosaveResponse(Product $product)
    {
        $product->load(['images', 'skus']);
        $main = $product->images->firstWhere('is_main', true) ?? $product->images->first();

        return response()->json([
            'id' => $product->id,
            'savedAt' => now()->format('H:i'),
            'mainImageId' => $main?->id,
            'skus' => $product->skus->sortBy('sort_order')->values()->map(fn (ProductSku $sku) => [
                'id' => $sku->id,
            ])->all(),
            'images' => $product->images->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'url' => $image->image_url,
                'isMain' => (bool) $image->is_main,
            ])->values()->all(),
        ]);
    }

    private function storeCard(): array
    {
        $store = SellerContext::store();
        $policy = $store->policies()->orderByDesc('version_no')->first();

        return [
            'name' => $store->store_name,
            'logo' => $store->logo_url,
            'banner' => $store->banner_image_url,
            'shippingNotice' => $policy?->shipping_policy_notice,
            'exchangeNotice' => $policy?->exchange_return_policy_notice,
        ];
    }

    private function own(Product $product): Product
    {
        abort_unless($product->store_id === SellerContext::store()->id, 404);

        return $product->load(['images', 'skus', 'brand', 'category']);
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
            'store' => $this->storeCard(),
            'product' => $product ? [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'description' => $product->description ?? '',
                'category_id' => $product->category_id,
                'brand_name' => $product->brand?->brand_name ?? '',
                'regular_price' => ProductView::money($product->regular_price),
                'sale_price' => ProductView::money($product->sale_price),
                'sale_status' => $product->sale_status ?: 'ON_SALE',
                'images' => $product->images->map(fn (ProductImage $image) => [
                    'id' => $image->id,
                    'url' => $image->image_url,
                    'isMain' => (bool) $image->is_main,
                ])->values()->all(),
                'skus' => $product->skus->map(fn (ProductSku $sku) => [
                    'id' => $sku->id,
                    'option_name' => $sku->option_name,
                    'regular_price' => ProductView::skuRegular($product, $sku),
                    'sale_price' => ProductView::skuSale($product, $sku),
                    'stock_quantity' => $sku->stock_quantity,
                    'sold_out' => $sku->status === 'SOLD_OUT',
                ])->values()->all(),
            ] : null,
        ];
    }
}
