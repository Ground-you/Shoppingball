<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import SellerShell from '../../Components/SellerShell.vue';
import { firstError } from '../../sellerError';

const props = defineProps({
    account: { type: Object, required: true },
    section: { type: String, default: 'profile' },
    profile: { type: Object, required: true },
});

const form = reactive({
    seller_name: props.profile.sellerName,
    manager_name: props.profile.managerName,
    representative_name: props.profile.representativeName,
    login_email: props.profile.email,
    contact_phone: props.profile.phone,
    business_address: props.profile.address,
    settlement_bank_name: props.profile.bankName,
    settlement_account_number: props.profile.accountNumber,
    settlement_account_holder: props.profile.accountHolder,
});
const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const withdrawPassword = ref('');
const message = ref('');
const error = ref('');
const showWithdraw = ref(false);

const save = async () => {
    error.value = '';
    message.value = '';
    try {
        const { data } = await axios.patch('/seller/profile', form);
        message.value = data.message;
    } catch (caught) {
        error.value = firstError(caught);
    }
};

const changePassword = async () => {
    error.value = '';
    message.value = '';
    try {
        const { data } = await axios.patch('/seller/password', passwordForm);
        message.value = data.message;
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
    } catch (caught) {
        error.value = firstError(caught);
    }
};

const withdraw = async () => {
    error.value = '';
    try {
        const { data } = await axios.post('/seller/withdraw', { password: withdrawPassword.value });
        window.location.href = data.redirect;
    } catch (caught) {
        error.value = firstError(caught);
    }
};
</script>

<template>
    <SellerShell :account="account" :section="section">
        <form class="rounded-2xl border border-neutral-200 bg-white p-6" @submit.prevent="save">
            <h1 class="text-[22px] font-bold">판매자 정보</h1>
            <p v-if="message" class="mt-3 text-sm font-bold text-brand">{{ message }}</p>
            <p v-if="error" class="mt-3 text-sm text-brand">{{ error }}</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">판매자 이름</span>
                    <input v-model="form.seller_name" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">담당자 이름</span>
                    <input v-model="form.manager_name" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">대표자명</span>
                    <input v-model="form.representative_name" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">이메일</span>
                    <input v-model="form.login_email" type="email" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">연락처</span>
                    <input v-model="form.contact_phone" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">사업자등록번호</span>
                    <input :value="profile.businessNumber" disabled class="h-11 w-full rounded-lg border border-neutral-200 bg-neutral-50 px-3 text-neutral-500">
                </label>
                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1 block text-[12px] text-neutral-500">사업자 주소</span>
                    <input v-model="form.business_address" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">정산 은행</span>
                    <input v-model="form.settlement_bank_name" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-[12px] text-neutral-500">예금주</span>
                    <input v-model="form.settlement_account_holder" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1 block text-[12px] text-neutral-500">정산 계좌번호</span>
                    <input v-model="form.settlement_account_number" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
                </label>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="h-11 rounded-lg bg-brand px-5 text-sm font-bold text-white">저장</button>
            </div>
        </form>

        <form class="mt-4 rounded-2xl border border-neutral-200 bg-white p-6" @submit.prevent="changePassword">
            <h2 class="text-base font-bold">비밀번호 변경</h2>
            <p class="mt-1 text-[12px] text-neutral-500">8자 이상, 대문자 1개, 특수문자 1개가 필요합니다.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <input v-model="passwordForm.current_password" type="password" placeholder="현재 비밀번호" class="h-11 rounded-lg border border-neutral-200 px-3 text-sm outline-none focus:border-brand">
                <input v-model="passwordForm.password" type="password" placeholder="새 비밀번호" class="h-11 rounded-lg border border-neutral-200 px-3 text-sm outline-none focus:border-brand">
                <input v-model="passwordForm.password_confirmation" type="password" placeholder="새 비밀번호 확인" class="h-11 rounded-lg border border-neutral-200 px-3 text-sm outline-none focus:border-brand">
            </div>
            <div class="mt-4 flex justify-end">
                <button type="submit" class="h-11 rounded-lg px-5 text-sm font-bold text-brand">비밀번호 변경</button>
            </div>
        </form>

        <section class="mt-4 rounded-2xl border border-neutral-200 bg-white p-6">
            <button type="button" class="text-sm text-neutral-500" @click="showWithdraw = !showWithdraw">판매자 탈퇴</button>
            <form v-if="showWithdraw" class="mt-4" @submit.prevent="withdraw">
                <p class="text-sm text-neutral-600">탈퇴하면 이 계정으로 로그인할 수 없고, 판매중 상품은 스토어에서 내려갑니다.</p>
                <input v-model="withdrawPassword" type="password" placeholder="비밀번호" class="mt-3 h-11 w-full max-w-xs rounded-lg border border-neutral-200 px-3 text-sm outline-none focus:border-brand">
                <button type="submit" class="mt-3 block h-11 rounded-lg px-4 text-sm font-bold text-red-500">탈퇴하기</button>
            </form>
        </section>
    </SellerShell>
</template>
