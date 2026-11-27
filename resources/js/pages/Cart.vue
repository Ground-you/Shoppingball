<script setup>
import { computed, reactive, ref } from 'vue';
import axios from 'axios';
import StoreHeader from '../components/StoreHeader.vue';

const props = defineProps({
    shortcuts: { type: Array, default: () => [] },
    buyer: { type: Object, default: null },
    groups: { type: Array, default: () => [] },
});

const groups = ref(props.groups.map((group) => ({
    ...group,
    items: group.items.map((item) => ({ ...item, checked: item.available })),
})));
const message = ref('');
const busy = reactive({});
const won = (value) => `${Number(value).toLocaleString('ko-KR')}원`;

const firstError = (error) => {
    const errors = error.response?.data?.errors || {};

    return Object.values(errors)[0]?.[0] || error.response?.data?.message || '요청에 실패했습니다.';
};

const selected = (group) => group.items.filter((item) => item.checked && item.available);
const productAmount = (group) => selected(group).reduce((sum, item) => sum + item.unitPrice * item.quantity, 0);
const shippingFee = (group) => {
    if (!selected(group).length) {
        return 0;
    }

    return group.freeThreshold !== null && productAmount(group) >= group.freeThreshold ? 0 : group.shippingFee;
};
const total = (group) => productAmount(group) + shippingFee(group);
const isEmpty = computed(() => groups.value.every((group) => !group.items.length));

const changeQuantity = async (item, value) => {
    const quantity = Math.min(99, Math.max(1, Number(value) || 1));
    const before = item.quantity;
    message.value = '';
    item.quantity = quantity;
    busy[item.id] = true;

    try {
        await axios.patch(`/cart/${item.id}`, { quantity });
    } catch (error) {
        item.quantity = before;
        message.value = firstError(error);
    } finally {
        busy[item.id] = false;
    }
};

const remove = async (group, item) => {
    message.value = '';
    busy[item.id] = true;

    try {
        await axios.delete(`/cart/${item.id}`);
        group.items = group.items.filter((candidate) => candidate.id !== item.id);
        groups.value = groups.value.filter((candidate) => candidate.items.length);
        window.dispatchEvent(new Event('cart-updated'));
    } catch (error) {
        message.value = firstError(error);
        busy[item.id] = false;
    }
};

const order = (group) => {
    const items = selected(group);

    if (!items.length) {
        message.value = '주문할 상품을 선택해 주세요.';
        return;
    }

    window.location.href = `/checkout?items=${items.map((item) => `${item.skuId}:${item.quantity}`).join(',')}`;
};
</script>

<template>
    <div class="min-h-screen bg-neutral-100 text-ink">
        <StoreHeader :shortcuts="shortcuts" :buyer="buyer" />
        <main class="mx-auto max-w-[860px] px-5 py-8">
            <h1 class="text-[24px] font-bold">장바구니</h1>
            <p class="mt-1 text-sm text-neutral-500">배송비와 정책이 판매자마다 달라서 판매자별로 나눠 주문합니다.</p>
            <p v-if="message" class="mt-4 rounded-lg bg-white px-4 py-3 text-sm text-brand" role="alert">{{ message }}</p>

            <section v-if="isEmpty" class="mt-6 rounded-2xl border border-neutral-200 bg-white px-6 py-16 text-center">
                <p class="font-medium">장바구니가 비어 있습니다.</p>
                <a href="/" class="mt-4 inline-block text-sm font-bold text-brand">쇼핑하러 가기</a>
            </section>

            <section v-for="group in groups" :key="group.storeId" class="mt-6 rounded-2xl border border-neutral-200 bg-white p-6">
                <h2 class="font-bold">{{ group.storeName }}</h2>
                <ul class="mt-4 divide-y divide-neutral-200">
                    <li v-for="item in group.items" :key="item.id" class="flex items-start gap-3 py-4 first:pt-0">
                        <input v-model="item.checked" type="checkbox" class="mt-9 accent-brand" :disabled="!item.available" :aria-label="`${item.name} 선택`">
                        <a :href="`/products/${item.productId}`" class="shrink-0">
                            <img v-if="item.image" :src="item.image" alt="" class="h-20 w-20 rounded-xl object-cover" :class="item.available ? '' : 'opacity-40'">
                            <div v-else class="h-20 w-20 rounded-xl bg-neutral-100"></div>
                        </a>
                        <div class="min-w-0 flex-1 text-sm">
                            <p class="font-medium" :class="item.available ? '' : 'text-neutral-400'">{{ item.name }}</p>
                            <p class="mt-1 text-neutral-500">{{ item.option }}</p>
                            <p v-if="!item.available" class="mt-2 font-bold text-brand">{{ item.unavailableReason }}</p>
                            <div v-else class="mt-2 flex items-center gap-2">
                                <button type="button" class="h-8 w-8 rounded-lg border border-neutral-300" :disabled="busy[item.id] || item.quantity <= 1" aria-label="수량 줄이기" @click="changeQuantity(item, item.quantity - 1)">−</button>
                                <input :value="item.quantity" type="number" min="1" :max="item.stock" class="h-8 w-14 rounded-lg border border-neutral-300 text-center outline-none focus:border-brand" @change="changeQuantity(item, $event.target.value)">
                                <button type="button" class="h-8 w-8 rounded-lg border border-neutral-300" :disabled="busy[item.id] || item.quantity >= item.stock" aria-label="수량 늘리기" @click="changeQuantity(item, item.quantity + 1)">+</button>
                                <span class="ml-2 text-xs text-neutral-400">재고 {{ item.stock }}</span>
                            </div>
                        </div>
                        <div class="shrink-0 text-right text-sm">
                            <p class="font-bold">{{ won(item.unitPrice * item.quantity) }}</p>
                            <button type="button" class="mt-3 text-xs text-neutral-400" :disabled="busy[item.id]" @click="remove(group, item)">삭제</button>
                        </div>
                    </li>
                </ul>

                <div class="mt-2 rounded-xl bg-neutral-50 px-4 py-3 text-sm">
                    <p class="flex justify-between"><span>상품 금액</span><span>{{ won(productAmount(group)) }}</span></p>
                    <p class="mt-1 flex justify-between"><span>배송비</span><span>{{ won(shippingFee(group)) }}</span></p>
                    <p class="mt-2 flex justify-between font-bold"><span>결제 예정 금액</span><span>{{ won(total(group)) }}</span></p>
                </div>
                <button type="button" class="mt-4 h-12 w-full rounded-lg bg-brand font-bold text-white disabled:opacity-60" :disabled="!selected(group).length" @click="order(group)">
                    {{ selected(group).length }}개 상품 주문하기
                </button>
            </section>
        </main>
    </div>
</template>
