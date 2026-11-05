<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import SellerShell from '../../Components/SellerShell.vue';
import { firstError } from '../../sellerError';

const props = defineProps({
    account: { type: Object, required: true },
    section: { type: String, default: 'store' },
    store: { type: Object, required: true },
});

const form = reactive({
    store_name: props.store.name,
    introduction: props.store.introduction,
    operation_status: props.store.operationStatus,
    shipping_policy_notice: props.store.shippingNotice,
    exchange_return_policy_notice: props.store.exchangeNotice,
});
const logoPreview = ref(props.store.logo || '');
const bannerPreview = ref(props.store.banner || '');
const logoFile = ref(null);
const bannerFile = ref(null);
const removeLogo = ref(false);
const removeBanner = ref(false);
const message = ref('');
const error = ref('');

const pick = (event, target) => {
    const file = event.target.files?.[0];
    if (!file) return;
    const url = URL.createObjectURL(file);
    if (target === 'logo') {
        logoFile.value = file;
        logoPreview.value = url;
        removeLogo.value = false;
    } else {
        bannerFile.value = file;
        bannerPreview.value = url;
        removeBanner.value = false;
    }
};

const clearImage = (target) => {
    if (target === 'logo') {
        logoFile.value = null;
        logoPreview.value = '';
        removeLogo.value = true;
    } else {
        bannerFile.value = null;
        bannerPreview.value = '';
        removeBanner.value = true;
    }
};

const save = async () => {
    message.value = '';
    error.value = '';
    const body = new FormData();
    Object.entries(form).forEach(([key, value]) => body.append(key, value ?? ''));
    body.append('remove_logo', removeLogo.value ? '1' : '0');
    body.append('remove_banner', removeBanner.value ? '1' : '0');
    if (logoFile.value) body.append('logo', logoFile.value);
    if (bannerFile.value) body.append('banner', bannerFile.value);

    try {
        const { data } = await axios.post('/seller/store', body);
        message.value = data.message;
    } catch (caught) {
        error.value = firstError(caught);
    }
};
</script>

<template>
    <SellerShell :account="account" :section="section">
        <form class="rounded-2xl border border-neutral-200 bg-white p-6" @submit.prevent="save">
            <h1 class="text-[22px] font-bold">상점 관리</h1>
            <p v-if="message" class="mt-3 text-sm font-bold text-brand">{{ message }}</p>
            <p v-if="error" class="mt-3 text-sm text-brand">{{ error }}</p>

            <label class="mt-6 block text-sm">
                <span class="mb-1 block text-[12px] text-neutral-500">상점 이름</span>
                <input v-model="form.store_name" class="h-11 w-full rounded-lg border border-neutral-200 px-3 outline-none focus:border-brand">
            </label>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <p class="text-[12px] text-neutral-500">상점 로고</p>
                    <div class="mt-2 flex h-28 items-center justify-center overflow-hidden rounded-2xl bg-neutral-100">
                        <img v-if="logoPreview" :src="logoPreview" alt="" class="h-full w-full object-contain">
                        <span v-else class="text-sm text-neutral-400">로고 없음</span>
                    </div>
                    <div class="mt-2 flex gap-3 text-sm">
                        <label class="cursor-pointer font-bold text-brand">
                            등록
                            <input type="file" accept="image/*" class="hidden" @change="pick($event, 'logo')">
                        </label>
                        <button type="button" class="text-neutral-500" @click="clearImage('logo')">삭제</button>
                    </div>
                </div>
                <div>
                    <p class="text-[12px] text-neutral-500">상점 배너 (대표 이미지)</p>
                    <div class="mt-2 flex h-28 items-center justify-center overflow-hidden rounded-2xl bg-neutral-100">
                        <img v-if="bannerPreview" :src="bannerPreview" alt="" class="h-full w-full object-cover">
                        <span v-else class="text-sm text-neutral-400">배너 없음</span>
                    </div>
                    <div class="mt-2 flex gap-3 text-sm">
                        <label class="cursor-pointer font-bold text-brand">
                            등록
                            <input type="file" accept="image/*" class="hidden" @change="pick($event, 'banner')">
                        </label>
                        <button type="button" class="text-neutral-500" @click="clearImage('banner')">삭제</button>
                    </div>
                </div>
            </div>

            <label class="mt-5 block text-sm">
                <span class="mb-1 block text-[12px] text-neutral-500">상점 소개</span>
                <textarea v-model="form.introduction" rows="4" class="w-full rounded-lg border border-neutral-200 px-3 py-2 outline-none focus:border-brand" />
            </label>

            <fieldset class="mt-5">
                <legend class="text-[12px] text-neutral-500">상점 운영 상태</legend>
                <div class="mt-2 flex gap-4 text-sm">
                    <label class="flex items-center gap-2"><input v-model="form.operation_status" type="radio" value="OPEN"> 영업중</label>
                    <label class="flex items-center gap-2"><input v-model="form.operation_status" type="radio" value="PAUSED"> 휴업</label>
                </div>
            </fieldset>

            <label class="mt-5 block text-sm">
                <span class="mb-1 block text-[12px] text-neutral-500">배송 정책 안내</span>
                <textarea v-model="form.shipping_policy_notice" rows="3" class="w-full rounded-lg border border-neutral-200 px-3 py-2 outline-none focus:border-brand" />
            </label>
            <label class="mt-4 block text-sm">
                <span class="mb-1 block text-[12px] text-neutral-500">교환/반품 정책 안내</span>
                <textarea v-model="form.exchange_return_policy_notice" rows="3" class="w-full rounded-lg border border-neutral-200 px-3 py-2 outline-none focus:border-brand" />
            </label>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="h-11 rounded-lg bg-brand px-5 text-sm font-bold text-white">저장</button>
            </div>
        </form>
    </SellerShell>
</template>
