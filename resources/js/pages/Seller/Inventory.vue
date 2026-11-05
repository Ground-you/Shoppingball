<script setup>
import { ref } from 'vue';
import axios from 'axios';
import SellerShell from '../../Components/SellerShell.vue';
import { firstError } from '../../sellerError';

const props = defineProps({
    account: { type: Object, required: true },
    section: { type: String, default: 'inventory' },
    rows: { type: Array, default: () => [] },
});

const items = ref(props.rows.map((row) => ({ ...row, draft: row.stock })));
const message = ref('');

const apply = async (row, action) => {
    message.value = '';
    try {
        const { data } = await axios.patch(`/seller/inventory/${row.id}`, {
            action,
            quantity: action === 'set' ? Number(row.draft) : 1,
        });
        Object.assign(row, data.sku, { draft: data.sku.stock });
    } catch (error) {
        message.value = firstError(error);
    }
};
</script>

<template>
    <SellerShell :account="account" :section="section">
        <section class="rounded-2xl border border-neutral-200 bg-white p-6">
            <h1 class="text-[22px] font-bold">재고 관리</h1>
            <p class="mt-2 text-sm text-neutral-500">옵션별 수량을 확인하고, 추가 · 차감 · 수정 · 품절 · 재입고를 처리합니다. 5개 이하는 재고 부족으로 표시합니다.</p>
            <p v-if="message" class="mt-3 text-sm text-brand">{{ message }}</p>
            <p v-if="!items.length" class="mt-8 text-sm text-neutral-500">재고를 관리할 상품이 없습니다.</p>

            <div v-else class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="text-[12px] text-neutral-500">
                        <tr class="border-b border-neutral-200">
                            <th class="py-3 font-medium">상품</th>
                            <th class="py-3 font-medium">옵션</th>
                            <th class="py-3 font-medium">재고</th>
                            <th class="py-3 font-medium">상태</th>
                            <th class="py-3 font-medium">수량 변경</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in items" :key="row.id" class="border-b border-neutral-100">
                            <td class="py-3 pr-3">
                                <a :href="`/seller/products/${row.productId}/edit`" class="font-medium">{{ row.productName }}</a>
                                <p class="text-[11px] text-neutral-400">{{ row.skuCode }}</p>
                            </td>
                            <td class="py-3">
                                {{ row.optionName }}
                                <span v-if="row.additionalPrice" class="text-neutral-400">+{{ row.additionalPrice.toLocaleString('ko-KR') }}원</span>
                            </td>
                            <td class="py-3">
                                <span :class="row.low ? 'font-bold text-brand' : ''">{{ row.stock }}</span>
                                <span v-if="row.low" class="ml-1 text-[11px] text-brand">부족</span>
                            </td>
                            <td class="py-3">{{ row.soldOut ? '품절' : '판매중' }}</td>
                            <td class="py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" class="h-8 rounded-lg border border-neutral-200 px-2" @click="apply(row, 'subtract')">-1</button>
                                    <button type="button" class="h-8 rounded-lg border border-neutral-200 px-2" @click="apply(row, 'add')">+1</button>
                                    <input v-model="row.draft" type="number" min="0" class="h-8 w-20 rounded-lg border border-neutral-200 px-2 outline-none focus:border-brand">
                                    <button type="button" class="h-8 rounded-lg px-2 font-bold text-brand" @click="apply(row, 'set')">수정</button>
                                    <button v-if="!row.soldOut" type="button" class="h-8 rounded-lg px-2 text-neutral-500" @click="apply(row, 'sold_out')">품절</button>
                                    <button v-else type="button" class="h-8 rounded-lg px-2 font-bold text-brand" @click="apply(row, 'restock')">재입고</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </SellerShell>
</template>
