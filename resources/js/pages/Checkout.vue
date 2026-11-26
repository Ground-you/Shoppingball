<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import StoreHeader from '../Components/StoreHeader.vue';

const props = defineProps({
    shortcuts: { type: Array, default: () => [] },
    buyer: { type: Object, default: null },
    payment: { type: Object, required: true },
    paymentError: { type: String, default: '' },
    sku: { type: Object, required: true },
    quantity: { type: Number, default: 1 },
    addresses: { type: Array, default: () => [] },
});

const defaultAddress = props.addresses.find((address) => address.is_default) || props.addresses[0];
const form = reactive({
    sku_id: props.sku.id,
    quantity: Math.min(Math.max(props.quantity, 1), Math.max(props.sku.stock, 1)),
    address_id: defaultAddress?.id || '',
    delivery_request: '',
});
const addressForm = reactive({
    address_name: '',
    recipient_name: '',
    recipient_phone: '',
    postal_code: '',
    address_line1: '',
    address_line2: '',
    is_default: true,
});
const message = ref(props.paymentError || '');
const submitting = ref(false);
const savingAddress = ref(false);
const postcodeBox = ref(null);
const postcodeOpen = ref(false);
const won = (value) => `${Number(value).toLocaleString('ko-KR')}원`;

const shipping = computed(() => {
    const productAmount = props.sku.unitPrice * form.quantity;
    if (props.sku.freeThreshold !== null && productAmount >= props.sku.freeThreshold) {
        return 0;
    }

    return props.sku.shippingFee;
});
const total = computed(() => props.sku.unitPrice * form.quantity + shipping.value);
const widgets = ref(null);
const widgetReady = ref(false);

const loadToss = () => new Promise((resolve, reject) => {
    if (window.TossPayments) {
        resolve();
        return;
    }

    const script = document.createElement('script');
    script.src = 'https://js.tosspayments.com/v2/standard';
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('토스페이먼츠를 불러오지 못했습니다.'));
    document.head.appendChild(script);
});

onMounted(async () => {
    if (props.payment.driver !== 'toss' || !props.payment.tossClientKey || !props.addresses.length) {
        return;
    }

    try {
        await loadToss();
        const instance = window.TossPayments(props.payment.tossClientKey).widgets({
            customerKey: props.payment.customerKey,
        });
        await instance.setAmount({ currency: 'KRW', value: total.value });
        await instance.renderPaymentMethods({ selector: '#toss-payment-method', variantKey: 'DEFAULT' });
        await instance.renderAgreement({ selector: '#toss-agreement' });
        widgets.value = instance;
        widgetReady.value = true;
    } catch (error) {
        message.value = error.message || '토스 결제창을 열지 못했습니다.';
    }
});

watch(total, async (value) => {
    if (!widgets.value) {
        return;
    }

    await widgets.value.setAmount({ currency: 'KRW', value });
});

const openPostcode = async () => {
    message.value = '';

    if (!window.daum?.Postcode) {
        message.value = '우편번호 찾기를 불러오지 못했습니다. 네트워크를 확인해 주세요.';
        return;
    }

    postcodeOpen.value = true;
    await nextTick();
    postcodeBox.value.innerHTML = '';

    new window.daum.Postcode({
        width: '100%',
        height: '420px',
        oncomplete(data) {
            addressForm.postal_code = data.zonecode;
            addressForm.address_line1 = data.userSelectedType === 'R' ? data.roadAddress : data.jibunAddress;
            postcodeOpen.value = false;
        },
    }).embed(postcodeBox.value);
};

const saveAddress = async () => {
    message.value = '';
    savingAddress.value = true;

    try {
        await axios.post('/mypage/addresses', addressForm);
        window.location.reload();
    } catch (error) {
        const errors = error.response?.data?.errors || {};
        message.value = Object.values(errors)[0]?.[0] || error.response?.data?.message || '배송지를 저장하지 못했습니다.';
        savingAddress.value = false;
    }
};

