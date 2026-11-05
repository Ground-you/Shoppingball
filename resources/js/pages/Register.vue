<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import StoreHeader from '../Components/StoreHeader.vue';

defineProps({
    shortcuts: { type: Array, default: () => [] },
    buyer: { type: Object, default: null },
});

const form = reactive({
    login_id: '',
    password: '',
    password_confirmation: '',
    buyer_name: '',
    nickname: '',
    email: '',
    phone: '',
    marketing_consent: false,
});
const errors = ref({});
const submitting = ref(false);

const fieldError = (name) => errors.value[name]?.[0] || '';

const submit = async () => {
    errors.value = {};
    submitting.value = true;

    try {
        const { data } = await axios.post('/register', form);
        window.location.href = data.redirect;
    } catch (error) {
        errors.value = error.response?.data?.errors || {};
        submitting.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-neutral-100 text-ink">
        <StoreHeader :shortcuts="shortcuts" :buyer="buyer" />

        <main class="mx-auto max-w-[460px] px-5 py-12">
            <section class="rounded-2xl border border-neutral-200 bg-white px-8 py-10">
                <h1 class="text-center text-[28px] font-bold">회원가입</h1>
                <p class="mt-2 text-center text-sm text-neutral-500">회원 정보를 입력해 주세요.</p>

                <form class="mt-8 space-y-4" @submit.prevent="submit">
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">아이디</span>
                        <input v-model="form.login_id" type="text" autocomplete="username" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                        <span v-if="fieldError('login_id')" class="mt-1 block text-xs text-brand">{{ fieldError('login_id') }}</span>
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
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">이름</span>
                        <input v-model="form.buyer_name" type="text" autocomplete="name" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                        <span v-if="fieldError('buyer_name')" class="mt-1 block text-xs text-brand">{{ fieldError('buyer_name') }}</span>
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">닉네임</span>
                        <input v-model="form.nickname" type="text" maxlength="10" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                        <span class="mt-1 block text-xs text-neutral-400">2~10자, 한글·영문·숫자</span>
                        <span v-if="fieldError('nickname')" class="mt-1 block text-xs text-brand">{{ fieldError('nickname') }}</span>
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">이메일</span>
                        <input v-model="form.email" type="email" autocomplete="email" placeholder="name@example.com" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                        <span v-if="fieldError('email')" class="mt-1 block text-xs text-brand">{{ fieldError('email') }}</span>
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">휴대폰</span>
                        <input v-model="form.phone" type="tel" autocomplete="tel" class="h-12 w-full rounded-lg border border-neutral-300 px-4 outline-none focus:border-brand">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-neutral-600">
                        <input v-model="form.marketing_consent" type="checkbox" class="h-4 w-4 accent-brand">
                        마케팅 정보 수신에 동의합니다.
                    </label>
                    <button type="submit" class="h-12 w-full rounded-lg bg-brand text-base font-bold text-white disabled:opacity-60" :disabled="submitting">
                        가입하기
                    </button>
                </form>
            </section>

            <a href="/login" class="mt-4 block text-center text-sm text-neutral-500">이미 계정이 있으면 로그인</a>
        </main>
    </div>
</template>
