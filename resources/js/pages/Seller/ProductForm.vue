<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import SellerShell from '../../components/SellerShell.vue';
import DetailEditor from '../../components/DetailEditor.vue';
import ProductDetail from '../ProductDetail.vue';
import { firstError } from '../../sellerError';

const props = defineProps({
    account: { type: Object, required: true },
    section: { type: String, default: 'products' },
    categories: { type: Array, default: () => [] },
    product: { type: Object, default: null },
    store: { type: Object, default: () => ({}) },
});

const form = reactive({
    product_name: props.product?.product_name || '',
    description: props.product?.description || '',
    category_id: props.product?.category_id || '',
    brand_name: props.product?.brand_name || '',
    sale_status: props.product?.sale_status || 'ON_SALE',
});

const blankSku = () => ({ id: null, option_name: '기본', regular_price: 0, sale_price: 0, stock_quantity: 0, sold_out: false });
const skus = ref(
    props.product?.skus?.length
        ? props.product.skus.map((sku) => ({ ...sku }))
        : [blankSku()],
);
const existing = ref((props.product?.images || []).map((image) => ({ ...image })));
const previews = ref([]);
let previewSeq = 0;
const mainImage = ref(
    props.product?.images?.find((image) => image.isMain)?.id?.toString()
        || (props.product?.images?.[0] ? String(props.product.images[0].id) : ''),
);
const message = ref('');
const autosaveNote = ref('');
const submitting = ref(false);
const previewOpen = ref(false);
const leaveOpen = ref(false);
const leaveHref = ref('/seller/products');
let leaving = false;
const draftId = ref(props.product?.sale_status === 'DRAFT' ? props.product.id : null);
const canAutosave = !props.product || props.product.sale_status === 'DRAFT';
let autosaveTimer = null;
let autosaving = false;
let quiet = false;

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
    skus.value.push({ id: null, option_name: '', regular_price: 0, sale_price: 0, stock_quantity: 0, sold_out: false });
};

const removeSku = (index) => {
    if (skus.value.length === 1) return;
    skus.value.splice(index, 1);
};

const clearOptionDiscount = (sku) => {
    sku.sale_price = sku.regular_price;
};

const buildBody = () => {
    const body = new FormData();
    body.append('product_name', form.product_name);
    body.append('description', form.description);
    body.append('category_id', form.category_id || '');
    body.append('brand_name', form.brand_name);
    body.append('sale_status', form.sale_status);
    body.append('keep_image_ids', existing.value.map((image) => image.id).join(','));
    body.append('skus', JSON.stringify(skus.value.map((sku) => ({
        id: sku.id,
        option_name: sku.option_name,
        regular_price: sku.regular_price === '' ? 0 : Number(sku.regular_price),
        sale_price: sku.sale_price === '' ? 0 : Number(sku.sale_price),
        stock_quantity: sku.stock_quantity === '' ? 0 : Number(sku.stock_quantity),
        sold_out: !!sku.sold_out,
    }))));

    const previewIndex = previews.value.findIndex((image) => image.key === mainImage.value);
    body.append('main_image', previewIndex >= 0 ? `new-${previewIndex}` : mainImage.value);
    previews.value.forEach((image) => body.append('images[]', image.file));

    return body;
};

const autosave = async () => {
    if (!canAutosave || submitting.value || autosaving) {
        return;
    }

    autosaving = true;
    try {
        const url = draftId.value ? `/seller/products/${draftId.value}/autosave` : '/seller/products/autosave';
        const { data } = await axios.post(url, buildBody());
        draftId.value = data.id;
        quiet = true;
        (data.skus || []).forEach((saved, index) => {
            if (skus.value[index]) {
                skus.value[index].id = saved.id;
            }
        });
        existing.value = data.images || [];
        previews.value.forEach((image) => URL.revokeObjectURL(image.url));
        previews.value = [];
        if (data.mainImageId) {
            mainImage.value = String(data.mainImageId);
        }
        await nextTick();
        quiet = false;
        autosaveNote.value = `${data.savedAt}에 임시저장했습니다. 구매자에게는 보이지 않고, 저장을 눌러야 선택한 판매 상태로 공개됩니다.`;
    } catch (error) {
        autosaveNote.value = firstError(error, '임시저장에 실패했습니다.');
    } finally {
        autosaving = false;
    }
};

const scheduleAutosave = () => {
    if (!canAutosave || quiet) {
        return;
    }

    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(autosave, 2000);
};

watch(form, scheduleAutosave, { deep: true });
watch(skus, scheduleAutosave, { deep: true });
watch(existing, scheduleAutosave, { deep: true });
watch(previews, scheduleAutosave, { deep: true });

