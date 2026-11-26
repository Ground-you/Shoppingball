<script setup>
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const editor = ref(null);
const color = ref('#111111');
const sizeOpen = ref(false);
const customSize = ref(18);
const message = ref('');
const selectedImage = ref(null);
const imageBox = ref({ top: 0, left: 0 });
const presets = [16, 20, 28, 40, 56];
const active = reactive({
    bold: false,
    italic: false,
    underline: false,
    strike: false,
    ul: false,
    ol: false,
    align: 'left',
});
let savedRange = null;
let writing = false;
let holdingToolbar = false;

const selectionInside = () => {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0 || !editor.value) {
        return false;
    }
    const node = selection.anchorNode;
    return node === editor.value || editor.value.contains(node);
};

const remember = () => {
    if (!selectionInside()) {
        return;
    }
    savedRange = window.getSelection().getRangeAt(0).cloneRange();
    refreshState();
};

const restore = () => {
    if (!savedRange) {
        return false;
    }
    const selection = window.getSelection();
    selection.removeAllRanges();
    try {
        selection.addRange(savedRange);
    } catch {
        savedRange = null;
        return false;
    }
    return selectionInside();
};

const refreshState = () => {
    active.bold = false;
    active.italic = false;
    active.underline = false;
    active.strike = false;
    active.ul = false;
    active.ol = false;
    active.align = '';
    if (!selectionInside()) {
        return;
    }

    const selection = window.getSelection();
    let node = selection.anchorNode;
    if (node?.nodeType === Node.TEXT_NODE) {
        node = node.parentElement;
    }
    if (!node || node === editor.value) {
        return;
    }

    const style = window.getComputedStyle(node);
    const decoration = `${style.textDecorationLine || ''} ${style.textDecoration || ''}`;
    active.bold = style.fontWeight === 'bold' || Number(style.fontWeight) >= 600;
    active.italic = style.fontStyle === 'italic';
    active.underline = decoration.includes('underline');
    active.strike = decoration.includes('line-through');
    active.ul = !!node.closest('ul');
    active.ol = !!node.closest('ol');
    const align = style.textAlign;
    active.align = align === 'center' || align === 'right' ? align : 'left';
};

const publish = () => {
    if (!editor.value) {
        return;
    }

    editor.value.querySelectorAll('[align]').forEach((node) => {
        const align = node.getAttribute('align');
        if (['left', 'center', 'right'].includes(align)) {
            node.style.textAlign = align;
        }
        node.removeAttribute('align');
    });
    const chosen = selectedImage.value;
    editor.value.querySelectorAll('img').forEach((node) => {
        node.classList.remove('detail-editor-selected');
        const match = (node.getAttribute('src') || '').match(/\/uploads\/products\/[A-Za-z0-9-]+\.(?:jpg|jpeg|png|webp|gif)$/i);
        if (match) {
            node.setAttribute('src', match[0]);
        }
    });
    editor.value.querySelectorAll('font').forEach((node) => {
        const span = document.createElement('span');
        if (node.color) {
            span.style.color = node.color;
        }
        if (node.getAttribute('size')) {
            span.style.fontSize = `${customSize.value}px`;
        }
        span.innerHTML = node.innerHTML;
        node.replaceWith(span);
    });
    writing = true;
    emit('update:modelValue', editor.value.innerHTML);
    if (chosen?.isConnected) {
        chosen.classList.add('detail-editor-selected');
    }
    nextTick(() => {
        writing = false;
    });
};

const prepareSelection = () => {
    const live = selectionInside() ? window.getSelection().getRangeAt(0) : null;
    if (live && !live.collapsed) {
        return true;
    }
    if (savedRange && !savedRange.collapsed) {
        return restore();
    }
    return !!live;
};

const alignSelectedImage = (align) => {
    const image = selectedImage.value;
    if (!image || !editor.value?.contains(image)) {
        return false;
    }

    let block = image.parentElement;
    if (!block || block === editor.value) {
        block = document.createElement('div');
        image.replaceWith(block);
        block.appendChild(image);
    }

    block.style.textAlign = align;
    active.align = align;
    message.value = '';
    publish();
    nextTick(placeImageButton);
    return true;
};

