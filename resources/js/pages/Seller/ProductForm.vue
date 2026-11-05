<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import SellerShell from '../../Components/SellerShell.vue';
import { firstError } from '../../sellerError';

const props = defineProps({
    account: { type: Object, required: true },
    section: { type: String, default: 'products' },
    categories: { type: Array, default: () => [] },
    product: { type: Object, default: null },
});

const form = reactive({
    product_name: props.product?.product_name || '',
    description: props.product?.description || '',
    category_id: props.product?.category_id || '',
    brand_name: props.product?.brand_name || '',
    regular_price: props.product?.regular_price ?? '',
    sale_price: props.product?.sale_price ?? '',
    sale_status: props.product?.sale_status || 'ON_SALE',
});

const skus = ref(
    props.product?.skus?.length
        ? props.product.skus.map((sku) => ({ ...sku }))
        : [{ id: null, option_name: '기본', additional_price: 0, stock_quantity: 0, sold_out: false }],
);
const existing = ref((props.product?.images || []).map((image) => ({ ...image })));
const previews = ref([]);
let previewSeq = 0;
const mainImage = ref(
    props.product?.images?.find((image) => image.isMain)?.id?.toString()
        || (props.product?.images?.[0] ? String(props.product.images[0].id) : ''),
);
const message = ref('');
const submitting = ref(false);

const onFiles = (event) => {
    [...event.target.files].forEach((file) => {
        previews.value.push({
            key: `file-${previewSeq++}`,
            url: URL.createObjectURL(file),
            file,
        });
    });
    if (!mainImage.value && previews.value.length) {
        mainImage.value = previews.value[0].key;
    }
    event.target.value = '';
};

const removeExisting = (id) => {
    existing.value = existing.value.filter((image) => image.id !== id);
    if (mainImage.value === String(id)) {
        mainImage.value = existing.value[0] ? String(existing.value[0].id) : (previews.value[0]?.key || '');
    }
};

const removePreview = (key) => {
    const image = previews.value.find((item) => item.key === key);
    if (image) URL.revokeObjectURL(image.url);
    previews.value = previews.value.filter((item) => item.key !== key);
    if (mainImage.value === key) {
        mainImage.value = existing.value[0] ? String(existing.value[0].id) : (previews.value[0]?.key || '');
    }
};

const addSku = () => {
    skus.value.push({ id: null, option_name: '', additional_price: 0, stock_quantity: 0, sold_out: false });
};

const removeSku = (index) => {
    if (skus.value.length === 1) return;
    skus.value.splice(index, 1);
};

const cancelDiscount = () => {
    form.sale_price = form.regular_price;
};

const submit = async () => {
    message.value = '';
    submitting.value = true;
    const body = new FormData();
    body.append('product_name', form.product_name);
    body.append('description', form.description);
    body.append('category_id', form.category_id || '');
    body.append('brand_name', form.brand_name);
    body.append('regular_price', form.regular_price === '' ? '' : form.regular_price);
    body.append('sale_price', form.sale_price === '' ? '' : form.sale_price);
    body.append('sale_status', form.sale_status);
    body.append('keep_image_ids', existing.value.map((image) => image.id).join(','));
    body.append('skus', JSON.stringify(skus.value.map((sku) => ({
        id: sku.id,
        option_name: sku.option_name,
        additional_price: sku.additional_price === '' ? 0 : Number(sku.additional_price),
        stock_quantity: sku.stock_quantity === '' ? 0 : Number(sku.stock_quantity),
        sold_out: !!sku.sold_out,
    }))));

    const previewIndex = previews.value.findIndex((image) => image.key === mainImage.value);
    body.append('main_image', previewIndex >= 0 ? `new-${previewIndex}` : mainImage.value);
    previews.value.forEach((image) => body.append('images[]', image.file));

    try {
        const url = props.product ? `/seller/products/${props.product.id}` : '/seller/products';
        const { data } = await axios.post(url, body);
        window.location.href = data.redirect;
    } catch (error) {
        if (error.response?.status === 401) {
            window.location.href = '/seller/login';
            return;
        }
        message.value = firstError(error);
        submitting.value = false;
    }
};
</script>