const fingerprint = () => JSON.stringify({
    product_name: form.product_name,
    description: form.description,
    category_id: form.category_id,
    brand_name: form.brand_name,
    sale_status: form.sale_status,
    skus: skus.value.map((sku) => ({
        option_name: sku.option_name,
        regular_price: Number(sku.regular_price) || 0,
        sale_price: Number(sku.sale_price) || 0,
        stock_quantity: Number(sku.stock_quantity) || 0,
        sold_out: !!sku.sold_out,
    })),
    images: existing.value.map((image) => image.id),
    pending: previews.value.map((image) => image.key),
    mainImage: String(mainImage.value || ''),
});
const savedFingerprint = ref('');
const dirty = () => fingerprint() !== savedFingerprint.value;

const onBeforeUnload = (event) => {
    if (leaving || submitting.value || !dirty()) {
        return;
    }
    event.preventDefault();
    event.returnValue = '';
};

const onDocumentClick = (event) => {
    const link = event.target.closest?.('a[href]');
    if (!link || link.target === '_blank' || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
    }
    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || submitting.value || !dirty()) {
        return;
    }
    event.preventDefault();
    event.stopPropagation();
    leaveHref.value = `${url.pathname}${url.search}`;
    leaveOpen.value = true;
};

const confirmLeave = () => {
    leaving = true;
    window.location.href = leaveHref.value;
};

const optionDiscount = (sku) => {
    if (sku.regular_price === '' || sku.sale_price === '') {
        return null;
    }
    const regular = Number(sku.regular_price);
    const sale = Number(sku.sale_price);
    if (!Number.isFinite(regular) || !Number.isFinite(sale) || regular <= 0 || sale >= regular) {
        return null;
    }
    return {
        percent: Math.round((1 - sale / regular) * 100),
        amount: regular - sale,
    };
};

const discountOf = (regular, sale) => {
    const base = Number(regular) || 0;
    const price = Number(sale) || 0;
    if (base <= 0 || price >= base) {
        return 0;
    }
    return Math.round((1 - price / base) * 100);
};

const previewProduct = computed(() => {
    const images = [
        ...existing.value.map((image) => ({
            id: image.id,
            url: image.url,
            isMain: String(image.id) === String(mainImage.value),
        })),
        ...previews.value.map((image) => ({
            id: image.key,
            url: image.url,
            isMain: image.key === mainImage.value,
        })),
    ].sort((left, right) => Number(right.isMain) - Number(left.isMain));

    return {
        name: form.product_name || '상품명',
        description: form.description || '',
        statusCode: form.sale_status,
        images,
        options: skus.value.map((sku, index) => ({
            id: sku.id || `new-${index}`,
            name: sku.option_name || '옵션',
            regularPrice: Number(sku.regular_price) || 0,
            salePrice: Number(sku.sale_price) || 0,
            price: Number(sku.sale_price) || 0,
            discount: discountOf(sku.regular_price, sku.sale_price),
            stock: Number(sku.stock_quantity) || 0,
            soldOut: !!sku.sold_out,
        })),
    };
});

onMounted(() => {
    savedFingerprint.value = fingerprint();
    window.addEventListener('beforeunload', onBeforeUnload);
    document.addEventListener('click', onDocumentClick, true);
});
onBeforeUnmount(() => {
    clearTimeout(autosaveTimer);
    window.removeEventListener('beforeunload', onBeforeUnload);
    document.removeEventListener('click', onDocumentClick, true);
});

