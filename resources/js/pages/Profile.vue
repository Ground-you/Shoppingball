<script setup>
import { nextTick, reactive, ref } from 'vue';
import axios from 'axios';
import StoreHeader from '../Components/StoreHeader.vue';

const props = defineProps({
    shortcuts: { type: Array, default: () => [] },
    buyer: { type: Object, required: true },
    orders: { type: Array, default: () => [] },
    addresses: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
});

const profile = reactive({
    buyer_name: props.buyer.name,
    nickname: props.buyer.nickname || '',
    email: props.buyer.email,
    phone: props.buyer.phone || '',
});
const profileError = ref('');
const profileSaved = ref(false);

const popup = ref(null);
const editingAddress = ref(null);
const editingPayment = ref(null);
const deleting = ref(null);
const formError = ref('');
const withdrawPassword = ref('');

const emptyAddress = () => ({
    address_name: '',
    recipient_name: '',
    recipient_phone: '',
    postal_code: '',
    address_line1: '',
    address_line2: '',
    is_default: false,
});

const emptyPayment = () => ({
    method_type: 'CARD',
    alias: '',
    provider_name: '',
    number_last4: '',
    is_default: false,
});

const addressForm = reactive(emptyAddress());
const postcodeBox = ref(null);
const postcodeOpen = ref(false);

