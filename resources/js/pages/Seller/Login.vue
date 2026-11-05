<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import { firstError } from '../../sellerError';

const form = reactive({
    login_email: '',
    password: '',
});
const message = ref('');
const submitting = ref(false);

const submit = async () => {
    message.value = '';
    submitting.value = true;

    try {
        const { data } = await axios.post('/seller/login', form);
        window.location.href = data.redirect;
    } catch (error) {
        message.value = firstError(error, '로그인에 실패했습니다.');
        submitting.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-neutral-100 text-ink">
        <main class="mx-auto flex max-w-[440px] flex-col px-5 py-16">
            <a href="/" class="mb-8 text-center text-[28px] font-black tracking-tight text-brand">ccuppang</a>
            <section class="rounded-2xl border border-neutral-200 bg-white px-8 py-10">
                <h1 class="text-center text-[26px] font-bold">판매자 로그인</h1>
                <p v-if="message" class="mt-4 text-center text-sm text-brand">{{ message }}</p>

                <form class="mt-8 space-y-3" @submit.prevent="submit">
                    <input
                        v-model="form.login_email"
                        type="email"
                        autocomplete="username"
                        placeholder="이메일"
                        class="h-12 w-full rounded-lg border border-neutral-300 px-4 text-sm outline-none focus:border-brand"
                    >
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="비밀번호"
                        class="h-12 w-full rounded-lg border border-neutral-300 px-4 text-sm outline-none focus:border-brand"
                    >
                    <button type="submit" class="h-12 w-full rounded-lg bg-brand text-base font-bold text-white disabled:opacity-60" :disabled="submitting">
                        로그인
                    </button>
                </form>
            </section>

            <section class="mt-4 rounded-2xl border border-neutral-200 bg-white px-6 py-5 text-sm leading-6 text-neutral-600">
                <p class="font-bold text-neutral-800">임시 판매자 계정</p>
                <p>이메일 seller@ccuppang.test</p>
                <p>비밀번호 Seller1!</p>
                <p class="mt-1 text-[12px] text-neutral-400">입점 심사 없이 상품, 상점, 가격, 재고 권한을 사용할 수 있습니다.</p>
            </section>
        </main>
    </div>
</template>
