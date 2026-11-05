<script setup>
import SellerShell from '../../Components/SellerShell.vue';

defineProps({
    account: { type: Object, required: true },
    section: { type: String, default: 'home' },
    storeOpen: { type: Boolean, default: true },
    counts: { type: Object, required: true },
    recent: { type: Array, default: () => [] },
});

const won = (value) => `${Number(value).toLocaleString('ko-KR')}원`;
</script>

<template>
    <SellerShell :account="account" :section="section">
        <section class="rounded-2xl border border-neutral-200 bg-white p-6">
            <h1 class="text-[22px] font-bold">{{ account.storeName || '판매자 홈' }}</h1>
            <p class="mt-2 text-sm text-neutral-500">
                {{ storeOpen ? '상점이 영업중입니다.' : '상점이 휴업중입니다. 판매중 상품도 스토어에 노출되지 않습니다.' }}
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="/seller/products/create" class="rounded-lg bg-brand px-4 py-2.5 text-sm font-bold text-white">상품 등록</a>
                <a href="/seller/inventory" class="rounded-lg px-4 py-2.5 text-sm font-bold text-brand">재고 관리</a>
            </div>
        </section>

        <section class="mt-4 grid gap-3 sm:grid-cols-3">
            <article v-for="card in [
                ['전체 상품', counts.total],
                ['판매중', counts.onSale],
                ['판매중지', counts.stopped],
                ['임시저장', counts.draft],
                ['재고 5개 이하', counts.lowStock],
                ['품절 옵션', counts.soldOut],
            ]" :key="card[0]" class="rounded-2xl border border-neutral-200 bg-white px-5 py-4">
                <p class="text-[12px] text-neutral-500">{{ card[0] }}</p>
                <p class="mt-1 text-[24px] font-bold">{{ card[1] }}</p>
            </article>
        </section>

        <section class="mt-4 rounded-2xl border border-neutral-200 bg-white p-6">
            <h2 class="text-base font-bold">최근 상품</h2>
            <p v-if="!recent.length" class="mt-4 text-sm text-neutral-500">등록한 상품이 없습니다.</p>
            <ul v-else class="mt-3 divide-y divide-neutral-200">
                <li v-for="item in recent" :key="item.id" class="flex items-center justify-between gap-4 py-3 text-sm">
                    <a :href="`/seller/products/${item.id}/edit`" class="truncate font-medium">{{ item.name }}</a>
                    <span class="shrink-0 text-neutral-500">{{ item.statusName }} · 재고 {{ item.stock }} · {{ won(item.price) }}</span>
                </li>
            </ul>
        </section>
    </SellerShell>
</template>
