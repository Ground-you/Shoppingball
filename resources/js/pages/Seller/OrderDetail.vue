<script setup>
import { ref } from 'vue';
import axios from 'axios';
import SellerShell from '../../Components/SellerShell.vue';

const props = defineProps({
    account: { type: Object, required: true },
    section: { type: String, default: 'orders' },
    order: { type: Object, required: true },
    carriers: { type: Array, default: () => [] },
});

const carrierId = ref(props.order.carrierId || props.carriers[0]?.id || '');
const rejectReasons = ref({});
const message = ref('');
const submitting = ref(false);
const won = (value) => `${Number(value).toLocaleString('ko-KR')}원`;

const progress = async (action) => {
    message.value = '';
    submitting.value = true;

    try {
        const { data } = await axios.post(`/seller/orders/${props.order.id}/progress`, {
            action,
            carrier_id: carrierId.value || null,
        });
        window.location.href = data.redirect;
    } catch (error) {
        const errors = error.response?.data?.errors || {};
        message.value = Object.values(errors)[0]?.[0] || error.response?.data?.message || '처리에 실패했습니다.';
        submitting.value = false;
    }
};

const claim = async (item, decision) => {
    message.value = '';
    submitting.value = true;

    try {
        const { data } = await axios.post(`/seller/orders/${props.order.id}/claims/${item.id}`, {
            decision,
            reject_reason: rejectReasons.value[item.id] || null,
        });
        window.location.href = data.redirect;
    } catch (error) {
        const errors = error.response?.data?.errors || {};
        message.value = Object.values(errors)[0]?.[0] || error.response?.data?.message || '처리에 실패했습니다.';
        submitting.value = false;
    }
};
</script>

<template>
    <SellerShell :account="account" :section="section">
        <section class="rounded-2xl border border-neutral-200 bg-white p-6">
            <a href="/seller/orders" class="text-sm text-neutral-500">주문 목록</a>
            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                <h1 class="text-[22px] font-bold">{{ order.orderNumber }}</h1>
                <span class="rounded-lg bg-neutral-100 px-3 py-1 text-sm font-medium">{{ order.statusName }}</span>
            </div>
            <p class="mt-2 text-sm text-neutral-500">{{ order.orderedAt }}</p>
            <p v-if="message" class="mt-3 text-sm text-brand">{{ message }}</p>
        </section>

        <section class="mt-4 rounded-2xl border border-neutral-200 bg-white p-6">
            <h2 class="font-bold">주문 상품</h2>
            <article v-for="item in order.items" :key="item.id" class="mt-3 border-t border-neutral-200 pt-3 text-sm">
                <p class="font-medium">{{ item.name }}</p>
                <p class="text-neutral-500">{{ item.option }} · {{ item.quantity }}개 · {{ won(item.amount) }}</p>
            </article>
        </section>

        <section class="mt-4 grid gap-4 lg:grid-cols-2">
            <article class="rounded-2xl border border-neutral-200 bg-white p-6 text-sm">
                <h2 class="font-bold">배송지</h2>
                <p class="mt-3 font-medium">{{ order.recipient }}</p>
                <p class="text-neutral-500">{{ order.phone }}</p>
                <p class="text-neutral-600">({{ order.postalCode }}) {{ order.address }}</p>
                <p v-if="order.deliveryRequest" class="mt-2 text-neutral-500">요청사항 {{ order.deliveryRequest }}</p>
            </article>
            <article class="rounded-2xl border border-neutral-200 bg-white p-6 text-sm">
                <h2 class="font-bold">결제</h2>
                <p class="mt-3">{{ order.payment.method }} · {{ order.payment.status }}</p>
                <p class="text-neutral-500">{{ order.payment.paid ? '결제 완료' : '결제 완료 전' }}<span v-if="order.payment.paidAt"> · {{ order.payment.paidAt }}</span></p>
                <p class="mt-2">상품 {{ won(order.productAmount) }} · 배송비 {{ won(order.shippingFee) }}</p>
                <p class="font-bold">결제 {{ won(order.amount) }}</p>
                <p v-if="order.payment.refund" class="mt-1 text-brand">환불 {{ won(order.payment.refund) }}</p>
                <p v-if="order.shipmentStatus" class="mt-2 text-neutral-500">배송 상태 {{ order.shipmentStatus }}</p>
            </article>
        </section>

        <section class="mt-4 rounded-2xl border border-neutral-200 bg-white p-6">
            <h2 class="font-bold">주문 처리</h2>
            <label v-if="order.actions.ship" class="mt-4 block text-sm">
                <span class="mb-1 block font-medium">택배사</span>
                <select v-model="carrierId" class="h-11 w-full rounded-lg border border-neutral-300 px-3 outline-none focus:border-brand">
                    <option v-for="carrier in carriers" :key="carrier.id" :value="carrier.id">{{ carrier.name }}</option>
                </select>
            </label>
            <div class="mt-4 flex flex-wrap gap-2">
                <button v-if="order.actions.ship" type="button" class="rounded-lg bg-brand px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60" :disabled="submitting" @click="progress('ship')">상품발송</button>
                <button v-if="order.actions.deliver" type="button" class="rounded-lg bg-brand px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60" :disabled="submitting" @click="progress('deliver')">상품도착</button>
            </div>
        </section>

        <section v-if="order.claims.length" class="mt-4 rounded-2xl border border-neutral-200 bg-white p-6">
            <h2 class="font-bold">취소·교환</h2>
            <article v-for="item in order.claims" :key="item.id" class="mt-4 border-t border-neutral-200 pt-4 text-sm">
                <p class="font-medium">{{ item.typeName }} · {{ item.status }}</p>
                <p class="mt-1 text-neutral-600">{{ item.productName }} / {{ item.optionName }} · {{ item.quantity }}개</p>
                <p v-if="item.type === 'EXCHANGE'" class="mt-1">
                    교환 옵션 {{ item.exchangeOption }} · 재고 {{ item.exchangeStock }}<span v-if="item.exchangeSoldOut"> · 품절</span>
                    · 추가 금액 {{ won(item.additionalAmount || 0) }}
                </p>
                <p v-if="item.reason" class="mt-1 text-neutral-500">사유 {{ item.reason }}</p>
                <p v-if="item.rejectReason" class="mt-1 text-neutral-500">거절 사유 {{ item.rejectReason }}</p>
                <div v-if="item.canApprove || item.canReject || item.canComplete" class="mt-3 space-y-2">
                    <input v-if="item.canReject" v-model="rejectReasons[item.id]" type="text" placeholder="거절 사유" class="h-11 w-full rounded-lg border border-neutral-300 px-3 outline-none focus:border-brand">
                    <div class="flex flex-wrap gap-2">
                        <button v-if="item.canApprove" type="button" class="rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white" :disabled="submitting" @click="claim(item, 'approve')">{{ item.typeName }} 승인</button>
                        <button v-if="item.canReject" type="button" class="rounded-lg border border-neutral-300 px-4 py-2 text-sm" :disabled="submitting" @click="claim(item, 'reject')">{{ item.typeName }} 거절</button>
                        <button v-if="item.canComplete" type="button" class="rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white" :disabled="submitting" @click="claim(item, 'complete')">교환 처리 완료</button>
                    </div>
                </div>
            </article>
        </section>
    </SellerShell>
</template>
