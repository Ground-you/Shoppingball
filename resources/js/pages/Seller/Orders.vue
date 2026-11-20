<script setup>
import { reactive } from 'vue';
import SellerShell from '../../Components/SellerShell.vue';

const props = defineProps({
    account: { type: Object, required: true },
    section: { type: String, default: 'orders' },
    keyword: { type: String, default: '' },
    status: { type: String, default: '' },
    orders: { type: Array, default: () => [] },
});

const form = reactive({
    q: props.keyword,
});
const won = (value) => `${Number(value).toLocaleString('ko-KR')}원`;

const search = () => {
    const params = new URLSearchParams();
    if (form.q) {
        params.set('q', form.q);
    }
    if (props.status) {
        params.set('status', props.status);
    }
    window.location.href = `/seller/orders${params.toString() ? `?${params}` : ''}`;
};
</script>

<template>
    <SellerShell :account="account" :section="section">
        <section class="rounded-2xl border border-neutral-200 bg-white p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-[22px] font-bold">주문 관리</h1>
                <div class="flex gap-2 text-sm">
                    <a href="/seller/orders" class="rounded-lg px-3 py-2" :class="status === '' ? 'bg-brand font-bold text-white' : 'bg-neutral-100'">전체</a>
                    <a href="/seller/orders?status=PREPARING" class="rounded-lg px-3 py-2" :class="status === 'PREPARING' ? 'bg-brand font-bold text-white' : 'bg-neutral-100'">신규 주문</a>
                </div>
            </div>
            <form class="mt-4 flex gap-2" @submit.prevent="search">
                <input v-model="form.q" type="search" placeholder="상품명 검색" class="h-11 min-w-0 flex-1 rounded-lg border border-neutral-300 px-4 text-sm outline-none focus:border-brand">
                <button type="submit" class="h-11 rounded-lg bg-brand px-4 text-sm font-bold text-white">검색</button>
            </form>
        </section>

        <section class="mt-4 rounded-2xl border border-neutral-200 bg-white p-6">
            <p v-if="!orders.length" class="text-sm text-neutral-500">주문이 없습니다.</p>
            <article v-for="order in orders" :key="order.id" class="border-t border-neutral-200 py-4 first:border-t-0 first:pt-0">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <a :href="`/seller/orders/${order.id}`" class="font-bold text-brand">{{ order.orderNumber }}</a>
                    <span class="text-sm text-neutral-500">{{ order.orderedAt }}</span>
                </div>
                <p v-for="item in order.items" :key="item.id" class="mt-2 text-sm">
                    {{ item.name }} / {{ item.option }} · {{ item.quantity }}개
                </p>
                <p class="mt-2 text-sm">
                    <span class="font-medium">{{ order.statusName }}</span>
                    <span class="text-neutral-500"> · {{ order.payment.status }} · {{ order.recipient }} · {{ won(order.amount) }}</span>
                </p>
            </article>
        </section>
    </SellerShell>
</template>
