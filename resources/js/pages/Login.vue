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
});
const message = ref('');
const submitting = ref(false);

const submit = async () => {
    message.value = '';
    submitting.value = true;

    try {
        const { data } = await axios.post('/login', form);
        window.location.href = data.redirect;
    } catch (error) {
        message.value = error.response?.data?.message || '로그인에 실패했습니다.';
        submitting.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-neutral-100 text-ink">
        <StoreHeader :shortcuts="shortcuts" :buyer="buyer" />

        <main class="mx-auto flex max-w-[420px] flex-col px-5 py-16">
            <section class="rounded-2xl border border-neutral-200 bg-white px-8 py-10">
                <h1 class="text-center text-[28px] font-bold">로그인</h1>
                <p v-if="message" class="mt-4 text-center text-sm text-brand">{{ message }}</p>

                <form class="mt-8 space-y-3" @submit.prevent="submit">
                    <input
                        v-model="form.login_id"
                        type="text"
                        autocomplete="username"
                        placeholder="아이디"
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

            <a href="/register" class="mt-4 block rounded-2xl border border-neutral-300 bg-white py-4 text-center text-sm font-medium">
                회원가입
            </a>
        </main>
    </div>
</template>
