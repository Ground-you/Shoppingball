<script setup>
import axios from 'axios';

defineProps({
    shortcuts: {
        type: Array,
        default: () => [],
    },
    buyer: {
        type: Object,
        default: null,
    },
});

const logout = async () => {
    const { data } = await axios.post('/logout');
    window.location.href = data.redirect;
};
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-neutral-200 bg-white">
        <div class="mx-auto flex h-[78px] max-w-[1160px] items-center gap-8 px-5">
            <a href="/" class="shrink-0 text-[30px] leading-none font-black tracking-tight text-brand">ccuppang</a>

            <form class="flex h-12 min-w-0 flex-1 overflow-hidden rounded-full border-2 border-brand" @submit.prevent>
                <input
                    type="search"
                    placeholder="찾고 싶은 상품을 검색해보세요"
                    class="min-w-0 flex-1 px-4 text-sm outline-none"
                >
                <button type="submit" class="bg-brand px-7 text-sm font-bold text-white">검색</button>
            </form>

            <a href="/seller" class="hidden shrink-0 text-[12px] font-bold text-brand lg:inline">판매자센터</a>

            <div class="hidden items-center gap-5 text-[12px] text-neutral-600 lg:flex">
                <a v-if="!buyer" href="/login" class="flex flex-col items-center gap-1">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="3.2" /><path d="M5 19c1.4-3 3.8-4.5 7-4.5s5.6 1.5 7 4.5" /></svg>
                    로그인
                </a>
                <div v-else class="flex items-center gap-4">
                    <a href="/mypage" class="max-w-24 truncate font-medium text-neutral-800">{{ buyer.nickname || buyer.name }}</a>
                    <button type="button" class="text-neutral-500" @click="logout">로그아웃</button>
                </div>
                <a :href="buyer ? '/mypage' : '/login'" class="flex flex-col items-center gap-1">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 10.5 12 4l8 6.5V20H4Z" /><path d="M10 20v-6h4v6" /></svg>
                    마이쇼핑
                </a>
                <button type="button" class="relative flex flex-col items-center gap-1">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 8h12l-1 11H7L6 8Z" /><path d="M9 8V7a3 3 0 0 1 6 0v1" /></svg>
                    <span class="absolute top-0 right-1 grid h-4 min-w-4 place-items-center rounded-full bg-brand px-1 text-[10px] font-bold text-white">0</span>
                    장바구니
                </button>
            </div>
        </div>

        <div class="border-t border-neutral-100">
            <div class="mx-auto flex h-11 max-w-[1160px] items-center gap-6 overflow-x-auto px-5 text-sm">
                <button type="button" class="flex shrink-0 items-center gap-1.5 font-bold">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
                    카테고리
                </button>
                <button v-for="item in shortcuts" :key="item" type="button" class="shrink-0 text-neutral-700">
                    {{ item }}
                </button>
            </div>
        </div>
    </header>
</template>
