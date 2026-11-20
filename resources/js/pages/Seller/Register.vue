<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';

const form = reactive({
    seller_name: '',
    business_registration_number: '',
    business_address: '',
    business_license_test: '',
    representative_name: '',
    manager_name: '',
    contact_phone: '',
    login_email: '',
    password: '',
    password_confirmation: '',
    settlement_bank_name: '',
    settlement_account_number: '',
    settlement_account_holder: '',
});
const errors = ref({});
const message = ref('');
const submitting = ref(false);

const fieldError = (name) => errors.value[name]?.[0] || '';

const digitsOnly = (event, field) => {
    form[field] = event.target.value.replace(/\D/g, '');
};

const submit = async () => {
    errors.value = {};
    message.value = '';
    submitting.value = true;

    try {
        const { data } = await axios.post('/seller/register', form);
        window.location.href = data.redirect;
    } catch (error) {
        errors.value = error.response?.data?.errors || {};
        message.value = error.response?.data?.message || '';
        if (!Object.keys(errors.value).length && !message.value) {
            message.value = '가입에 실패했습니다.';
        }
        submitting.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-neutral-100 text-ink">
        <main class="mx-auto max-w-[640px] px-5 py-12">
            <a href="/" class="mb-8 block text-center text-[28px] font-black tracking-tight text-brand">ccuppang</a>
            <section class="rounded-2xl border border-neutral-200 bg-white px-8 py-10">
                <h1 class="text-center text-[26px] font-bold">판매자 회원가입</h1>
                <p class="mt-2 text-center text-sm text-neutral-500">가입하면 바로 판매자 센터를 사용할 수 있습니다.</p>
                <p v-if="message && !Object.keys(errors).length" class="mt-4 text-center text-sm text-brand">{{ message }}</p>

                <form class="mt-8 space-y-4" @submit.prevent="submit">
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">판매자 이름</span>
                        <input v-model="form.seller_name" type="text" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                        <span v-if="fieldError('seller_name')" class="mt-1 block text-xs text-brand">{{ fieldError('seller_name') }}</span>
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">사업자등록번호</span>
                        <input v-model="form.business_registration_number" type="text" placeholder="123-45-67890" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                        <span v-if="fieldError('business_registration_number')" class="mt-1 block text-xs text-brand">{{ fieldError('business_registration_number') }}</span>
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">사업자 주소</span>
                        <input v-model="form.business_address" type="text" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                        <span v-if="fieldError('business_address')" class="mt-1 block text-xs text-brand">{{ fieldError('business_address') }}</span>
                    </label>
                    <label class="block rounded-xl bg-neutral-50 p-4 text-sm">
                        <span class="mb-1 block font-medium">사업자등록증 (테스트)</span>
                        <input v-model="form.business_license_test" type="text" placeholder="예: 테스트 등록증" class="h-12 w-full rounded-lg border border-neutral-300 bg-white px-4 outline-none focus:border-brand">
                        <span class="mt-1 block text-xs text-neutral-400">파일 제출은 아직 연결하지 않았습니다. 확인용 문구만 저장합니다.</span>
                        <span v-if="fieldError('business_license_test')" class="mt-1 block text-xs text-brand">{{ fieldError('business_license_test') }}</span>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">대표자명</span>
                            <input v-model="form.representative_name" type="text" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                            <span v-if="fieldError('representative_name')" class="mt-1 block text-xs text-brand">{{ fieldError('representative_name') }}</span>
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">담당자 이름</span>
                            <input v-model="form.manager_name" type="text" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                            <span v-if="fieldError('manager_name')" class="mt-1 block text-xs text-brand">{{ fieldError('manager_name') }}</span>
                        </label>
                    </div>
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">연락처</span>
                        <input :value="form.contact_phone" type="tel" inputmode="numeric" placeholder="01012345678" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand" @input="digitsOnly($event, 'contact_phone')">
                        <span v-if="fieldError('contact_phone')" class="mt-1 block text-xs text-brand">{{ fieldError('contact_phone') }}</span>
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">이메일</span>
                        <input v-model="form.login_email" type="email" autocomplete="email" placeholder="name@example.com" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                        <span v-if="fieldError('login_email')" class="mt-1 block text-xs text-brand">{{ fieldError('login_email') }}</span>
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">비밀번호</span>
                        <input v-model="form.password" type="password" autocomplete="new-password" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                        <span class="mt-1 block text-xs text-neutral-400">8자 이상, 대문자 1개, 특수문자 1개</span>
                        <span v-if="fieldError('password')" class="mt-1 block text-xs text-brand">{{ fieldError('password') }}</span>
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">비밀번호 확인</span>
                        <input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">정산 은행</span>
                            <input v-model="form.settlement_bank_name" type="text" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                            <span v-if="fieldError('settlement_bank_name')" class="mt-1 block text-xs text-brand">{{ fieldError('settlement_bank_name') }}</span>
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">예금주</span>
                            <input v-model="form.settlement_account_holder" type="text" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                            <span v-if="fieldError('settlement_account_holder')" class="mt-1 block text-xs text-brand">{{ fieldError('settlement_account_holder') }}</span>
                        </label>
                    </div>
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">정산 계좌번호</span>
                        <input :value="form.settlement_account_number" type="text" inputmode="numeric" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand" @input="digitsOnly($event, 'settlement_account_number')">
                        <span v-if="fieldError('settlement_account_number')" class="mt-1 block text-xs text-brand">{{ fieldError('settlement_account_number') }}</span>
                    </label>
                    <button type="submit" class="h-12 w-full rounded-lg bg-brand text-base font-bold text-white disabled:opacity-60" :disabled="submitting">
                        가입하기
                    </button>
                </form>
            </section>
            <a href="/seller/login" class="mt-4 block text-center text-sm text-neutral-500">로그인으로 돌아가기</a>
        </main>
    </div>
</template>
