<script setup>
import { ref } from 'vue';
import axios from 'axios';
import SellerShell from '../../Components/SellerShell.vue';
import { firstError } from '../../sellerError';

const props = defineProps({
    account: { type: Object, required: true },
    section: { type: String, default: 'products' },
    products: { type: Array, default: () => [] },
});

const selected = ref([]);
const message = ref('');
const won = (value) => `${Number(value).toLocaleString('ko-KR')}원`;

const toggleAll = (event) => {
    selected.value = event.target.checked ? props.products.map((product) => product.id) : [];
};

const bulk = async (saleStatus) => {
    message.value = '';
    try {
        const { data } = await axios.post('/seller/products/bulk', {
            ids: selected.value,
            sale_status: saleStatus,
        });
        message.value = data.message;
        window.location.reload();
    } catch (error) {
        message.value = firstError(error);
    }
};

const remove = async (id) => {
    if (!window.confirm('이 상품을 삭제할까요?')) return;
    await axios.delete(`/seller/products/${id}`);
    window.location.reload();
};
</script>

<template>
    <SellerShell :account="account" :section="section">
        <section class="rounded-2xl border border-neutral-200 bg-white p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-[22px] font-bold">상품 관리</h1>
                <a href="/seller/products/create" class="rounded-lg bg-brand px-4 py-2.5 text-sm font-bold text-white">상품 등록</a>
            </div>
            <p v-if="message" class="mt-3 text-sm text-brand">{{ message }}</p>

            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                <button type="button" class="rounded-lg px-3 py-2 font-bold text-brand" @click="bulk('ON_SALE')">선택 판매 시작</button>
                <button type="button" class="rounded-lg px-3 py-2 font-bold text-neutral-600" @click="bulk('STOPPED')">선택 판매 중지</button>
            </div>

            <p v-if="!products.length" class="mt-8 text-sm text-neutral-500">등록한 상품이 없습니다.</p>

            <div v-else class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="text-[12px] text-neutral-500">
                        <tr class="border-b border-neutral-200">
                            <th class="py-3 pr-3 font-medium"><input type="checkbox" @change="toggleAll"></th>
                            <th class="py-3 font-medium">상품</th>
                            <th class="py-3 font-medium">판매가</th>
                            <th class="py-3 font-medium">재고</th>
                            <th class="py-3 font-medium">상태</th>
                            <th class="py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product in products" :key="product.id" class="border-b border-neutral-100">
                            <td class="py-3 pr-3">
                                <input v-model="selected" type="checkbox" :value="product.id">
                            </td>
                            <td class="py-3">
                                <div class="flex items-center gap-3">
                                    <img v-if="product.image" :src="product.image" alt="" class="h-12 w-12 rounded-lg object-cover">
                                    <div v-else class="h-12 w-12 rounded-lg bg-neutral-100" />
                                    <div>
                                        <p class="font-medium">{{ product.name }}</p>
                                        <p class="text-[12px] text-neutral-400">{{ product.category || '카테고리 없음' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <p class="font-bold">{{ won(product.salePrice) }}</p>
                                <p v-if="product.regularPrice > product.salePrice" class="text-[12px] text-neutral-400 line-through">{{ won(product.regularPrice) }}</p>
                            </td>
                            <td class="py-3">{{ product.stock }}</td>
                            <td class="py-3">{{ product.statusName }}</td>
                            <td class="py-3 text-right">
                                <a :href="`/seller/products/${product.id}/preview`" class="mr-3 text-neutral-500">미리보기</a>
                                <a :href="`/seller/products/${product.id}/edit`" class="mr-3 font-bold text-brand">수정</a>
                                <button type="button" class="text-neutral-500" @click="remove(product.id)">삭제</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </SellerShell>
</template>
