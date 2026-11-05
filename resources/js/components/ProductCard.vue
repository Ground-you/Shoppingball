<script setup>
defineProps({
    product: {
        type: Object,
        required: true,
    },
    dark: {
        type: Boolean,
        default: false,
    },
});

const won = (value) => `${Number(value).toLocaleString('ko-KR')}원`;

const badgeClass = (badge) => {
    if (badge === '오늘도착') return 'bg-arrive';
    if (badge === '골드딜') return 'bg-amber-500';
    if (badge === '신선') return 'bg-emerald-600';
    return 'bg-brand';
};
</script>

<template>
    <component :is="product.href ? 'a' : 'article'" :href="product.href || undefined" class="block">
        <div class="relative aspect-square overflow-hidden rounded-xl bg-neutral-100">
            <img v-if="product.image" :src="product.image" :alt="product.name" class="h-full w-full object-cover">
            <div v-else class="grid h-full place-items-center text-[11px] text-neutral-400">이미지 없음</div>
            <span v-if="product.badge" class="absolute top-2 left-2 px-1.5 py-0.5 text-[10px] font-bold text-white" :class="badgeClass(product.badge)">
                {{ product.badge }}
            </span>
        </div>
        <h3 class="mt-2 line-clamp-2 text-[13px] leading-snug" :class="dark ? 'text-white' : 'text-neutral-800'">
            {{ product.name }}
        </h3>
        <p v-if="product.description" class="mt-1 line-clamp-2 text-[11px] leading-snug" :class="dark ? 'text-white/60' : 'text-neutral-500'">
            {{ product.description }}
        </p>
        <p class="mt-1 text-[16px] font-bold" :class="dark ? 'text-white' : 'text-neutral-950'">
            <span v-if="product.discount > 0" class="mr-1 text-brand">{{ product.discount }}%</span>{{ won(product.price) }}
        </p>
        <p v-if="product.originalPrice > product.price" class="text-[11px] line-through" :class="dark ? 'text-white/50' : 'text-neutral-400'">{{ won(product.originalPrice) }}</p>
        <p v-if="product.reviews" class="mt-1 text-[11px] text-amber-500">★ {{ product.rating }} <span :class="dark ? 'text-white/50' : 'text-neutral-400'">({{ product.reviews.toLocaleString('ko-KR') }})</span></p>
    </component>
</template>