const command = (name, value = null) => {
    sizeOpen.value = false;
    const imageAlign = { justifyLeft: 'left', justifyCenter: 'center', justifyRight: 'right' }[name];
    if (imageAlign && alignSelectedImage(imageAlign)) {
        return;
    }
    if (!prepareSelection()) {
        message.value = '서식을 줄 글자를 먼저 드래그해 주세요.';
        return;
    }

    const selection = window.getSelection();
    const inline = ['bold', 'italic', 'underline', 'strikeThrough', 'foreColor', 'removeFormat'];
    if (inline.includes(name) && selection.isCollapsed) {
        message.value = '서식을 줄 글자를 먼저 드래그해 주세요.';
        return;
    }

    message.value = '';
    document.execCommand('styleWithCSS', false, true);
    document.execCommand(name, false, value);
    remember();
    publish();
};

const applySize = (pixels) => {
    const size = Math.round(Number(pixels));
    if (!Number.isFinite(size) || size < 8 || size > 96) {
        message.value = '글자 크기는 8px부터 96px까지 입력할 수 있습니다.';
        return;
    }

    customSize.value = size;
    if (!prepareSelection()) {
        message.value = '크기를 바꿀 글자를 먼저 드래그해 주세요.';
        return;
    }
    const selection = window.getSelection();
    if (!selection || selection.isCollapsed) {
        message.value = '크기를 바꿀 글자를 먼저 드래그해 주세요.';
        return;
    }

    message.value = '';
    document.execCommand('styleWithCSS', false, false);
    document.execCommand('fontSize', false, '7');
    editor.value?.querySelectorAll('font[size="7"]').forEach((node) => {
        const span = document.createElement('span');
        span.style.fontSize = `${size}px`;
        span.style.lineHeight = '1.4';
        span.innerHTML = node.innerHTML;
        node.replaceWith(span);
    });
    sizeOpen.value = false;
    remember();
    publish();
};

const keepSelection = (event) => {
    holdingToolbar = true;
    if (selectionInside()) {
        savedRange = window.getSelection().getRangeAt(0).cloneRange();
    }
    event.preventDefault();
};

const toggleSize = () => {
    if (!sizeOpen.value) {
        remember();
    }
    sizeOpen.value = !sizeOpen.value;
};

const placeImageButton = () => {
    if (!selectedImage.value || !editor.value) {
        return;
    }
    const imageRect = selectedImage.value.getBoundingClientRect();
    const editorRect = editor.value.getBoundingClientRect();
    imageBox.value = {
        top: imageRect.top - editorRect.top,
        left: imageRect.right - editorRect.left - 28,
    };
};

const clearImageSelection = () => {
    selectedImage.value?.classList.remove('detail-editor-selected');
    selectedImage.value = null;
};

const chooseImage = (image) => {
    clearImageSelection();
    image.classList.add('detail-editor-selected');
    selectedImage.value = image;
    const align = image.parentElement?.style.textAlign;
    active.align = align === 'center' || align === 'right' ? align : 'left';
    nextTick(placeImageButton);
};

const removeSelectedImage = () => {
    if (!selectedImage.value) {
        return;
    }
    selectedImage.value.remove();
    selectedImage.value = null;
    message.value = '';
    publish();
};

const onEditorMouseDown = (event) => {
    const image = event.target.closest?.('img');
    if (image && editor.value?.contains(image)) {
        event.preventDefault();
        chooseImage(image);
        return;
    }
    clearImageSelection();
};

const onEditorKeydown = (event) => {
    if (!selectedImage.value || event.target.closest?.('input, textarea')) {
        return;
    }
    if (event.key === 'Delete' || event.key === 'Backspace') {
        event.preventDefault();
        removeSelectedImage();
    }
};

