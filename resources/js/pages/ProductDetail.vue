<script setup>
import { ref } from 'vue';
import StoreHeader from '../Components/StoreHeader.vue';

const props = defineProps({
    shortcuts: { type: Array, default: () => [] },
    buyer: { type: Object, default: null },
    product: { type: Object, required: true },
    store: { type: Object, required: true },
    preview: { type: Boolean, default: false },
});

const active = ref(props.product.images[0]?.url || '');
const won = (value) => `${Number(value).toLocaleString('ko-KR')}원`;
</script>

<template>
    <div class="min-h-screen bg-white text-ink">
        <StoreHeader :shortcuts="shortcuts" :buyer="buyer" />
        <p v-if="preview" class="bg-neutral-100 py-2 text-center text-sm">
            미리보기입니다.
            <span v-if="product.statusCode !== 'ON_SALE'">구매자에게는 판매중일 때만 보입니다.</span>
            <a href="/seller/products" class="ml-2 font-bold text-brand">판매자 센터</a>
        </p>

        <div v-if="store.banner" class="mx-auto max-w-[1160px] px-5 pt-6">
            <img :src="store.banner" alt="" class="h-44 w-full rounded-2xl object-cover">
        </div>

        <main class="mx-auto grid max-w-[1160px] gap-8 px-5 py-8 lg:grid-cols-2">
            <div>
                <div class="overflow-hidden rounded-2xl bg-neutral-100">
                    <img v-if="active" :src="active" :alt="product.name" class="aspect-square w-full object-cover">
                    <div v-else class="grid aspect-square place-items-center text-sm text-neutral-400">이미지 없음</div>
                </div>
                <div v-if="product.images.length > 1" class="mt-3 flex gap-2">
                    <button v-for="image in product.images" :key="image.id" type="button" class="overflow-hidden rounded-lg" @click="active = image.url">
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
                    <span v-if="product.discount" class="mr-2 text-brand">{{ product.discount }}%</span>{{ won(product.salePrice) }}
                </p>
                <p v-if="product.regularPrice > product.salePrice" class="text-sm text-neutral-400 line-through">{{ won(product.regularPrice) }}</p>

                <ul class="mt-6 divide-y divide-neutral-200 rounded-2xl border border-neutral-200">
                    <li v-for="option in product.options" :key="option.id" class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ option.name }}</p>
                            <p class="text-[12px] text-neutral-500">추가 {{ won(option.additionalPrice) }} · 재고 {{ option.stock }}</p>
                        </div>
                        <span class="shrink-0 font-bold">{{ option.soldOut ? '품절' : won(option.price) }}</span>
                    </li>
                </ul>

                <p v-if="product.description" class="mt-6 whitespace-pre-line text-sm leading-6 text-neutral-600">{{ product.description }}</p>
                <p v-if="store.introduction" class="mt-6 text-sm leading-6 text-neutral-500">{{ store.introduction }}</p>
                <p v-if="store.shippingNotice" class="mt-4 text-sm leading-6 text-neutral-500">배송 {{ store.shippingNotice }}</p>
                <p v-if="store.exchangeNotice" class="mt-2 text-sm leading-6 text-neutral-500">교환/반품 {{ store.exchangeNotice }}</p>
            </div>
        </main>
    </div>
</template>