const submit = async () => {
    message.value = '';
    clearTimeout(autosaveTimer);
    submitting.value = true;
    while (autosaving) {
        await new Promise((resolve) => setTimeout(resolve, 100));
    }
    const body = buildBody();

    try {
        const id = draftId.value || props.product?.id;
        const url = id ? `/seller/products/${id}` : '/seller/products';
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
                <button type="button" class="text-sm font-bold text-brand" @click="previewOpen = true">미리보기</button>
            </div>
            <p v-if="message" class="mt-3 text-sm text-brand">{{ message }}</p>
            <p v-if="autosaveNote" class="mt-2 text-[12px] text-neutral-500">{{ autosaveNote }}</p>
            <p v-else-if="!product || product.sale_status === 'DRAFT'" class="mt-2 text-[12px] text-neutral-500">입력하면 약 2초 뒤 서버에 임시저장됩니다. 구매자에게는 보이지 않고, 판매중으로 공개하려면 저장을 누르세요.</p>
            <p v-else class="mt-2 text-[12px] text-neutral-500">이미 공개된 상품은 저장을 눌러야 반영됩니다. 작성 중인 내용이 자동으로 공개되지는 않습니다.</p>

            <label class="mt-6 block text-sm">
                <span class="mb-1 block text-[12px] text-neutral-500">상품명</span>
                <input v-model="form.product_name" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
            </label>
            <div class="mt-4 text-sm">
                <span class="mb-1 block text-[12px] text-neutral-500">상품 상세 설명</span>
                <DetailEditor v-model="form.description" />
            </div>

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

            <label class="mt-4 block text-sm">
                <span class="mb-1 block text-[12px] text-neutral-500">판매 상태</span>
                <select v-model="form.sale_status" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand sm:max-w-xs">
                    <option value="DRAFT">임시저장</option>
                <option value="ON_SALE">판매중</option>
                    <option value="SOLD_OUT">품절</option>
                    <option value="STOPPED">판매중지</option>
                </select>
            </label>

            <div class="mt-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold">옵션 · 가격 · 재고</h2>
                    <button type="button" class="text-sm font-bold text-brand" @click="addSku">옵션 추가</button>
                </div>
                <p class="mt-1 text-[12px] leading-5 text-neutral-500">옵션마다 정상가와 판매가를 따로 입력합니다. 할인율은 그 옵션의 두 가격으로 계산되므로, 블랙과 화이트의 할인율을 같게 맞출 수 있습니다. 상품 목록에는 판매가가 가장 낮은 옵션이 보입니다.</p>
                <div v-for="(sku, index) in skus" :key="sku.id || index" class="mt-3 rounded-xl bg-neutral-50 p-3">
                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1.4fr)_120px_120px_100px] sm:items-end">
                        <label class="block text-sm">
                            <span class="mb-1 block text-[12px] font-medium text-neutral-500">옵션 이름</span>
                            <input v-model="sku.option_name" placeholder="예: 화이트 / L" class="h-10 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm outline-none focus:border-brand">
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block text-[12px] font-medium text-neutral-500">정상가</span>
                            <input v-model="sku.regular_price" type="number" min="0" class="h-10 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm outline-none focus:border-brand">
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block text-[12px] font-medium text-neutral-500">판매가</span>
                            <input v-model="sku.sale_price" type="number" min="0" class="h-10 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm outline-none focus:border-brand">
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block text-[12px] font-medium text-neutral-500">재고 (개)</span>
                            <input v-model="sku.stock_quantity" type="number" min="0" class="h-10 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm outline-none focus:border-brand">
                        </label>
                    </div>
                    <button type="button" class="mt-2 text-sm font-bold text-brand" @click="clearOptionDiscount(sku)">이 옵션 할인 취소</button>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <label class="flex items-center gap-2 text-sm text-neutral-600">
                            <input v-model="sku.sold_out" type="checkbox"> 이 옵션 품절
                        </label>
                        <div class="flex items-center gap-3">
                            <span v-if="optionDiscount(sku)" class="text-sm font-bold text-brand">{{ optionDiscount(sku).percent }}% · {{ optionDiscount(sku).amount.toLocaleString('ko-KR') }}원 할인</span>
                            <button type="button" class="text-sm text-neutral-500" @click="removeSku(index)">옵션 삭제</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="/seller/products" class="grid h-11 place-items-center rounded-lg px-4 text-sm text-neutral-500">목록</a>
                <button type="submit" class="h-11 rounded-lg bg-brand px-5 text-sm font-bold text-white disabled:opacity-60" :disabled="submitting">저장</button>
            </div>
        </form>

        <div v-if="previewOpen" class="fixed inset-0 z-50 flex flex-col bg-neutral-100">
            <div class="flex items-center justify-between gap-3 border-b border-neutral-200 bg-white px-4 py-3">
                <p class="text-sm text-neutral-600">미리보기입니다. 저장을 누르기 전에는 구매자에게 적용되지 않습니다.</p>
                <button type="button" class="h-9 rounded-lg px-3 text-sm font-bold text-brand" @click="previewOpen = false">닫기</button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto bg-white">
                <ProductDetail :product="previewProduct" :store="store" :shortcuts="['오늘도착', '신선마켓', '골드딜', '기획전', '해외직구', '브랜드특가']" preview embedded />
            </div>
        </div>

        <div v-if="leaveOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="leaveOpen = false">
            <section class="w-full max-w-md rounded-2xl bg-white p-6">
                <h2 class="text-lg font-bold">저장하지 않고 나갈까요?</h2>
                <p v-if="canAutosave" class="mt-3 text-sm leading-6 text-neutral-600">저장을 누르기 전에는 구매자에게 공개되지 않습니다. 임시저장된 내용은 상품 목록에 남아 있고, 아직 임시저장되지 않은 마지막 입력만 사라질 수 있습니다.</p>
                <p v-else class="mt-3 text-sm leading-6 text-neutral-600">이 페이지를 나가면 저장하지 않은 수정은 사라집니다. 구매자 화면은 마지막 저장 내용 그대로입니다.</p>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="h-10 rounded-lg px-4 text-sm" @click="leaveOpen = false">계속 작성</button>
                    <button type="button" class="h-10 rounded-lg bg-neutral-900 px-4 text-sm font-bold text-white" @click="confirmLeave">나가기</button>
                </div>
            </section>
        </div>
    </SellerShell>
</template>