const insertImage = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) {
        return;
    }

    message.value = '';
    const body = new FormData();
    body.append('image', file);

    try {
        const { data } = await axios.post('/seller/products/detail-images', body);
        const image = document.createElement('img');
        image.alt = '';
        image.src = data.url;
        const block = document.createElement('div');
        block.style.textAlign = 'left';
        block.appendChild(image);

        restore();
        let anchor = null;
        const selection = window.getSelection();
        if (selection && selection.rangeCount > 0 && editor.value?.contains(selection.anchorNode) && selection.anchorNode !== editor.value) {
            anchor = selection.anchorNode;
            while (anchor.parentElement && anchor.parentElement !== editor.value) {
                anchor = anchor.parentElement;
            }
            if (anchor.parentElement !== editor.value) {
                anchor = null;
            }
        }

        if (anchor) {
            anchor.after(block);
        } else {
            editor.value.appendChild(block);
        }

        const line = document.createElement('div');
        line.appendChild(document.createElement('br'));
        block.after(line);
        const range = document.createRange();
        range.setStart(line, 0);
        range.collapse(true);
        const next = window.getSelection();
        next.removeAllRanges();
        next.addRange(range);
        savedRange = range.cloneRange();
        publish();
    } catch (error) {
        message.value = error.response?.data?.message || '이미지를 넣지 못했습니다.';
    }
};

const onSelectionChange = () => {
    if (holdingToolbar || !selectionInside()) {
        return;
    }
    savedRange = window.getSelection().getRangeAt(0).cloneRange();
    refreshState();
};

const releaseToolbar = () => {
    setTimeout(() => {
        holdingToolbar = false;
    }, 0);
};

