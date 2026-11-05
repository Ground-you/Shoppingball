<script setup>
import axios from 'axios';

defineProps({
    account: { type: Object, required: true },
    section: { type: String, default: '' },
});

const items = [
    { id: 'home', label: '판매자 홈', href: '/seller' },
    { id: 'profile', label: '판매자 정보', href: '/seller/profile' },
    { id: 'store', label: '상점 관리', href: '/seller/store' },
    { id: 'products', label: '상품 관리', href: '/seller/products' },
    { id: 'inventory', label: '재고 관리', href: '/seller/inventory' },
];

const logout = async () => {
    const { data } = await axios.post('/seller/logout');
    window.location.href = data.redirect;
};
</script>

<template>
    <div class="min-h-screen bg-neutral-100 text-ink">
        <header class="border-b border-neutral-200 bg-white">
            <div class="mx-auto flex h-16 max-w-[1160px] items-center justify-between px-5">
                <a href="/seller" class="text-[22px] font-black tracking-tight text-brand">ccuppang 판매자</a>
                <div class="flex items-center gap-4 text-[13px]">
                    <span class="hidden text-neutral-500 sm:inline">{{ account.storeName || account.sellerName }}</span>
                    <a href="/" class="text-neutral-600">스토어 보기</a>
                    <button type="button" class="font-bold text-brand" @click="logout">로그아웃</button>
                </div>
            </div>
        </header>

        <div class="mx-auto grid max-w-[1160px] gap-5 px-5 py-6 lg:grid-cols-[220px_minmax(0,1fr)]">
            <aside class="h-fit rounded-2xl border border-neutral-200 bg-white p-2">
                <a
                    v-for="item in items"
                    :key="item.id"
                    :href="item.href"
                    class="block rounded-xl px-3 py-2.5 text-sm"
                    :class="section === item.id ? 'bg-brand font-bold text-white' : 'text-neutral-700 hover:bg-neutral-50'"
                >
                    {{ item.label }}
                </a>
            </aside>
            <div>
                <slot />
            </div>
        </div>
    </div>
</template>