<template>
    <SellerShell :account="account" :section="section">
        <form class="rounded-2xl border border-neutral-200 bg-white p-6" @submit.prevent="submit">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-[22px] font-bold">{{ product ? '상품 수정' : '상품 등록' }}</h1>
                <a v-if="product" :href="`/seller/products/${product.id}/preview`" class="text-sm font-bold text-brand">미리보기</a>
            </div>
            <p v-if="message" class="mt-3 text-sm text-brand">{{ message }}</p>

            <label class="mt-6 block text-sm">
                <span class="mb-1 block text-[12px] text-neutral-500">상품명</span>
                <input v-model="form.product_name" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
            </label>
            <label class="mt-4 block text-sm">
                <span class="mb-1 block text-[12px] text-neutral-500">상품 설명</span>
                <textarea v-model="form.description" rows="5" class="w-full rounded-lg border border-neutral-200 px-3 py-2 outline-none focus:border-brand" />
            </label>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">카테고리</span>
                    <select v-model="form.category_id" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                        <option value="">선택 안 함</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">브랜드</span>
                    <input v-model="form.brand_name" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
            </div>

            <div class="mt-6">
                <p class="text-sm font-bold">상품 이미지</p>
                <p class="mt-1 text-[12px] text-neutral-500">대표 이미지를 선택해 주세요. 최대 8장입니다.</p>
                <div class="mt-3 flex flex-wrap gap-3">
                    <label v-for="image in existing" :key="image.id" class="relative">
                        <img :src="image.url" alt="" class="h-24 w-24 rounded-xl object-cover">
                        <input v-model="mainImage" type="radio" class="mt-1" :value="String(image.id)">
                        <button type="button" class="absolute top-1 right-1 rounded-full bg-white px-1.5 text-[11px]" @click="removeExisting(image.id)">×</button>
                    </label>
                    <label v-for="image in previews" :key="image.key" class="relative">
                        <img :src="image.url" alt="" class="h-24 w-24 rounded-xl object-cover">
                        <input v-model="mainImage" type="radio" class="mt-1" :value="image.key">
                        <button type="button" class="absolute top-1 right-1 rounded-full bg-white px-1.5 text-[11px]" @click="removePreview(image.key)">×</button>
                    </label>
                    <label class="grid h-24 w-24 cursor-pointer place-items-center rounded-xl border border-dashed border-neutral-300 text-sm text-neutral-500">
                        추가
                        <input type="file" accept="image/*" multiple class="hidden" @change="onFiles">
                    </label>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">정상가</span>
                    <input v-model="form.regular_price" type="number" min="0" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">판매가</span>
                    <input v-model="form.sale_price" type="number" min="0" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
                <div class="flex items-end">
                    <button type="button" class="h-11 rounded-lg px-3 text-sm font-bold text-brand" @click="cancelDiscount">할인 취소</button>
                </div>
            </div>
            <p class="mt-2 text-[12px] text-neutral-400">판매가가 할인가입니다. 할인 시작일·종료일은 현재 상품 테이블에 없어 저장하지 않습니다.</p>

            <label class="mt-4 block text-sm">
                <span class="mb-1 block text-[12px] text-neutral-500">판매 상태</span>
                <select v-model="form.sale_status" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand sm:max-w-xs">
                    <option value="ON_SALE">판매중</option>
                    <option value="STOPPED">판매중지</option>
                    <option value="DRAFT">임시저장</option>
                </select>
            </label>

            <div class="mt-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold">옵션 · 가격 · 재고</h2>
                    <button type="button" class="text-sm font-bold text-brand" @click="addSku">옵션 추가</button>
                </div>
                <div v-for="(sku, index) in skus" :key="sku.id || index" class="mt-3 grid items-center gap-2 rounded-xl bg-neutral-50 p-3 sm:grid-cols-[minmax(0,1.4fr)_120px_100px_70px_52px]">
                    <input v-model="sku.option_name" placeholder="옵션 이름. 예: 화이트 / L" class="h-10 rounded-lg border border-neutral-200 bg-white px-3 text-sm outline-none focus:border-brand">
                    <input v-model="sku.additional_price" type="number" min="0" placeholder="추가금액" class="h-10 rounded-lg border border-neutral-200 bg-white px-3 text-sm outline-none focus:border-brand">
                    <input v-model="sku.stock_quantity" type="number" min="0" placeholder="재고" class="h-10 rounded-lg border border-neutral-200 bg-white px-3 text-sm outline-none focus:border-brand">
                    <label class="flex items-center gap-1 text-[12px] text-neutral-600">
                        <input v-model="sku.sold_out" type="checkbox"> 품절
                    </label>
                    <button type="button" class="text-sm text-neutral-500" @click="removeSku(index)">삭제</button>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="/seller/products" class="grid h-11 place-items-center rounded-lg px-4 text-sm text-neutral-500">목록</a>
                <button type="submit" class="h-11 rounded-lg bg-brand px-5 text-sm font-bold text-white disabled:opacity-60" :disabled="submitting">저장</button>
            </div>
        </form>
    </SellerShell>
</template>