const openPostcode = async () => {
    formError.value = '';

    if (!window.daum?.Postcode) {
        formError.value = '우편번호 찾기를 불러오지 못했습니다. 네트워크를 확인해 주세요.';
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
const paymentForm = reactive(emptyPayment());

const won = (value) => `${Number(value).toLocaleString('ko-KR')}원`;

const closePopup = () => {
    popup.value = null;
    formError.value = '';
    deleting.value = null;
    postcodeOpen.value = false;
};

const openAddress = (address = null) => {
    editingAddress.value = address;
    Object.assign(addressForm, address ? { ...address } : emptyAddress());
    formError.value = '';
    popup.value = 'address';
};

const openPayment = (method = null) => {
    editingPayment.value = method;
    Object.assign(paymentForm, method ? { ...method } : emptyPayment());
    formError.value = '';
    popup.value = 'payment';
};

const saveProfile = async () => {
    profileError.value = '';
    profileSaved.value = false;

    try {
        await axios.patch('/mypage', profile);
        profileSaved.value = true;
        window.location.reload();
    } catch (error) {
        const errors = error.response?.data?.errors || {};
        profileError.value = errors.buyer_name?.[0] || errors.email?.[0] || errors.nickname?.[0] || '정보를 저장하지 못했습니다.';
    }
};

const saveAddress = async () => {
    formError.value = '';

    try {
        if (editingAddress.value) {
            await axios.patch(`/mypage/addresses/${editingAddress.value.id}`, addressForm);
        } else {
            await axios.post('/mypage/addresses', addressForm);
        }
        window.location.reload();
    } catch (error) {
        formError.value = firstError(error) || '배송지를 저장하지 못했습니다.';
    }
};

const savePayment = async () => {
    formError.value = '';

    try {
        if (editingPayment.value) {
            await axios.patch(`/mypage/payment-methods/${editingPayment.value.id}`, paymentForm);
        } else {
            await axios.post('/mypage/payment-methods', paymentForm);
        }
        window.location.reload();
    } catch (error) {
        formError.value = firstError(error) || '결제 수단을 저장하지 못했습니다.';
    }
};

const setDefaultAddress = async (address) => {
    await axios.patch(`/mypage/addresses/${address.id}/default`);
    window.location.reload();
};

const removeAddress = async () => {
    await axios.delete(`/mypage/addresses/${deleting.value.id}`);
    window.location.reload();
};

const removePayment = async () => {
    await axios.delete(`/mypage/payment-methods/${deleting.value.id}`);
    window.location.reload();
};

const withdraw = async () => {
    formError.value = '';

    try {
        const { data } = await axios.post('/withdraw', { password: withdrawPassword.value });
        window.location.href = data.redirect;
    } catch (error) {
        formError.value = error.response?.data?.message || firstError(error) || '탈퇴에 실패했습니다.';
    }
};

const firstError = (error) => {
    const errors = error.response?.data?.errors || {};
    const first = Object.values(errors)[0];
    return first?.[0] || '';
};
</script>

<template>
    <div class="min-h-screen bg-neutral-100 text-ink">
        <StoreHeader :shortcuts="shortcuts" :buyer="buyer" />

        <main class="mx-auto max-w-[1160px] space-y-4 px-5 py-8">
            <section class="rounded-2xl bg-white px-6 py-5">
                <div class="pl-[7px]">
                    <p class="text-sm text-neutral-500">마이쇼핑</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ buyer.nickname || buyer.name }}님</h1>
                    <p class="mt-1 text-sm text-neutral-500">{{ buyer.loginId }}</p>
                </div>

                <div class="mt-6 grid gap-6 border-t border-neutral-300 pt-6 lg:grid-cols-[1.5fr_2.5fr]">
                    <div class="flex h-full flex-col pl-[7px]">
                    <h2 class="text-lg font-bold">내 정보 수정</h2>
                    <p v-if="profileError" class="mt-3 text-sm text-brand">{{ profileError }}</p>
                    <form class="mt-3 flex h-full flex-col gap-3" @submit.prevent="saveProfile">
                        <label class="text-sm">
                            <span class="block font-medium leading-5">이름</span>
                            <input v-model="profile.buyer_name" type="text" class="h-8 w-full bg-transparent px-1 outline-none">
                        </label>
                        <label class="text-sm">
                            <span class="block font-medium leading-5">닉네임</span>
                            <input v-model="profile.nickname" type="text" maxlength="10" class="h-8 w-full bg-transparent px-1 outline-none">
                        </label>
                        <label class="text-sm">
                            <span class="block font-medium leading-5">이메일</span>
                            <input v-model="profile.email" type="email" class="h-8 w-full bg-transparent px-1 outline-none">
                        </label>
                        <label class="text-sm">
                            <span class="block font-medium leading-5">휴대폰</span>
                            <input v-model="profile.phone" type="tel" class="h-8 w-full bg-transparent px-1 outline-none">
                        </label>
                        <div class="mt-auto pt-3">
                            <button type="submit" class="px-1 text-sm font-bold text-brand">저장</button>
                        </div>
                    </form>
                    </div>

                <div class="pl-[7px] lg:border-l lg:border-neutral-300 lg:pl-[calc(2rem+7px)]">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold">배송지</h2>
                        <button type="button" class="text-sm font-bold text-brand" @click="openAddress()">배송지 추가</button>
                    </div>
                    <p v-if="addresses.length === 0" class="py-6 text-sm text-neutral-500">등록된 배송지가 없습니다.</p>
                    <div
                        class="mt-3"
                        :class="addresses.length >= 5 ? 'max-h-80 overflow-y-auto overscroll-contain pr-2' : ''"
                    >
                        <article v-for="address in addresses" :key="address.id" class="flex items-start justify-between border-t border-neutral-300 py-3 text-sm">
                            <div>
                                <p class="font-medium">{{ address.recipient_name }} <span v-if="address.is_default" class="ml-1 text-xs text-brand">기본</span></p>
                                <p class="text-neutral-500">{{ address.recipient_phone }}</p>
                                <p class="text-neutral-600">({{ address.postal_code }}) {{ address.address_line1 }} {{ address.address_line2 }}</p>
                            </div>
                            <div class="flex shrink-0 gap-3 text-neutral-500">
                                <button v-if="!address.is_default" type="button" class="font-medium text-brand" @click="setDefaultAddress(address)">기본 배송지로 설정</button>
                                <button type="button" @click="openAddress(address)">수정</button>
                                <button type="button" @click="deleting = address; popup = 'address-delete'">삭제</button>
                            </div>
                        </article>
                    </div>
                </div>
                </div>
            </section>

            <section class="rounded-2xl bg-white py-5 pr-6 pl-[calc(1.5rem+7px)]">
                <h2 class="text-lg font-bold">구매한 상품</h2>
                <p v-if="orders.length === 0" class="py-8 text-center text-sm text-neutral-500">아직 구매한 상품이 없습니다.</p>
                <article v-for="order in orders" :key="order.id" class="mt-4 border-t border-neutral-300 pt-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium">{{ order.orderNumber }}</span>
                        <span class="text-neutral-500">{{ order.orderedAt }}</span>
                    </div>
                    <p v-for="item in order.items" :key="item.name + item.option" class="mt-2 text-sm">
                        {{ item.name }} / {{ item.option }} · {{ item.quantity }}개 · {{ won(item.amount) }}
                    </p>
                    <p class="mt-2 text-sm font-bold">결제 {{ won(order.finalPaymentAmount) }}</p>
                </article>
            </section>

            <section class="rounded-2xl bg-white py-5 pr-6 pl-[calc(1.5rem+7px)]">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">결제 수단</h2>
                    <button type="button" class="text-sm font-bold text-brand" @click="openPayment()">결제 수단 추가</button>
                </div>
                <p v-if="paymentMethods.length === 0" class="py-6 text-sm text-neutral-500">등록된 결제 수단이 없습니다.</p>
                <article v-for="method in paymentMethods" :key="method.id" class="mt-3 flex items-start justify-between border-t border-neutral-300 pt-3 text-sm">
                    <div>
                        <p class="font-medium">{{ method.alias }} <span v-if="method.is_default" class="ml-1 text-xs text-brand">기본</span></p>
                        <p class="text-neutral-500">{{ method.method_type === 'CARD' ? '카드' : '계좌' }} · {{ method.provider_name }} · {{ method.number_last4 }}</p>
                    </div>
                    <div class="flex gap-3 text-neutral-500">
                        <button type="button" @click="openPayment(method)">변경</button>
                        <button type="button" @click="deleting = method; popup = 'payment-delete'">삭제</button>
                    </div>
                </article>
            </section>

            <section class="rounded-2xl bg-white py-5 pr-6 pl-[calc(1.5rem+7px)]">
                <button type="button" class="text-sm text-neutral-500 underline" @click="popup = 'withdraw'; withdrawPassword = ''; formError = ''">회원탈퇴</button>
            </section>
        </main>

        <div v-if="popup" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="closePopup">
            <section class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">
                        {{ popup === 'address' ? (editingAddress ? '배송지 수정' : '배송지 추가') : '' }}
                        {{ popup === 'payment' ? (editingPayment ? '결제 수단 변경' : '결제 수단 추가') : '' }}
                        {{ popup === 'address-delete' ? '배송지 삭제' : '' }}
                        {{ popup === 'payment-delete' ? '결제 수단 삭제' : '' }}
                        {{ popup === 'withdraw' ? '회원탈퇴' : '' }}
                    </h2>
                    <button type="button" class="text-xl text-neutral-400" @click="closePopup">×</button>
                </div>
                <p v-if="formError" class="mt-3 text-sm text-brand">{{ formError }}</p>

                <form v-if="popup === 'address'" class="mt-5 space-y-3" @submit.prevent="saveAddress">
                    <input v-model="addressForm.address_name" type="text" placeholder="배송지 이름" class="h-11 w-full rounded-lg border border-neutral-300 px-3 text-sm outline-none focus:border-brand">
                    <input v-model="addressForm.recipient_name" type="text" placeholder="받는 사람" class="h-11 w-full rounded-lg border border-neutral-300 px-3 text-sm outline-none focus:border-brand">
                    <input v-model="addressForm.recipient_phone" type="tel" placeholder="연락처" class="h-11 w-full rounded-lg border border-neutral-300 px-3 text-sm outline-none focus:border-brand">
                    <div class="flex gap-2">
                        <input v-model="addressForm.postal_code" type="text" readonly placeholder="우편번호" class="h-11 min-w-0 flex-1 rounded-lg border border-neutral-300 bg-neutral-50 px-3 text-sm outline-none">
                        <button type="button" class="h-11 shrink-0 rounded-lg border border-brand px-3 text-sm font-bold text-brand" @click="openPostcode">우편번호 찾기</button>
                    </div>
                    <input v-model="addressForm.address_line1" type="text" placeholder="주소" class="h-11 w-full rounded-lg border border-neutral-300 px-3 text-sm outline-none focus:border-brand">
                    <input v-model="addressForm.address_line2" type="text" placeholder="상세주소" class="h-11 w-full rounded-lg border border-neutral-300 px-3 text-sm outline-none focus:border-brand">
                    <label class="flex items-center gap-2 text-sm"><input v-model="addressForm.is_default" type="checkbox" class="accent-brand">기본 배송지로 설정</label>
                    <button type="submit" class="h-11 w-full rounded-lg bg-brand text-sm font-bold text-white">저장</button>
                </form>

                <form v-else-if="popup === 'payment'" class="mt-5 space-y-3" @submit.prevent="savePayment">
                    <select v-model="paymentForm.method_type" class="h-11 w-full rounded-lg border border-neutral-300 px-3 text-sm outline-none focus:border-brand">
                        <option value="CARD">카드</option>
                        <option value="ACCOUNT">계좌</option>
                    </select>
                    <input v-model="paymentForm.alias" type="text" placeholder="별칭" class="h-11 w-full rounded-lg border border-neutral-300 px-3 text-sm outline-none focus:border-brand">
                    <input v-model="paymentForm.provider_name" type="text" placeholder="카드사 또는 은행" class="h-11 w-full rounded-lg border border-neutral-300 px-3 text-sm outline-none focus:border-brand">
                    <input v-model="paymentForm.number_last4" type="text" maxlength="4" placeholder="끝 4자리" class="h-11 w-full rounded-lg border border-neutral-300 px-3 text-sm outline-none focus:border-brand">
                    <label class="flex items-center gap-2 text-sm"><input v-model="paymentForm.is_default" type="checkbox" class="accent-brand">기본 결제 수단으로 설정</label>
                    <button type="submit" class="h-11 w-full rounded-lg bg-brand text-sm font-bold text-white">저장</button>
                </form>

                <div v-else-if="popup === 'address-delete' || popup === 'payment-delete'" class="mt-5">
                    <p class="text-sm text-neutral-600">삭제하면 목록에서 바로 사라집니다.</p>
                    <div class="mt-5 flex gap-2">
                        <button type="button" class="h-11 flex-1 rounded-lg border border-neutral-300 text-sm" @click="closePopup">취소</button>
                        <button type="button" class="h-11 flex-1 rounded-lg bg-brand text-sm font-bold text-white" @click="popup === 'address-delete' ? removeAddress() : removePayment()">삭제</button>
                    </div>
                </div>

                <form v-else class="mt-5 space-y-3" @submit.prevent="withdraw">
                    <p class="text-sm leading-6 text-neutral-600">탈퇴하면 이 아이디로 다시 로그인할 수 없습니다. 구매 기록이 있으면 주문 연결을 위해 개인정보를 지운 기록만 남습니다.</p>
                    <input v-model="withdrawPassword" type="password" placeholder="비밀번호" class="h-11 w-full rounded-lg border border-neutral-300 px-3 text-sm outline-none focus:border-brand">
                    <button type="submit" class="h-11 w-full rounded-lg bg-brand text-sm font-bold text-white">탈퇴하기</button>
                </form>
            </section>
        </div>

        <div v-if="postcodeOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 px-4">
            <section class="w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold">우편번호 찾기</h2>
                    <button type="button" class="text-xl text-neutral-400" @click="postcodeOpen = false">×</button>
                </div>
                <div ref="postcodeBox" class="h-[460px] overflow-hidden rounded-lg border border-neutral-300"></div>
            </section>
        </div>
    </div>
</template>
