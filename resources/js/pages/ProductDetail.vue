<script setup>
import { computed, ref } from 'vue';
import StoreHeader from '../components/StoreHeader.vue';

const props = defineProps({
    shortcuts: { type: Array, default: () => [] },
    buyer: { type: Object, default: null },
    product: { type: Object, default: null },
    store: { type: Object, default: null },
    preview: { type: Boolean, default: false },
    embedded: { type: Boolean, default: false },
    deleted: { type: Boolean, default: false },
});

const active = ref(props.product?.images?.[0]?.url || '');
const optionId = ref(props.product?.options?.find((option) => !option.soldOut)?.id || props.product?.options?.[0]?.id || '');
const quantity = ref(1);
const viewer = ref(false);
const won = (value) => `${Number(value).toLocaleString('ko-KR')}원`;
const selected = computed(() => props.product?.options?.find((option) => String(option.id) === String(optionId.value)) || props.product?.options?.[0] || null);
const salePrice = computed(() => selected.value?.salePrice ?? selected.value?.price ?? props.product?.salePrice ?? 0);
const regularPrice = computed(() => selected.value?.regularPrice ?? props.product?.regularPrice ?? 0);
const discount = computed(() => selected.value?.discount ?? 0);
const richDetail = computed(() => String(props.product?.description || '').includes('<'));

const buy = () => {
    if (props.preview || !optionId.value) {
        return;
    }

    const target = `/checkout?sku=${optionId.value}&quantity=${quantity.value}`;
    window.location.href = props.buyer ? target : '/login';
};
</script>

<template>
    <div class="min-h-screen bg-white text-ink">
        <StoreHeader :shortcuts="shortcuts" :buyer="buyer" />
        <p v-if="preview && !embedded" class="bg-neutral-100 py-2 text-center text-sm">
            미리보기입니다.
            <span v-if="product.statusCode !== 'ON_SALE'">구매자에게는 판매중일 때만 보입니다.</span>
            <a href="/seller/products" class="ml-2 font-bold text-brand">판매자 센터</a>
        </p>

        <div v-if="store?.banner" class="mx-auto max-w-[1160px] px-5 pt-6">
            <img :src="store.banner" alt="" class="h-44 w-full rounded-2xl object-cover">
        </div>

        <main v-if="deleted" class="mx-auto max-w-[1160px] px-5 py-24 text-center">
            <h1 class="text-[26px] font-bold">삭제된 상품</h1>
            <p class="mt-3 text-sm text-neutral-500">판매자가 삭제한 상품입니다.</p>
            <a href="/" class="mt-6 inline-block text-sm font-bold text-brand">홈으로</a>
        </main>
        <main v-else class="mx-auto grid max-w-[1160px] gap-8 px-5 py-8 lg:grid-cols-2">
            <div>
                <div class="overflow-hidden rounded-2xl border border-neutral-400 bg-neutral-100">
                    <button v-if="active" type="button" class="block w-full" @click="viewer = true">
                        <img :src="active" :alt="product.name" class="aspect-square w-full object-contain">
                    </button>
                    <div v-else class="grid aspect-square place-items-center text-sm text-neutral-400">이미지 없음</div>
                </div>
                <div v-if="product.images.length > 1" class="mt-3 flex gap-2">
                    <button
                        v-for="image in product.images"
                        :key="image.id"
                        type="button"
                        class="overflow-hidden rounded-lg border bg-neutral-100"
                        :class="active === image.url ? 'border-neutral-900' : 'border-neutral-300'"
                        @click="active = image.url"
                    >
                        <img :src="image.url" alt="" class="h-16 w-16 object-cover">
                    </button>
                </div>
            </div>

            <div>
                <div class="flex items-center gap-2 text-sm text-neutral-500">
                    <img v-if="store.logo" :src="store.logo" alt="" class="h-8 w-8 rounded-full object-cover">
                    <p>{{ store.name }}</p>
                </div>
                <h1 class="mt-2 text-[26px] font-bold leading-snug">{{ product.name }}</h1>
                <p class="mt-4 text-[28px] font-bold">
                    <span v-if="discount" class="mr-2 text-brand">{{ discount }}%</span>{{ won(salePrice) }}
                </p>
                <p v-if="regularPrice > salePrice" class="text-sm text-neutral-400 line-through">{{ won(regularPrice) }}</p>

                <ul class="mt-6 divide-y divide-neutral-200 rounded-2xl border border-neutral-200">
                    <li v-for="option in product.options" :key="option.id" class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <label class="flex min-w-0 flex-1 items-center gap-3">
                            <input v-model="optionId" type="radio" :value="option.id" :disabled="option.soldOut">
                        <div>
                            <p class="font-medium">{{ option.name }}</p>
                            <p class="text-[12px] text-neutral-500">정상가 {{ won(option.regularPrice) }} · 재고 {{ option.stock }}</p>
                        </div>
                        <span class="shrink-0 font-bold">{{ option.soldOut ? '품절' : won(option.salePrice ?? option.price) }}</span>
                        </label>
                    </li>
                </ul>

                <div class="mt-4 flex gap-2">
                    <input v-model.number="quantity" type="number" min="1" class="h-12 w-24 rounded-lg border border-neutral-300 px-3 outline-none focus:border-brand">
                    <button type="button" class="h-12 flex-1 rounded-lg bg-brand font-bold text-white" @click="buy">구매하기</button>
                </div>

                <p v-if="store.shippingNotice" class="mt-6 text-sm leading-6 text-neutral-500">배송 {{ store.shippingNotice }}</p>
                <p v-if="store.exchangeNotice" class="mt-2 text-sm leading-6 text-neutral-500">교환/반품 {{ store.exchangeNotice }}</p>
            </div>
        </main>

        <section v-if="!deleted && product.description" class="mx-auto max-w-[860px] px-5 pb-16">
            <div class="rounded-2xl border border-neutral-200 px-6 py-8 sm:px-8">
                <h2 class="text-center text-lg font-bold">상품 상세</h2>
                <div v-if="richDetail" class="product-detail mt-6 text-base" v-html="product.description"></div>
                <p v-else class="mt-6 whitespace-pre-line text-base leading-7">{{ product.description }}</p>
            </div>
        </section>

        <div v-if="viewer && active" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click.self="viewer = false">
            <button type="button" class="absolute top-4 right-4 text-3xl text-white" @click="viewer = false">×</button>
            <img :src="active" :alt="product.name" class="max-h-[90vh] max-w-full object-contain">
        </div>
    </div>
</template>

<style>
.product-detail {
    line-height: 1.6;
}
.product-detail [style*="font-size"] {
    line-height: 1.4;
}
.product-detail img {
    display: block;
    width: fit-content;
    max-width: 100%;
    height: auto;
    margin: 8px 0;
}
.product-detail div[style*="text-align: center"] > img,
.product-detail div[style*="text-align:center"] > img {
    margin-left: auto;
    margin-right: auto;
}
.product-detail div[style*="text-align: right"] > img,
.product-detail div[style*="text-align:right"] > img {
    margin-left: auto;
    margin-right: 0;
}
.product-detail ul {
    list-style: disc;
    padding-left: 1.25rem;
}
.product-detail ol {
    list-style: decimal;
    padding-left: 1.25rem;
}
</style>
