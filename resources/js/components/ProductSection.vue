<script setup>
import ProductCard from './ProductCard.vue';

defineProps({
    section: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <section class="mx-auto max-w-[1160px] px-5 py-6" :class="section.tone === 'gold' ? '' : ''">
        <div v-if="section.tone === 'gold'" class="rounded-2xl bg-[#1c140c] px-6 py-6">
            <div class="mb-4 flex items-end justify-between">
                <h2 class="text-[22px] font-bold text-amber-300">{{ section.title }}</h2>
                <button type="button" class="text-[13px] text-white/70">더보기</button>
            </div>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <ProductCard v-for="product in section.products" :key="product.id" :product="product" dark />
            </div>
        </div>

        <template v-else>
            <div class="mb-4 flex items-end justify-between">
                <h2 class="text-[22px] font-bold">{{ section.title }}</h2>
                <button type="button" class="text-[13px] text-neutral-400">더보기</button>
            </div>
            <div class="grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-3 lg:grid-cols-6">
                <ProductCard v-for="product in section.products" :key="product.id" :product="product" />
            </div>
        </template>
    </section>
</template>
