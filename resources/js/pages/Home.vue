<script setup>
import StoreHeader from '../Components/StoreHeader.vue';
import ProductSection from '../Components/ProductSection.vue';

defineProps({
    shortcuts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    banner: { type: Object, required: true },
    sideCards: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    buyer: { type: Object, default: null },
});
</script>

<template>
    <div class="min-h-screen bg-white text-ink">
        <StoreHeader :shortcuts="shortcuts" :buyer="buyer" />

        <main class="mx-auto grid max-w-[1160px] gap-4 px-5 py-5 lg:grid-cols-[210px_minmax(0,1fr)_230px]">
            <aside class="hidden overflow-hidden rounded-2xl border border-neutral-200 lg:block">
                <ul>
                    <li v-for="category in categories" :key="category">
                        <button type="button" class="flex w-full items-center justify-between px-3 py-2.5 text-left text-[13px] text-neutral-700 hover:bg-neutral-50">
                            {{ category }}
                            <span class="text-neutral-300">›</span>
                        </button>
                    </li>
                </ul>
            </aside>

            <section class="relative min-h-[280px] overflow-hidden rounded-2xl bg-neutral-200 sm:min-h-[340px]">
                <img :src="banner.image" alt="" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-black/55 to-black/10" />
                <div class="relative flex h-full flex-col justify-end p-8 text-white">
                    <p class="text-sm text-white/80">{{ banner.eyebrow }}</p>
                    <h1 class="mt-2 text-[30px] leading-snug font-bold">
                        <span v-for="line in banner.title" :key="line" class="block">{{ line }}</span>
                    </h1>
                    <div class="mt-5 flex items-center gap-3 text-xs">
                        <button type="button" class="grid h-7 w-7 place-items-center rounded-full bg-black/30">‹</button>
                        <span>{{ banner.page }}</span>
                        <button type="button" class="grid h-7 w-7 place-items-center rounded-full bg-black/30">›</button>
                    </div>
                </div>
            </section>

            <aside class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                <article v-for="card in sideCards" :key="card.title" class="rounded-2xl border border-neutral-200 p-4">
                    <h2 class="text-sm font-bold">{{ card.title }}</h2>
                    <p class="mt-2 text-[12px] leading-5 text-neutral-500">{{ card.body }}</p>
                    <a v-if="card.action === '로그인'" href="/login" class="mt-3 inline-block text-[12px] font-bold text-brand">{{ card.action }}</a>
                    <button v-else type="button" class="mt-3 text-[12px] font-bold text-brand">{{ card.action }}</button>
                </article>
            </aside>
        </main>

        <ProductSection v-for="section in sections" :key="section.id" :section="section" />

        <footer class="mt-6 border-t border-neutral-200">
            <div class="mx-auto flex max-w-[1160px] flex-wrap gap-x-8 gap-y-2 px-5 py-8 text-[12px] text-neutral-500">
                <p class="font-bold text-neutral-800">ccuppang</p>
                <button type="button">회사소개</button>
                <button type="button">이용약관</button>
                <button type="button">개인정보처리방침</button>
                <button type="button">고객센터</button>
                <a href="/seller" class="font-bold text-brand">판매자센터</a>
                <p class="w-full">포트폴리오용 화면입니다. 상품과 가격은 예시입니다.</p>
            </div>
        </footer>
    </div>
</template>
