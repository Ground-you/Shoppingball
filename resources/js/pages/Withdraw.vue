<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import StoreHeader from '../Components/StoreHeader.vue';

defineProps({
    shortcuts: { type: Array, default: () => [] },
    buyer: { type: Object, default: null },
});

const form = reactive({ password: '' });
const message = ref('');
const submitting = ref(false);

const submit = async () => {
    message.value = '';
    submitting.value = true;

    try {
        const { data } = await axios.post('/withdraw', form);
        window.location.href = data.redirect;
    } catch (error) {
        message.value = error.response?.data?.message || error.response?.data?.errors?.password?.[0] || '탈퇴에 실패했습니다.';
        submitting.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-neutral-100 text-ink">
        <StoreHeader :shortcuts="shortcuts" :buyer="buyer" />

        <main class="mx-auto max-w-[460px] px-5 py-16">
            <section class="rounded-2xl border border-neutral-200 bg-white px-8 py-10">
                <h1 class="text-[24px] font-bold">회원탈퇴</h1>
                <p class="mt-3 text-sm leading-6 text-neutral-600">
                    탈퇴하면 이 계정으로 다시 로그인할 수 없습니다. 주문 기록은 남고, 로그인은 막힙니다.
                </p>
                <p v-if="message" class="mt-4 text-sm text-brand">{{ message }}</p>

                <form class="mt-6 space-y-3" @submit.prevent="submit">
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="비밀번호 확인"
                        class="h-12 w-full rounded-lg border border-neutral-300 px-4 text-sm outline-none focus:border-brand"
                    >
                    <button type="submit" class="h-12 w-full rounded-lg bg-brand text-base font-bold text-white disabled:opacity-60" :disabled="submitting">
                        탈퇴하기
                    </button>
                </form>
            </section>
        </main>
    </div>
</template>