onMounted(() => {
    if (editor.value) {
        editor.value.innerHTML = props.modelValue || '';
    }
    document.addEventListener('selectionchange', onSelectionChange);
    document.addEventListener('mouseup', releaseToolbar);
    document.addEventListener('keydown', onEditorKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('selectionchange', onSelectionChange);
    document.removeEventListener('mouseup', releaseToolbar);
    document.removeEventListener('keydown', onEditorKeydown);
});

watch(() => props.modelValue, (value) => {
    if (writing || !editor.value || document.activeElement === editor.value) {
        return;
    }
    if (editor.value.innerHTML !== (value || '')) {
        editor.value.innerHTML = value || '';
    }
});
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center gap-1 rounded-t-lg border border-neutral-200 bg-neutral-50 p-2">
            <button type="button" class="h-8 rounded-md px-2 text-sm font-bold" :class="active.bold ? 'bg-neutral-900 text-white' : ''" title="굵게" @mousedown="keepSelection" @click="command('bold')">굵게</button>
            <button type="button" class="h-8 rounded-md px-2 text-sm italic" :class="active.italic ? 'bg-neutral-900 text-white' : ''" title="기울임" @mousedown="keepSelection" @click="command('italic')">기울임</button>
            <button type="button" class="h-8 rounded-md px-2 text-sm underline" :class="active.underline ? 'bg-neutral-900 text-white' : ''" title="밑줄" @mousedown="keepSelection" @click="command('underline')">밑줄</button>
            <button type="button" class="h-8 rounded-md px-2 text-sm line-through" :class="active.strike ? 'bg-neutral-900 text-white' : ''" title="취소선" @mousedown="keepSelection" @click="command('strikeThrough')">취소선</button>
            <button type="button" class="h-8 rounded-md px-2 text-sm" :class="active.ul ? 'bg-neutral-900 text-white' : ''" title="글머리 목록" @mousedown="keepSelection" @click="command('insertUnorderedList')">목록</button>
            <button type="button" class="h-8 rounded-md px-2 text-sm" :class="active.ol ? 'bg-neutral-900 text-white' : ''" title="번호 목록" @mousedown="keepSelection" @click="command('insertOrderedList')">번호</button>
            <button type="button" class="h-8 rounded-md px-2 text-sm" title="서식 지우기" @mousedown="keepSelection" @click="command('removeFormat')">서식 지우기</button>

            <div class="relative">
                <button type="button" class="h-8 rounded-md border border-neutral-200 bg-white px-2 text-sm" @mousedown="keepSelection" @click="toggleSize">글자 크기</button>
                <div v-if="sizeOpen" class="absolute left-0 z-20 mt-1 w-44 rounded-lg border border-neutral-200 bg-white p-2 shadow-lg">
                    <button
                        v-for="preset in presets"
                        :key="preset"
                        type="button"
                        class="block h-8 w-full rounded-md px-2 text-left text-sm hover:bg-neutral-50"
                        @mousedown="keepSelection"
                        @click="applySize(preset)"
                    >{{ preset }}px</button>
                    <div class="mt-2 border-t border-neutral-100 pt-2">
                        <p class="px-1 text-[11px] text-neutral-500">직접 입력</p>
                        <div class="mt-1 flex items-center gap-1">
                            <input v-model="customSize" type="number" min="8" max="96" class="h-8 w-full rounded-md border border-neutral-200 px-2 text-sm outline-none focus:border-brand" @mousedown="remember" @keydown.enter.prevent="applySize(customSize)">
                            <span class="text-xs text-neutral-400">px</span>
                            <button type="button" class="h-8 shrink-0 rounded-md px-2 text-sm font-bold text-brand" @mousedown="keepSelection" @click="applySize(customSize)">적용</button>
                        </div>
                    </div>
                </div>
            </div>

            <label class="flex h-8 items-center gap-1 rounded-md border border-neutral-200 bg-white px-2 text-sm" @mousedown="remember">
                색
                <input v-model="color" type="color" class="h-5 w-5" @input="command('foreColor', color)">
            </label>
            <button type="button" class="h-8 rounded-md px-2 text-sm" :class="active.align === 'left' ? 'bg-neutral-900 text-white' : ''" @mousedown="keepSelection" @click="command('justifyLeft')">왼쪽</button>
            <button type="button" class="h-8 rounded-md px-2 text-sm" :class="active.align === 'center' ? 'bg-neutral-900 text-white' : ''" @mousedown="keepSelection" @click="command('justifyCenter')">가운데</button>
            <button type="button" class="h-8 rounded-md px-2 text-sm" :class="active.align === 'right' ? 'bg-neutral-900 text-white' : ''" @mousedown="keepSelection" @click="command('justifyRight')">오른쪽</button>
            <label class="h-8 cursor-pointer rounded-md px-2 text-sm leading-8 font-bold text-brand" @mousedown="remember">
                이미지
                <input type="file" accept="image/*" class="hidden" @change="insertImage">
            </label>
        </div>
        <div class="relative">
            <div
                ref="editor"
                contenteditable="true"
                class="detail-editor min-h-52 rounded-b-lg border border-t-0 border-neutral-200 px-3 py-3 text-sm outline-none focus:border-brand"
                @input="publish"
                @mouseup="remember"
                @keyup="remember"
                @mousedown="onEditorMouseDown"
                @scroll="placeImageButton"
            ></div>
            <button
                v-if="selectedImage"
                type="button"
                class="absolute z-10 grid h-7 w-7 place-items-center rounded-full bg-neutral-900 text-sm text-white"
                :style="{ top: `${imageBox.top}px`, left: `${imageBox.left}px` }"
                @mousedown.prevent
                @click="removeSelectedImage"
            >×</button>
        </div>
        <p v-if="message" class="mt-1 text-xs text-brand">{{ message }}</p>
    </div>
</template>

<style>
.detail-editor {
    line-height: 1.6;
}
.detail-editor [style*="font-size"] {
    line-height: 1.4;
}
.detail-editor img {
    display: block;
    width: fit-content;
    max-width: 100%;
    height: auto;
    margin: 8px 0;
    cursor: pointer;
}
.detail-editor div[style*="text-align: center"] > img,
.detail-editor div[style*="text-align:center"] > img {
    margin-left: auto;
    margin-right: auto;
}
.detail-editor div[style*="text-align: right"] > img,
.detail-editor div[style*="text-align:right"] > img {
    margin-left: auto;
    margin-right: 0;
}
.detail-editor img.detail-editor-selected {
    outline: 2px solid #1a6dff;
    outline-offset: 2px;
}
.detail-editor ul {
    list-style: disc;
    padding-left: 1.25rem;
}
.detail-editor ol {
    list-style: decimal;
    padding-left: 1.25rem;
}
</style>