const submit = async () => {
    message.value = '';

    if (!form.address_id) {
        message.value = '등록된 배송지를 선택해 주세요.';
        return;
    }

    submitting.value = true;

    try {
        const { data } = await axios.post('/checkout', form);

        if (props.payment.driver === 'toss') {
            await widgets.value.setAmount({ currency: 'KRW', value: data.amount });
            await widgets.value.requestPayment({
                orderId: data.orderNumber,
                orderName: data.orderName,
                successUrl: `${window.location.origin}/checkout/success`,
                failUrl: `${window.location.origin}/checkout/fail`,
                customerEmail: props.buyer.email,
                customerName: props.buyer.name,
            });
            return;
        }

        window.location.href = data.redirect;
    } catch (error) {
        const errors = error.response?.data?.errors || {};
        message.value = Object.values(errors)[0]?.[0] || error.response?.data?.message || '주문에 실패했습니다.';
        submitting.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-neutral-100 text-ink">
        <StoreHeader :shortcuts="shortcuts" :buyer="buyer" />
        <main class="mx-auto max-w-[720px] px-5 py-8">
            <section class="rounded-2xl border border-neutral-200 bg-white p-6">
                <h1 class="text-[24px] font-bold">주문하기</h1>
                <div class="mt-5 flex gap-4">
                    <img v-if="sku.image" :src="sku.image" alt="" class="h-24 w-24 rounded-xl object-cover">
                    <div>
                        <p class="font-medium">{{ sku.name }}</p>
                        <p class="mt-1 text-sm text-neutral-500">{{ sku.option }}</p>
                        <p class="mt-2 font-bold">{{ won(sku.unitPrice) }}</p>
                    </div>
                </div>

                <label class="mt-6 block text-sm">
                    <span class="mb-1 block font-medium">수량</span>
                    <input v-model.number="form.quantity" type="number" min="1" :max="sku.stock" class="h-12 w-28 rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                </label>

                <form v-if="addresses.length" class="mt-4 space-y-4" @submit.prevent="submit">
                    <div class="space-y-2">
                        <p class="text-sm font-medium">배송지</p>
                        <label v-for="address in addresses" :key="address.id" class="flex gap-3 rounded-xl border border-neutral-200 px-4 py-3 text-sm">
                            <input v-model="form.address_id" type="radio" :value="address.id" class="mt-1">
                            <span>
                                <span class="font-medium">{{ address.address_name || address.recipient_name }}</span>
                                <span v-if="address.is_default" class="ml-1 text-xs text-brand">기본</span>
                                <span class="mt-1 block text-neutral-500">{{ address.recipient_name }} · {{ address.recipient_phone }}</span>
                                <span class="block text-neutral-600">({{ address.postal_code }}) {{ address.address_line1 }} {{ address.address_line2 }}</span>
                            </span>
                        </label>
                    </div>

                    <input v-model="form.delivery_request" type="text" placeholder="배송 요청사항" class="h-12 w-full rounded-lg border border-neutral-300 px-4 text-sm outline-none focus:border-brand">

                    <div class="rounded-xl bg-neutral-50 px-4 py-3 text-sm">
                        <p class="flex justify-between"><span>상품 금액</span><span>{{ won(sku.unitPrice * form.quantity) }}</span></p>
                        <p class="mt-1 flex justify-between"><span>배송비</span><span>{{ won(shipping) }}</span></p>
                        <p class="mt-2 flex justify-between font-bold"><span>결제 금액</span><span>{{ won(total) }}</span></p>
                    </div>

                    <div v-if="payment.driver === 'toss'" class="space-y-3">
                        <p class="text-sm font-medium">결제수단</p>
                        <p class="text-sm text-neutral-500">카드와 간편결제를 고른 뒤 결제하기를 누르면 토스 결제창이 열립니다.</p>
                        <div id="toss-payment-method"></div>
                        <div id="toss-agreement"></div>
                    </div>
                    <p v-else class="text-sm text-neutral-500">테스트 결제로 바로 결제 완료 처리됩니다. 나중에 토스페이먼츠로 바꿀 수 있습니다.</p>
                    <p v-if="message" class="text-sm text-brand">{{ message }}</p>
                    <button type="submit" class="h-12 w-full rounded-lg bg-brand font-bold text-white disabled:opacity-60" :disabled="submitting || sku.soldOut || !form.address_id || (payment.driver === 'toss' ? !widgetReady : payment.driver !== 'manual')">
                        {{ sku.soldOut ? '품절' : '결제하기' }}
                    </button>
                </form>

                <form v-else class="mt-4 rounded-xl border border-neutral-200 p-4" @submit.prevent="saveAddress">
                        <p class="text-sm font-medium">등록된 배송지가 없습니다.</p>
                        <p class="mt-1 text-sm text-neutral-500">결제하려면 배송지를 먼저 등록해 주세요.</p>
                        <div class="mt-4 grid gap-3">
                            <input v-model="addressForm.address_name" type="text" placeholder="배송지 이름" class="h-12 rounded-lg border border-neutral-300 px-4 text-sm outline-none focus:border-brand">
                            <input v-model="addressForm.recipient_name" type="text" placeholder="받는 사람" class="h-12 rounded-lg border border-neutral-300 px-4 text-sm outline-none focus:border-brand">
                            <input v-model="addressForm.recipient_phone" type="tel" placeholder="연락처" class="h-12 rounded-lg border border-neutral-300 px-4 text-sm outline-none focus:border-brand">
                            <div class="flex gap-2">
                                <input v-model="addressForm.postal_code" type="text" readonly placeholder="우편번호" class="h-12 min-w-0 flex-1 rounded-lg border border-neutral-300 bg-neutral-50 px-4 text-sm outline-none">
                                <button type="button" class="h-12 shrink-0 rounded-lg border border-brand px-3 text-sm font-bold text-brand" @click="openPostcode">우편번호 찾기</button>
                            </div>
                            <input v-model="addressForm.address_line1" type="text" readonly placeholder="주소" class="h-12 rounded-lg border border-neutral-300 bg-neutral-50 px-4 text-sm outline-none">
                            <input v-model="addressForm.address_line2" type="text" placeholder="상세주소" class="h-12 rounded-lg border border-neutral-300 px-4 text-sm outline-none focus:border-brand">
                            <p v-if="message" class="text-sm text-brand">{{ message }}</p>
                            <button type="submit" class="h-12 rounded-lg bg-brand font-bold text-white disabled:opacity-60" :disabled="savingAddress">
                                {{ savingAddress ? '등록 중' : '배송지 등록' }}
                            </button>
                        </div>
                </form>
            </section>
        </main>
        <div v-if="postcodeOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-[520px] rounded-2xl bg-white p-4">
                <div class="mb-3 flex items-center justify-between">
                    <p class="font-bold">우편번호 찾기</p>
                    <button type="button" class="text-xl text-neutral-400" @click="postcodeOpen = false">×</button>
                </div>
                <div ref="postcodeBox" class="h-[460px] overflow-hidden rounded-lg border border-neutral-300"></div>
            </div>
        </div>
    </div>
</template>
