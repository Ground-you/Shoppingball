<script setup>
import { nextTick, ref } from 'vue';

defineProps({
    error: { type: String, default: '' },
    inputClass: { type: String, default: 'h-12' },
});

const postal = defineModel('postal', { type: String, default: '' });
const line1 = defineModel('line1', { type: String, default: '' });
const line2 = defineModel('line2', { type: String, default: '' });

const postcodeBox = ref(null);
const postcodeOpen = ref(false);
const lookupError = ref('');

const openPostcode = async () => {
    lookupError.value = '';

    if (!window.daum?.Postcode) {
        lookupError.value = '우편번호 찾기를 불러오지 못했습니다. 네트워크를 확인해 주세요.';
        return;
    }

    postcodeOpen.value = true;
    await nextTick();
    postcodeBox.value.innerHTML = '';

    new window.daum.Postcode({
        width: '100%',
        height: '420px',
        oncomplete(data) {
            postal.value = data.zonecode;
            line1.value = data.userSelectedType === 'R' ? data.roadAddress : data.jibunAddress;
            postcodeOpen.value = false;
        },
    }).embed(postcodeBox.value);
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex gap-2">
            <input :value="postal" type="text" readonly placeholder="우편번호" :class="[inputClass, 'min-w-0 flex-1 rounded-lg border border-neutral-300 bg-neutral-50 px-4 text-sm outline-none']">
            <button type="button" :class="[inputClass, 'shrink-0 rounded-lg border border-brand px-3 text-sm font-bold text-brand']" @click="openPostcode">우편번호 찾기</button>
        </div>
        <input :value="line1" type="text" readonly placeholder="주소" :class="[inputClass, 'w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 text-sm outline-none']">
        <input v-model="line2" type="text" placeholder="상세주소" :class="[inputClass, 'w-full rounded-lg border border-neutral-300 px-4 text-sm outline-none focus:border-brand']">
        <p v-if="lookupError || error" class="text-xs text-brand">{{ lookupError || error }}</p>
    </div>

    <div v-if="postcodeOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 px-4">
        <section class="w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold">우편번호 찾기</h2>
                <button type="button" class="text-xl text-neutral-400" @click="postcodeOpen = false">×</button>
            </div>
            <div ref="postcodeBox" class="h-[460px] overflow-hidden rounded-lg border border-neutral-300"></div>
        </section>
    </div>
</template>
